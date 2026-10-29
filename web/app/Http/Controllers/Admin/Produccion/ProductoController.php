<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\GestionRecetas;
use App\Application\Produccion\ObtenerResumenPlantaHoy;
use App\Application\Productos\ActualizarProductoUseCase;
use App\Application\Productos\CambiarEstadoProductoUseCase;
use App\Application\Productos\CrearProductoUseCase;
use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductoController extends Controller
{
    public function index(ObtenerResumenPlantaHoy $resumen): View
    {
        $productos = Producto::with('receta')->orderByDesc('activo')->orderBy('nombre')->get();
        $producidoMes = DB::table('lotes_produccion')->where('estado', 'finalizado')
            ->where('fecha', '>=', now('America/Lima')->startOfMonth()->toDateString())
            ->groupBy('producto_id')->pluck(DB::raw('SUM(unidades_producidas)'), 'producto_id');

        return view('admin.produccion.productos.index', [
            'productos' => $productos,
            'producidoMes' => $producidoMes,
            'resumen' => $resumen->ejecutar(),
        ]);
    }

    public function imagen(int $producto): StreamedResponse
    {
        $registro = Producto::findOrFail($producto);
        abort_if($registro->imagen === null || ! Storage::disk('public')->exists($registro->imagen), 404);

        return Storage::disk('public')->response($registro->imagen, null, ['Cache-Control' => 'private, max-age=604800']);
    }

    public function create(): View
    {
        return view('admin.produccion.productos.form', ['producto' => null, 'recetas' => $this->recetasDisponibles()]);
    }

    public function store(Request $request, CrearProductoUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            DB::transaction(function () use ($crear, $datos, $request): void {
                $producto = $crear->ejecutar(...$datos);
                $this->guardarDatosComerciales($producto->id, $request);
                app(GestionRecetas::class)->vincular($producto->id, $request->integer('receta_id'));
            });
        } catch (ProductoInvalidoException $e) {
            return back()->withErrors(['nombre' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Producto creado correctamente.');
    }

    public function edit(int $producto): View
    {
        $registro = Producto::with('receta')->findOrFail($producto);

        return view('admin.produccion.productos.form', ['producto' => $registro, 'recetas' => $this->recetasDisponibles($registro)]);
    }

    public function update(int $producto, Request $request, ActualizarProductoUseCase $actualizar): RedirectResponse
    {
        $datos = $this->validarDatos($request, $producto);

        try {
            DB::transaction(function () use ($actualizar, $producto, $datos, $request): void {
                Receta::whereKey($request->integer('receta_id'))->lockForUpdate()->firstOrFail();
                $registro = Producto::whereKey($producto)->lockForUpdate()->firstOrFail();
                $datos[5] = $registro->litros_por_unidad !== null ? (float) $registro->litros_por_unidad : null;
                $datos[6] = $registro->otros_insumos;
                $actualizar->ejecutar($producto, ...$datos);
                $this->guardarDatosComerciales($producto, $request);
                app(GestionRecetas::class)->vincular($producto, $request->integer('receta_id'));
            });
        } catch (ProductoInvalidoException $e) {
            return back()->withErrors(['nombre' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Producto actualizado correctamente.');
    }

    public function actualizarMinimo(int $producto, Request $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validate(['stock_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3']],
            ['stock_minimo.decimal' => 'Usa como máximo 3 decimales.', 'stock_minimo.min' => 'El mínimo no puede ser negativo.']);
        $registro = Producto::findOrFail($producto);
        $registro->update(['stock_minimo' => $datos['stock_minimo'] ?? null]);
        $mensaje = $registro->stock_minimo !== null ? "Aviso guardado: {$registro->nombre} se marcará al llegar a ".(float) $registro->stock_minimo.'.' : "Aviso quitado para {$registro->nombre}.";

        return $request->expectsJson()
            ? response()->json(['mensaje' => $mensaje, 'stock_minimo' => $registro->stock_minimo !== null ? (float) $registro->stock_minimo : null])
            : back()->with('estado', $mensaje);
    }

    public function cambiarEstado(int $producto, Request $request, CambiarEstadoProductoUseCase $cambiar): RedirectResponse
    {
        $request->validate(['activo' => ['required', 'boolean']]);

        $cambiar->ejecutar($producto, $request->boolean('activo'));

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Estado del producto actualizado.');
    }

    public function show(int $producto, ProductoRepositoryInterface $productos): View
    {
        abort_if($productos->buscarPorId($producto) === null, 404);
        $registro = Producto::with('receta')->findOrFail($producto);

        return view('admin.produccion.productos.show', [
            'producto' => $registro,
            'ingredientes' => $registro->receta_id !== null ? DB::table('receta_materiales as receta')->join('materiales as material', 'material.id', '=', 'receta.material_id')
                ->where('receta.receta_id', $registro->receta_id)->orderBy('material.nombre')->get(['material.nombre', 'material.unidad', 'receta.cantidad']) : collect(),
            'lotes' => DB::table('lotes_produccion')->where('producto_id', $producto)->orderByDesc('fecha')->orderByDesc('id')->limit(5)->get(),
        ]);
    }

    /** Recetas activas, más la receta actual del producto que se edita aunque esté inactiva. */
    private function recetasDisponibles(?Producto $producto = null): Collection
    {
        return Receta::withCount('productos')->where('activo', true)
            ->when($producto?->receta_id !== null, fn ($q) => $q->orWhere('id', $producto->receta_id))
            ->orderBy('nombre')->get();
    }

    /** @return array{0: string, 1: string, 2: string, 3: ?float, 4: ?string, 5: ?float, 6: ?string} */
    private function validarDatos(Request $request, ?int $productoId = null): array
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => ['nullable', 'string', 'max:30', 'alpha_dash', Rule::unique('productos', 'codigo')->ignore($productoId)],
            'presentacion' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'contenido_por_unidad' => ['nullable', 'numeric', 'gt:0'],
            'unidad_contenido' => ['nullable', 'string', 'max:20'],
            'receta_id' => ['required', 'integer', 'exists:recetas,id'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quitar_imagen' => ['nullable', 'boolean'],
            'cantidad_receta' => ['prohibited'],
            'litros_por_unidad' => ['prohibited'],
            'otros_insumos' => ['prohibited'],
            'ingredientes' => ['prohibited'],
            'precio_proveedor' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
            'precio_cliente' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
            'precio_mayorista' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
        ], ['receta_id.required' => 'Elige la receta con la que se produce este producto.']);

        return [
            $request->string('nombre')->toString(),
            $request->string('presentacion')->toString(),
            GestionRecetas::unidadDeProducto(Receta::findOrFail($request->integer('receta_id'))->unidad_base,
                $request->filled('contenido_por_unidad') ? (float) $request->input('contenido_por_unidad') : null, $request->input('unidad_contenido')),
            $request->filled('contenido_por_unidad') ? (float) $request->input('contenido_por_unidad') : null,
            $request->string('unidad_contenido')->toString() ?: null,
            null,
            null,
        ];
    }

    private function guardarDatosComerciales(int $productoId, Request $request): void
    {
        $producto = Producto::findOrFail($productoId);
        $datos = collect(['precio_proveedor', 'precio_cliente', 'precio_mayorista', 'codigo', 'descripcion', 'stock_minimo'])
            ->mapWithKeys(fn ($campo) => [$campo => $request->filled($campo) ? $request->input($campo) : null])->all();
        if ($request->hasFile('imagen') || $request->boolean('quitar_imagen')) {
            if ($producto->imagen !== null) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $datos['imagen'] = $request->hasFile('imagen') ? $request->file('imagen')->store('productos', 'public') : null;
        }
        $producto->update($datos);
    }
}
