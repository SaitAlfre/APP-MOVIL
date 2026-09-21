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
            'estado' => ['nullable', 'string', 'in:pendiente,registrada'],
        ]);

        $hasta = $request->filled('hasta') ? new DateTimeImmutable($request->string('hasta')->toString()) : new DateTimeImmutable('today');
        $desde = $request->filled('desde') ? new DateTimeImmutable($request->string('desde')->toString()) : $hasta->modify('-'.(self::DIAS_POR_DEFECTO - 1).' days');
        $estado = $request->filled('estado') ? $request->string('estado')->toString() : null;

        $filas = $listar->ejecutar($desde, $hasta, $estado);

        return view('admin.recepcion.index', [
            'filas' => $filas,
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => $estado,
            'pendientes' => count(array_filter($filas, fn (array $fila) => $fila['recepcion'] === null)),
        ]);
    }

    public function registrarLlegada(int $jornada, Request $request, RegistrarLlegadaUseCase $registrar): RedirectResponse
    {
        $datos = $request->validate([
            'llegada_en' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'litros_medidos' => ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,2'],
            'motivo_diferencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:500'],
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

        return redirect()->route('admin.recepcion.index')->with('estado', 'Llegada registrada. Calidad puede evaluarla de forma independiente.');
    }
}
