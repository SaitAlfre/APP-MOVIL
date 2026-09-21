<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Cliente;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Venta;
use App\Infrastructure\Persistence\Eloquent\VentaDetalle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.ventas.index', [
            'tab' => $request->string('tab')->value() === 'clientes' ? 'clientes' : 'ventas',
            'ventas' => Venta::with(['cliente', 'detalles.producto'])->latest('vendida_en')->paginate(20, ['*'], 'ventas'),
            'clientes' => Cliente::orderBy('nombre')->paginate(20, ['*'], 'clientes'),
            'clientesActivos' => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'ventasHoy' => Venta::whereDate('vendida_en', today())->where('estado', 'completada')->sum('total'),
        ]);
    }

    public function storeCliente(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['mayorista', 'restaurante', 'minorista', 'institucional'])],
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['required', 'string', 'max:20', 'unique:clientes,documento'],
            'celular' => ['nullable', 'string', 'max:20'],
            'ciudad' => ['nullable', 'string', 'max:80'],
        ]);
        $siguiente = ((int) Cliente::max('id')) + 1;
        Cliente::create($datos + ['codigo' => 'CLI-'.str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT)]);

        return redirect()->route('admin.ventas.index', ['tab' => 'clientes'])->with('estado', 'Cliente registrado.');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'productos' => collect($request->input('productos', []))
                ->filter(fn (array $fila) => filled($fila['producto_id'] ?? null) || filled($fila['cantidad'] ?? null) || filled($fila['precio_unitario'] ?? null))
                ->values()->all(),
        ]);
        $datos = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['required', Rule::in(['pendiente', 'completada'])],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'productos' => ['required', 'array', 'min:1', 'max:20'],
            'productos.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'productos.*.precio_unitario' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($datos): void {
            $subtotal = collect($datos['productos'])->sum(fn ($fila) => round((float) $fila['cantidad'] * (float) $fila['precio_unitario'], 2));
            $descuento = (float) ($datos['descuento'] ?? 0);
            abort_if($descuento > $subtotal, 422, 'El descuento no puede superar el subtotal.');
            $codigo = 'VTA-'.str_pad((string) (((int) Venta::max('id')) + 1), 4, '0', STR_PAD_LEFT);
            $venta = Venta::create([
                'codigo' => $codigo, 'cliente_id' => $datos['cliente_id'], 'usuario_id' => auth('operador')->id(),
                'subtotal' => $subtotal, 'descuento' => $descuento, 'total' => $subtotal - $descuento,
                'estado' => $datos['estado'], 'vendida_en' => now(), 'observaciones' => $datos['observaciones'] ?? null,
            ]);

            foreach ($datos['productos'] as $fila) {
                $producto = Producto::lockForUpdate()->findOrFail($fila['producto_id']);
                $cantidad = (float) $fila['cantidad'];
                if ($datos['estado'] === 'completada') {
                    abort_if((float) $producto->existencia < $cantidad, 422, "Stock insuficiente de {$producto->nombre}.");
                    $producto->decrement('existencia', $cantidad);
                    MovimientoProducto::create([
                        'producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => -$cantidad,
                        'unidad' => $producto->unidad_produccion, 'motivo' => 'Despacho '.$codigo,
                        'usuario_id' => auth('operador')->id(), 'fecha' => now(),
                    ]);
                }
                VentaDetalle::create([
                    'venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $cantidad,
                    'precio_unitario' => $fila['precio_unitario'], 'subtotal' => round($cantidad * (float) $fila['precio_unitario'], 2),
                ]);
            }
        });

        return back()->with('estado', 'Venta registrada y stock actualizado.');
    }

    public function updateEstado(Request $request, Venta $venta): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['completada', 'anulada'])]]);
        abort_if($venta->estado !== 'pendiente', 409, 'Solo se puede cambiar una venta pendiente.');

        DB::transaction(function () use ($datos, $venta): void {
            if ($datos['estado'] === 'completada') {
                foreach ($venta->detalles as $detalle) {
                    $producto = Producto::lockForUpdate()->findOrFail($detalle->producto_id);
                    abort_if((float) $producto->existencia < (float) $detalle->cantidad, 422, "Stock insuficiente de {$producto->nombre}.");
                    $producto->decrement('existencia', $detalle->cantidad);
                    MovimientoProducto::create([
                        'producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => -(float) $detalle->cantidad,
                        'unidad' => $producto->unidad_produccion, 'motivo' => 'Despacho '.$venta->codigo,
                        'usuario_id' => auth('operador')->id(), 'fecha' => now(),
                    ]);
                }
            }
            $venta->update(['estado' => $datos['estado']]);
        });

        return back()->with('estado', 'Estado de la venta actualizado.');
    }
}
