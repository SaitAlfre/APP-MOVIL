<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\GestionRecetas;
use App\Application\Productos\ActualizarProductoUseCase;
use App\Application\Productos\CambiarEstadoProductoUseCase;
use App\Application\Productos\CrearProductoUseCase;
use App\Application\Productos\ListarProductosUseCase;
use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(ListarProductosUseCase $listar): View
    {
        $productos = $listar->ejecutar();

        return view('admin.produccion.productos.index', ['productos' => $productos, 'tarifas' => Producto::whereIn('id', $productos->pluck('id'))->get()->keyBy('id')]);
    }

    public function create(): View
    {
        return view('admin.produccion.productos.form', ['producto' => null, 'tarifas' => null, 'recetas' => Receta::where('activo', true)->orderBy('nombre')->get()]);
    }

    public function store(Request $request, CrearProductoUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            DB::transaction(function () use ($crear, $datos, $request): void {
                $producto = $crear->ejecutar(...$datos);
                $this->guardarPrecios($producto->id, $request);
                app(GestionRecetas::class)->vincular($producto->id, $request->filled('receta_id') ? $request->integer('receta_id') : null, (float) ($request->input('cantidad_receta') ?: 1));
            });
        } catch (ProductoInvalidoException $e) {
            return back()->withErrors(['nombre' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Producto creado correctamente.');
    }

    public function edit(int $producto, ProductoRepositoryInterface $productos): View
    {
        $productoDominio = $productos->buscarPorId($producto);

        abort_if($productoDominio === null, 404);

        return view('admin.produccion.productos.form', ['producto' => $productoDominio, 'tarifas' => Producto::findOrFail($producto), 'recetas' => Receta::where('activo', true)->orWhere('id', Producto::findOrFail($producto)->receta_id)->orderBy('nombre')->get()]);
    }

    public function update(int $producto, Request $request, ActualizarProductoUseCase $actualizar): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            DB::transaction(function () use ($actualizar, $producto, $datos, $request): void {
                if ($request->filled('receta_id')) {
                    Receta::whereKey($request->integer('receta_id'))->lockForUpdate()->firstOrFail();
                }
                $registro = Producto::whereKey($producto)->lockForUpdate()->firstOrFail();
                $datos[5] = $registro->litros_por_unidad !== null ? (float) $registro->litros_por_unidad : null;
                $datos[6] = $registro->otros_insumos;
                $actualizar->ejecutar($producto, ...$datos);
                $this->guardarPrecios($producto, $request);
                if ($request->has('receta_id')) {
                    app(GestionRecetas::class)->vincular($producto, $request->filled('receta_id') ? $request->integer('receta_id') : null, (float) ($request->input('cantidad_receta') ?: 1));
                }
            });
        } catch (ProductoInvalidoException $e) {
            return back()->withErrors(['nombre' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Producto actualizado correctamente.');
    }

    public function cambiarEstado(int $producto, Request $request, CambiarEstadoProductoUseCase $cambiar): RedirectResponse
    {
        $request->validate(['activo' => ['required', 'boolean']]);

        $cambiar->ejecutar($producto, $request->boolean('activo'));

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Estado del producto actualizado.');
    }

    public function show(int $producto, ProductoRepositoryInterface $productos): View
    {
        $productoDominio = $productos->buscarPorId($producto);

        abort_if($productoDominio === null, 404);

        return view('admin.produccion.productos.show', ['producto' => $productoDominio, 'tarifas' => Producto::findOrFail($producto), 'recetas' => Receta::where('activo', true)->orWhere('id', Producto::findOrFail($producto)->receta_id)->orderBy('nombre')->get()]);
    }

    /** @return array{0: string, 1: string, 2: string, 3: ?float, 4: ?string, 5: ?float, 6: ?string} */
    private function validarDatos(Request $request): array
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'presentacion' => ['required', 'string', 'max:100'],
            'unidad_produccion' => ['required', 'string', 'max:20'],
            'contenido_por_unidad' => ['nullable', 'numeric', 'gt:0'],
            'unidad_contenido' => ['nullable', 'string', 'max:20'],
            'receta_id' => ['nullable', 'integer', 'exists:recetas,id'],
            'cantidad_receta' => ['nullable', 'numeric', 'min:0.001', 'max:999999', 'decimal:0,3'],
            'litros_por_unidad' => ['prohibited'],
            'otros_insumos' => ['prohibited'],
            'ingredientes' => ['prohibited'],
            'precio_proveedor' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
            'precio_cliente' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
            'precio_mayorista' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
        ]);

        return [
            $request->string('nombre')->toString(),
            $request->string('presentacion')->toString(),
            $request->string('unidad_produccion')->toString(),
            $request->filled('contenido_por_unidad') ? (float) $request->input('contenido_por_unidad') : null,
            $request->string('unidad_contenido')->toString() ?: null,
            null,
            null,
        ];
    }

    private function guardarPrecios(int $productoId, Request $request): void
    {
        Producto::findOrFail($productoId)->update(collect(['precio_proveedor', 'precio_cliente', 'precio_mayorista'])
            ->mapWithKeys(fn ($campo) => [$campo => $request->filled($campo) ? $request->input($campo) : null])->all());
    }
}
