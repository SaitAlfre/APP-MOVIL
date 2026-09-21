<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\CancelarLoteProduccionUseCase;
use App\Application\Produccion\CrearLoteProduccionUseCase;
use App\Application\Produccion\FinalizarLoteProduccionUseCase;
use App\Application\Produccion\IniciarLoteProduccionUseCase;
use App\Application\Produccion\ListarDiasConSaldoDisponibleUseCase;
use App\Application\Produccion\ObtenerSaldoProduccionUseCase;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProduccionController extends Controller
{
    public function index(
        Request $request,
        ListarDiasConSaldoDisponibleUseCase $listarDias,
        ProductoRepositoryInterface $productos,
        ObtenerSaldoProduccionUseCase $obtenerSaldo,
    ): View {
        $dias = $listarDias->ejecutar();

        $fecha = $request->filled('fecha') ? new DateTimeImmutable($request->string('fecha')->toString()) : null;
        $productoId = $request->integer('producto_id') ?: null;
        $litrosAsignados = $request->filled('litros_asignados') ? (float) $request->input('litros_asignados') : null;

        $producto = $productoId !== null ? $productos->buscarPorId($productoId) : null;
        $saldo = $fecha !== null ? $obtenerSaldo->ejecutar($fecha) : null;
        $unidadesEstimadas = ($producto !== null && $litrosAsignados !== null && $litrosAsignados > 0)
            ? (int) floor($litrosAsignados / $producto->litrosPorUnidad)
            : null;

        return view('admin.produccion.produccion.index', [
            'dias' => $dias,
            'productos' => $productos->todosActivos(),
            'fechaSeleccionada' => $fecha,
            'productoSeleccionadoId' => $productoId,
            'litrosAsignadosSeleccionados' => $litrosAsignados,
            'saldo' => $saldo,
            'unidadesEstimadas' => $unidadesEstimadas,
        ]);
    }

    public function store(Request $request, CrearLoteProduccionUseCase $crear): RedirectResponse
    {
        $request->validate([
            'fecha' => ['required', 'date'],
            'producto_id' => ['required', 'integer'],
            'litros_asignados' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $lote = $crear->ejecutar(
                fecha: new DateTimeImmutable($request->string('fecha')->toString()),
                productoId: $request->integer('producto_id'),
                litrosAsignados: (float) $request->input('litros_asignados'),
                responsableId: auth('operador')->id(),
            );
        } catch (LoteProduccionInvalidoException $e) {
            return back()->withErrors(['litros_asignados' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.produccion.historial.index')
            ->with('estado', "Lote {$lote->codigo} creado en borrador: {$lote->unidadesEstimadas} unidades estimadas.");
    }

    public function iniciar(int $lote, IniciarLoteProduccionUseCase $iniciar): RedirectResponse
    {
        try {
            $iniciar->ejecutar($lote, (int) auth('operador')->id());
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['estado' => $this->mensajeSeguro($e)]);
        }

        return back()->with('estado', 'Lote iniciado.');
    }

    public function finalizar(int $lote, Request $request, FinalizarLoteProduccionUseCase $finalizar): RedirectResponse
    {
        $request->validate([
            'litros_usados' => ['required', 'numeric', 'min:0'],
            'litros_merma_proceso' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $resultado = $finalizar->ejecutar(
                loteId: $lote,
                litrosUsados: (float) $request->input('litros_usados'),
                litrosMermaProceso: (float) ($request->input('litros_merma_proceso') ?: 0),
                usuarioId: auth('operador')->id(),
            );
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['litros_usados' => $this->mensajeSeguro($e)]);
        }

        return back()->with('estado', "Lote finalizado: {$resultado->unidadesProducidas} unidades producidas.");
    }

    public function cancelar(int $lote, Request $request, CancelarLoteProduccionUseCase $cancelar): RedirectResponse
    {
        $request->validate(['motivo' => ['required', 'string', 'max:255']]);

        try {
            $cancelar->ejecutar($lote, $request->string('motivo')->toString(), auth('operador')->id());
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['motivo' => $this->mensajeSeguro($e)]);
        }

        return back()->with('estado', 'Lote cancelado.');
    }
}
