<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Recetas\ActivarRecetaUseCase;
use App\Application\Recetas\CrearRecetaUseCase;
use App\Application\Recetas\EditarRecetaUseCase;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Domain\Recetas\Exceptions\RecetaInvalidaException;
use App\Domain\Recetas\RecetaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RecetaController extends Controller
{
    public function index(Request $request, ProductoRepositoryInterface $productos, RecetaRepositoryInterface $recetas): View
    {
        $productoId = $request->integer('producto_id') ?: null;
        $productoSeleccionado = $productoId !== null ? $productos->buscarPorId($productoId) : null;

        return view('admin.produccion.recetas.index', [
            'productos' => $productos->paginar(200)->items(),
            'productoSeleccionado' => $productoSeleccionado,
            'recetas' => $productoSeleccionado !== null ? $recetas->listarPorProducto($productoId) : [],
        ]);
    }

    public function create(Request $request, ProductoRepositoryInterface $productos, InsumoRepositoryInterface $insumos): View
    {
        $productoId = $request->integer('producto_id') ?: null;

        return view('admin.produccion.recetas.form', [
            'receta' => null,
            'productos' => $productos->todosActivos(),
            'productoSeleccionadoId' => $productoId,
            'insumos' => $insumos->todosActivos(),
        ]);
    }

    public function store(Request $request, CrearRecetaUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            $receta = $crear->ejecutar(
                productoId: (int) $request->input('producto_id'),
                nombre: $datos['nombre'],
                rendimientoBase: $datos['rendimiento_base'],
                rendimientoUnidad: $datos['rendimiento_unidad'],
                observaciones: $datos['observaciones'],
                ingredientesInput: $datos['ingredientes'],
                usuarioId: auth('operador')->id(),
            );
        } catch (RecetaInvalidaException|RuntimeException $e) {
            return back()->withErrors(['nombre' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.produccion.recetas.index', ['producto_id' => $receta->productoId])
            ->with('estado', 'Receta creada como borrador. Actívala cuando esté lista.');
    }

    public function edit(int $receta, RecetaRepositoryInterface $recetas, ProductoRepositoryInterface $productos, InsumoRepositoryInterface $insumos): View
    {
        $recetaDominio = $recetas->buscarPorId($receta);

        abort_if($recetaDominio === null, 404);

        return view('admin.produccion.recetas.form', [
            'receta' => $recetaDominio,
            'productos' => $productos->todosActivos(),
            'productoSeleccionadoId' => $recetaDominio->productoId,
            'insumos' => $insumos->todosActivos(),
        ]);
    }

    public function update(int $receta, Request $request, EditarRecetaUseCase $editar): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        try {
            $recetaActualizada = $editar->ejecutar(
                recetaId: $receta,
                nombre: $datos['nombre'],
                rendimientoBase: $datos['rendimiento_base'],
                rendimientoUnidad: $datos['rendimiento_unidad'],
                observaciones: $datos['observaciones'],
                ingredientesInput: $datos['ingredientes'],
                usuarioId: auth('operador')->id(),
            );
        } catch (RecetaInvalidaException|RuntimeException $e) {
            return back()->withErrors(['nombre' => $e->getMessage()])->withInput();
        }

        $mensaje = $recetaActualizada->id !== $receta
            ? "Se creó la versión {$recetaActualizada->version} de la receta (la anterior no se modifica retroactivamente)."
            : 'Receta actualizada correctamente.';

        return redirect()->route('admin.produccion.recetas.index', ['producto_id' => $recetaActualizada->productoId])->with('estado', $mensaje);
    }

    public function activar(int $receta, ActivarRecetaUseCase $activar): RedirectResponse
    {
        try {
            $recetaActivada = $activar->ejecutar($receta);
        } catch (RecetaInvalidaException|LoteProduccionInvalidoException $e) {
            return back()->withErrors(['receta' => $e->getMessage()]);
        }

        return redirect()->route('admin.produccion.recetas.index', ['producto_id' => $recetaActivada->productoId])
            ->with('estado', 'Receta activada. Es la única versión activa de este producto.');
    }

    /** @return array{nombre: string, rendimiento_base: float, rendimiento_unidad: string, observaciones: ?string, ingredientes: list<array{insumo_id: int, cantidad: float, unidad: string}>} */
    private function validarDatos(Request $request): array
    {
        // El formulario renderiza filas de ingrediente fijas (sin JS); las que quedaron
        // sin insumo seleccionado se descartan antes de validar.
        $ingredientesFiltrados = collect($request->input('ingredientes', []))
            ->filter(fn ($fila) => filled($fila['insumo_id'] ?? null) && filled($fila['cantidad'] ?? null))
            ->values()
            ->all();
        $request->merge(['ingredientes' => $ingredientesFiltrados]);

        $validado = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'rendimiento_base' => ['required', 'numeric', 'gt:0'],
            'rendimiento_unidad' => ['required', 'string', 'max:20'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'ingredientes' => ['required', 'array', 'min:1'],
            'ingredientes.*.insumo_id' => ['required', 'integer'],
            'ingredientes.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'ingredientes.*.unidad' => ['required', 'string', 'max:20'],
        ]);

        return [
            'nombre' => $validado['nombre'],
            'rendimiento_base' => (float) $validado['rendimiento_base'],
            'rendimiento_unidad' => $validado['rendimiento_unidad'],
            'observaciones' => $validado['observaciones'] ?? null,
            'ingredientes' => array_map(fn ($i) => [
                'insumo_id' => (int) $i['insumo_id'],
                'cantidad' => (float) $i['cantidad'],
                'unidad' => $i['unidad'],
            ], array_values($validado['ingredientes'])),
        ];
    }
}
