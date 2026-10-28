<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Proveedores\EstadoProveedor;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Cliente;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Venta;
use App\Infrastructure\Persistence\Eloquent\VentaDetalle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.ventas.index', [
            'tab' => $request->string('tab')->value() === 'clientes' ? 'clientes' : 'ventas',
            'ventas' => Venta::with(['cliente', 'proveedor', 'detalles.producto'])->latest('vendida_en')->paginate(20, ['*'], 'ventas'),
            'clientes' => Cliente::orderBy('nombre')->paginate(20, ['*'], 'clientes'),
            'clientesActivos' => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'proveedoresActivos' => Proveedor::where('estado', 'activo')->orderBy('nombres')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'ventasHoy' => Venta::whereDate('vendida_en', today())->where('estado', 'completada')->sum('total'),
        ]);
    }

    public function storeCliente(Request $request): RedirectResponse
    {
        return app(CompradorController::class)->store($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo_comprador' => ['required', Rule::in(['proveedor', 'cliente', 'mayorista'])],
            'cliente_id' => ['nullable', 'required_unless:tipo_comprador,proveedor', 'prohibited_if:tipo_comprador,proveedor', 'integer', 'exists:clientes,id'],
            'proveedor_id' => ['nullable', 'required_if:tipo_comprador,proveedor', 'prohibited_unless:tipo_comprador,proveedor', 'integer', 'exists:proveedores,id'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'estado' => ['required', Rule::in(['pendiente', 'completada'])],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'productos' => ['required', 'array', 'min:1', 'max:20'],
            'productos.*' => ['array'],
            'productos.*.producto_id' => ['nullable', 'integer', 'distinct', 'exists:productos,id'],
            'productos.*.cantidad' => ['nullable', 'numeric', 'gt:0', 'max:1000000', 'decimal:0,3'],
        ]);
        $filas = collect($datos['productos'])->filter(fn ($fila) => filled($fila['producto_id'] ?? null) || filled($fila['cantidad'] ?? null))->values();
        if ($filas->isEmpty() || $filas->contains(fn ($fila) => empty($fila['producto_id']) || empty($fila['cantidad']))) {
            throw ValidationException::withMessages(['productos' => 'Selecciona un producto y una cantidad en cada fila utilizada.']);
        }

        DB::transaction(function () use ($datos, $filas): void {
            $tipo = $datos['tipo_comprador'];
            if ($tipo === 'proveedor') {
                $comprador = Proveedor::whereKey($datos['proveedor_id'])->lockForUpdate()->firstOrFail();
                if ($comprador->estado !== EstadoProveedor::Activo) {
                    throw ValidationException::withMessages(['proveedor_id' => 'Selecciona un proveedor activo.']);
                }
            } else {
                $comprador = Cliente::whereKey($datos['cliente_id'])->lockForUpdate()->firstOrFail();
                if (! $comprador->activo || ($comprador->tipo === 'mayorista') !== ($tipo === 'mayorista')) {
                    throw ValidationException::withMessages(['cliente_id' => 'El comprador debe estar activo y corresponder al tipo seleccionado.']);
                }
            }
            $productos = Producto::whereIn('id', $filas->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lineas = $filas->map(function ($fila) use ($productos, $tipo, $datos) {
                $producto = $productos->get($fila['producto_id']);
                $precio = (float) ($producto?->{'precio_'.$tipo} ?? 0);
                if ($producto === null || ! $producto->activo || $precio <= 0) {
                    throw ValidationException::withMessages(['productos' => 'El producto debe estar activo y tener precio para '.$tipo.'. Configúralo en Productos.']);
                }
                $cantidad = (float) $fila['cantidad'];
                if ($datos['estado'] === 'completada' && (float) $producto->existencia < $cantidad) {
                    throw ValidationException::withMessages(['productos' => "No alcanza {$producto->nombre}: hay {$producto->existencia} {$producto->unidad_produccion} disponibles y solicitaste {$cantidad}."]);
                }

                return ['producto' => $producto, 'cantidad' => $cantidad, 'precio' => $precio, 'subtotal' => round($cantidad * $precio, 2)];
            });
            $subtotal = round($lineas->sum('subtotal'), 2);
            $descuento = (float) ($datos['descuento'] ?? 0);
            if ($subtotal > 9999999999.99 || $descuento > $subtotal) {
                throw ValidationException::withMessages(['descuento' => 'Revisa el importe y el descuento de la venta. El descuento no puede superar el subtotal.']);
            }
            $codigo = 'VTA-'.strtoupper(bin2hex(random_bytes(5)));
            $venta = Venta::create([
                'codigo' => $codigo, 'tipo_comprador' => $tipo,
                'cliente_id' => $tipo !== 'proveedor' ? $comprador->id : null,
                'proveedor_id' => $tipo === 'proveedor' ? $comprador->id : null, 'usuario_id' => auth('operador')->id(),
                'subtotal' => $subtotal, 'descuento' => $descuento, 'total' => round($subtotal - $descuento, 2),
                'estado' => $datos['estado'], 'vendida_en' => now(), 'observaciones' => $datos['observaciones'] ?? null,
            ]);
            foreach ($lineas as $linea) {
                $producto = $linea['producto'];
                if ($datos['estado'] === 'completada') {
                    $this->despachar($producto, $linea['cantidad'], $codigo);
                }
                VentaDetalle::create([
                    'venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio'], 'subtotal' => $linea['subtotal'],
                ]);
            }
        });

        return back()->with('estado', $datos['estado'] === 'completada' ? 'Venta completada y productos despachados.' : 'Venta guardada como pendiente de despacho.');
    }

    public function updateEstado(Request $request, Venta $venta): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['completada', 'anulada'])]]);
        DB::transaction(function () use ($datos, $venta): void {
            $venta = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();
            if ($venta->estado !== 'pendiente') {
                throw ValidationException::withMessages(['estado' => 'Esta venta ya no está pendiente. Recarga la página para consultar su estado actual.']);
            }
            if ($datos['estado'] === 'completada') {
                foreach ($venta->detalles as $detalle) {
                    $producto = Producto::lockForUpdate()->findOrFail($detalle->producto_id);
                    if ((float) $producto->existencia < (float) $detalle->cantidad) {
                        throw ValidationException::withMessages(['productos' => "No alcanza {$producto->nombre}: hay {$producto->existencia} {$producto->unidad_produccion} disponibles. Revisa el inventario antes de completar el despacho."]);
                    }
                    $this->despachar($producto, (float) $detalle->cantidad, $venta->codigo);
                }
            }
            $venta->update(['estado' => $datos['estado']]);
        });

        return back()->with('estado', 'Estado de la venta actualizado.');
    }

    private function despachar(Producto $producto, float $cantidad, string $codigo): void
    {
        $producto->decrement('existencia', $cantidad);
        MovimientoProducto::create([
            'producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => -$cantidad,
            'unidad' => $producto->unidad_produccion, 'motivo' => 'Despacho '.$codigo,
            'usuario_id' => auth('operador')->id(), 'fecha' => now(),
        ]);
    }
}
