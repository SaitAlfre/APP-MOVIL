<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Productos\ActualizarProductoUseCase;
use App\Application\Productos\CambiarEstadoProductoUseCase;
use App\Application\Productos\CrearProductoUseCase;
use App\Application\Productos\ListarProductosUseCase;
use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(ListarProductosUseCase $listar): View
    {
        return view('admin.produccion.productos.index', ['productos' => $listar->ejecutar()]);
    }

    public function create(): View
    {
        return view('admin.produccion.productos.form', ['producto' => null]);
    }

    public function store(Request $request, CrearProductoUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            $crear->ejecutar(...$datos);
        } catch (ProductoInvalidoException $e) {
            return back()->withErrors(['nombre' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Receta creada correctamente.');
    }

    public function edit(int $producto, ProductoRepositoryInterface $productos): View
    {
        $productoDominio = $productos->buscarPorId($producto);

        abort_if($productoDominio === null, 404);

        return view('admin.produccion.productos.form', ['producto' => $productoDominio]);
    }

    public function update(int $producto, Request $request, ActualizarProductoUseCase $actualizar): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            $actualizar->ejecutar($producto, ...$datos);
        } catch (ProductoInvalidoException $e) {
            return back()->withErrors(['nombre' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Receta actualizada correctamente.');
    }

    public function cambiarEstado(int $producto, Request $request, CambiarEstadoProductoUseCase $cambiar): RedirectResponse
    {
        $request->validate(['activo' => ['required', 'boolean']]);

        $cambiar->ejecutar($producto, $request->boolean('activo'));

        return redirect()->route('admin.produccion.productos.index')->with('estado', 'Estado de la receta actualizado.');
    }

    public function show(int $producto, ProductoRepositoryInterface $productos): View
    {
        $productoDominio = $productos->buscarPorId($producto);

        abort_if($productoDominio === null, 404);

        return view('admin.produccion.productos.show', ['producto' => $productoDominio]);
    }

    /** @return array{0: string, 1: string, 2: string, 3: ?float, 4: ?string, 5: float, 6: ?string} */
    private function validarDatos(Request $request): array
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'presentacion' => ['required', 'string', 'max:100'],
            'unidad_produccion' => ['required', 'string', 'max:20'],
            'contenido_por_unidad' => ['nullable', 'numeric', 'gt:0'],
            'unidad_contenido' => ['nullable', 'string', 'max:20'],
            'litros_por_unidad' => ['required', 'numeric', 'gt:0'],
            'otros_insumos' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            $request->string('nombre')->toString(),
            $request->string('presentacion')->toString(),
            $request->string('unidad_produccion')->toString(),
            $request->filled('contenido_por_unidad') ? (float) $request->input('contenido_por_unidad') : null,
            $request->string('unidad_contenido')->toString() ?: null,
            (float) $request->input('litros_por_unidad'),
            $request->string('otros_insumos')->toString() ?: null,
        ];
    }
}
