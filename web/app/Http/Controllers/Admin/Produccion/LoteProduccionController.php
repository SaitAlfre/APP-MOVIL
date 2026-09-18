<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\CancelarLoteProduccionUseCase;
use App\Application\Produccion\CrearLoteProduccionUseCase;
use App\Application\Produccion\FinalizarLoteProduccionUseCase;
use App\Application\Produccion\IniciarLoteProduccionUseCase;
use App\Application\Produccion\ListarLotesProduccionUseCase;
use App\Application\Produccion\PreviewNecesidadesLoteUseCase;
use App\Application\Produccion\RegistrarConsumoLoteUseCase;
use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Produccion\EstadoLoteProduccion;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Domain\Recetas\RecetaRepositoryInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class LoteProduccionController extends Controller
{
    public function index(Request $request, ListarLotesProduccionUseCase $listar, ProductoRepositoryInterface $productos, UsuarioRepositoryInterface $usuarios): View
    {
        $filtro = $request->string('estado')->toString();
        $estado = $filtro !== '' ? EstadoLoteProduccion::tryFrom($filtro) : null;
        $productoId = $request->integer('producto_id') ?: null;

        $lotes = $listar->ejecutar(20, $estado, $productoId);

        $filas = collect($lotes->items())->map(fn ($lote) => [
            'lote' => $lote,
            'producto' => $productos->buscarPorId($lote->productoId),
            'responsable' => $usuarios->buscarPorId($lote->responsableUsuarioId),
        ]);

        return view('admin.produccion.lotes.index', ['filas' => $filas, 'paginador' => $lotes, 'filtroActual' => $estado]);
    }

    public function create(Request $request, ProductoRepositoryInterface $productos, PreviewNecesidadesLoteUseCase $preview): View
    {
        $productoId = $request->integer('producto_id') ?: null;
        $cantidad = $request->filled('cantidad_planificada') ? (float) $request->input('cantidad_planificada') : null;

        $previsualizacion = null;
        $errorPreview = null;

        if ($productoId !== null && $cantidad !== null && $cantidad > 0) {
            try {
                $previsualizacion = $preview->ejecutar($productoId, $cantidad);
            } catch (ProductoInvalidoException|LoteProduccionInvalidoException|InsumoInvalidoException $e) {
                $errorPreview = $e->getMessage();
            }
        }

        return view('admin.produccion.lotes.create', [
            'productos' => $productos->todosActivos(),
            'productoSeleccionadoId' => $productoId,
            'cantidadPlanificada' => $cantidad,
            'previsualizacion' => $previsualizacion,
            'errorPreview' => $errorPreview,
        ]);
    }

    public function store(Request $request, CrearLoteProduccionUseCase $crear): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
            'producto_id' => ['required', 'integer'],
            'cantidad_planificada' => ['required', 'numeric', 'gt:0'],
            'fecha_planificada' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $lote = $crear->ejecutar(
                codigo: $request->string('codigo')->toString(),
                productoId: $request->integer('producto_id'),
                cantidadPlanificada: (float) $request->input('cantidad_planificada'),
                fechaPlanificada: new DateTimeImmutable($request->string('fecha_planificada')->toString()),
                observaciones: $request->string('observaciones')->toString() ?: null,
                responsableUsuarioId: auth('operador')->id(),
            );
        } catch (ProductoInvalidoException|LoteProduccionInvalidoException|InsumoInvalidoException $e) {
            return back()->withErrors(['codigo' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.produccion.lotes.show', $lote->id)->with('estado', 'Lote creado como borrador.');
    }

    public function show(
        int $lote,
        LoteProduccionRepositoryInterface $lotes,
        ProductoRepositoryInterface $productos,
        RecetaRepositoryInterface $recetas,
        UsuarioRepositoryInterface $usuarios,
        InsumoRepositoryInterface $insumos,
    ): View {
        $loteDominio = $lotes->buscarPorId($lote);

        abort_if($loteDominio === null, 404);

        $insumosDelLote = collect($lotes->insumosDelLote($lote))->map(fn ($li) => [
            'detalle' => $li,
            'insumo' => $insumos->buscarPorId($li->insumoId),
        ]);

        return view('admin.produccion.lotes.show', [
            'lote' => $loteDominio,
            'producto' => $productos->buscarPorId($loteDominio->productoId),
            'receta' => $recetas->buscarPorId($loteDominio->recetaId),
            'responsable' => $usuarios->buscarPorId($loteDominio->responsableUsuarioId),
            'insumos' => $insumosDelLote,
        ]);
    }

    public function iniciar(int $lote, IniciarLoteProduccionUseCase $iniciar): RedirectResponse
    {
        try {
            $iniciar->ejecutar($lote);
        } catch (LoteProduccionInvalidoException|InsumoInvalidoException|RuntimeException $e) {
            return back()->withErrors(['lote' => $e->getMessage()]);
        }

        return redirect()->route('admin.produccion.lotes.show', $lote)->with('estado', 'Lote iniciado: materiales reservados.');
    }

    public function registrarConsumo(int $lote, Request $request, RegistrarConsumoLoteUseCase $registrar): RedirectResponse
    {
        $consumos = $this->consumosDesdeRequest($request);

        try {
            $registrar->ejecutar($lote, $consumos, auth('operador')->id());
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['consumo' => $e->getMessage()]);
        }

        return redirect()->route('admin.produccion.lotes.show', $lote)->with('estado', 'Consumo registrado correctamente.');
    }

    public function finalizar(int $lote, Request $request, FinalizarLoteProduccionUseCase $finalizar): RedirectResponse
    {
        $request->validate(['cantidad_obtenida' => ['required', 'numeric', 'gte:0']]);

        $consumos = $this->consumosDesdeRequest($request);

        try {
            $finalizar->ejecutar($lote, (float) $request->input('cantidad_obtenida'), $consumos !== [] ? $consumos : null, auth('operador')->id());
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['cantidad_obtenida' => $e->getMessage()]);
        }

        return redirect()->route('admin.produccion.lotes.show', $lote)->with('estado', 'Lote finalizado: producción ingresada al inventario.');
    }

    public function cancelar(int $lote, Request $request, CancelarLoteProduccionUseCase $cancelar): RedirectResponse
    {
        $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        try {
            $cancelar->ejecutar($lote, $request->string('motivo')->toString(), auth('operador')->id());
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect()->route('admin.produccion.lotes.show', $lote)->with('estado', 'Lote cancelado.');
    }

    /** @return array<int, float> */
    private function consumosDesdeRequest(Request $request): array
    {
        $consumos = [];

        foreach ((array) $request->input('consumo', []) as $insumoId => $cantidad) {
            if ($cantidad === null || $cantidad === '') {
                continue;
            }

            $consumos[(int) $insumoId] = (float) $cantidad;
        }

        return $consumos;
    }
}
