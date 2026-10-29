<?php

namespace App\Http\Controllers\Admin;

use App\Application\Recepcion\ListarJornadasParaRecepcionUseCase;
use App\Application\Recepcion\RegistrarLlegadaUseCase;
use App\Domain\Recepcion\Exceptions\RecepcionInvalidaException;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RecepcionController extends Controller
{
    private const int DIAS_POR_DEFECTO = 14;

    public function index(Request $request, ListarJornadasParaRecepcionUseCase $listar): View
    {
        $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', 'string', 'in:pendiente,registrada,diferencia,exacta'],
            'buscar' => ['nullable', 'string', 'max:120'],
            'periodo' => ['nullable', 'in:hoy,ayer,semana'],
        ]);

        $hoyLima = new DateTimeImmutable('today', new \DateTimeZone('America/Lima'));
        $periodo = $request->filled('periodo') ? $request->string('periodo')->toString() : null;
        [$desde, $hasta] = match ($periodo) {
            'hoy' => [$hoyLima, $hoyLima],
            'ayer' => [$hoyLima->modify('-1 day'), $hoyLima->modify('-1 day')],
            'semana' => [$hoyLima->modify('monday this week'), $hoyLima],
            default => [null, null],
        };
        $hasta ??= $request->filled('hasta') ? new DateTimeImmutable($request->string('hasta')->toString()) : new DateTimeImmutable('today');
        $desde ??= $request->filled('desde') ? new DateTimeImmutable($request->string('desde')->toString()) : $hasta->modify('-'.(self::DIAS_POR_DEFECTO - 1).' days');
        $estado = $request->filled('estado') ? $request->string('estado')->toString() : null;
        $buscar = mb_strtolower(trim($request->string('buscar')->toString()));

        $todas = array_values(array_filter($listar->ejecutar($desde, $hasta),
            fn (array $fila) => $buscar === '' || str_contains(mb_strtolower($fila['acopiador'].' '.$fila['zona'].' '.$fila['placa']), $buscar)));
        $estadoDe = fn (array $fila) => match (true) {
            $fila['recepcion'] === null => 'pendiente',
            $fila['recepcion']->diferencia() !== 0.0 => 'diferencia',
            default => 'exacta',
        };
        $filas = array_values(array_filter($todas, fn (array $fila) => $estado === null
            || ($estado === 'registrada' ? $fila['recepcion'] !== null : $estadoDe($fila) === $estado)));
        $recibidas = array_filter($todas, fn (array $fila) => $fila['recepcion'] !== null);
        $hoy = (new DateTimeImmutable('now', new \DateTimeZone('America/Lima')))->format('Y-m-d');

        return view('admin.recepcion.index', [
            'filas' => $filas,
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => $estado,
            'periodo' => $periodo,
            'conteos' => [
                '' => count($todas),
                'pendiente' => count(array_filter($todas, fn (array $fila) => $estadoDe($fila) === 'pendiente')),
                'diferencia' => count(array_filter($todas, fn (array $fila) => $estadoDe($fila) === 'diferencia')),
                'exacta' => count(array_filter($todas, fn (array $fila) => $estadoDe($fila) === 'exacta')),
            ],
            'estadoDe' => $estadoDe,
            'pendientes' => count(array_filter($todas, fn (array $fila) => $fila['recepcion'] === null)),
            'mermaPorZona' => collect($recibidas)->groupBy('zona')
                ->map(fn ($grupo, $zona) => ['label' => $zona, 'value' => round($grupo->sum(fn (array $fila) => max(0.0, -$fila['recepcion']->diferencia())), 1), 'viajes' => $grupo->count()])
                ->sortByDesc('value')->values()->all(),
            'deHoy' => collect($todas)->filter(fn (array $fila) => $fila['fecha']->format('Y-m-d') === $hoy)
                ->sortBy(fn (array $fila) => $fila['recepcion']?->llegadaEn->getTimestamp() ?? PHP_INT_MAX)->values()->all(),
        ]);
    }

    public function registrarLlegada(int $jornada, Request $request, RegistrarLlegadaUseCase $registrar): RedirectResponse
    {
        $datos = $request->validate([
            'llegada_en' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'litros_medidos' => ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,1'],
            'motivo_diferencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'volver' => ['nullable', 'in:jornada'],
        ]);

        try {
            $registrar->ejecutar(
                jornadaId: $jornada,
                llegadaEn: DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $datos['llegada_en']),
                litrosMedidos: (float) $datos['litros_medidos'],
                motivoDiferencia: $datos['motivo_diferencia'] ?? null,
                observaciones: $datos['observaciones'] ?? null,
                usuarioId: auth('operador')->id(),
            );
        } catch (RecepcionInvalidaException|RuntimeException $e) {
            return back()->withErrors(['litros_medidos' => $this->mensajeSeguro($e)])->withInput();
        }

        $destino = ($datos['volver'] ?? null) === 'jornada'
            ? redirect()->route('admin.acopiadores.jornadas.show', $jornada)
            : redirect()->route('admin.recepcion.index');

        return $destino->with('estado', 'Llegada registrada. Calidad puede evaluarla de forma independiente.');
    }
}
