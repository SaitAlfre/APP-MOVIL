<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Entregas\AnularEntregaUseCase;
use App\Application\Entregas\CorregirEntregaUseCase;
use App\Application\Entregas\RegistrarEntregaUseCase;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use App\Domain\Jornadas\Jornada;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Acopiador\StoreEntregaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class EntregaController extends Controller
{
    public function create(Request $request, ProveedorRepositoryInterface $proveedores): View
    {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');

        return view('acopiador.entregas.create', [
            'proveedores' => $proveedores->activosPorZona($jornada->zonaId),
            'proveedorPreseleccionado' => $request->query('proveedor_id'),
        ]);
    }

    public function store(
        StoreEntregaRequest $request,
        RegistrarEntregaUseCase $registrar,
        EntregaRepositoryInterface $entregas,
    ): RedirectResponse {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');
        $usuarioId = auth('operador')->id();

        if (! $request->boolean('forzar')) {
            $existentes = $entregas->deJornadaYProveedor($jornada->id, $request->integer('proveedor_id'));
            if ($existentes !== []) {
                return back()->withInput()->with('duplicado', [
                    'entrega_id' => $existentes[0]->id,
                    'litros_existentes' => $existentes[0]->litros,
                    'tachos_existentes' => $existentes[0]->tachos,
                    'litros_nuevos' => (float) $request->input('litros'),
                    'tachos_nuevos' => (int) $request->input('tachos'),
                ]);
            }
        }

        try {
            $resultado = $registrar->ejecutar(
                jornadaId: $jornada->id,
                proveedorId: $request->integer('proveedor_id'),
                usuarioId: $usuarioId,
                zonaId: $jornada->zonaId,
                vehiculoId: $jornada->vehiculoId,
                litros: (float) $request->input('litros'),
                tachos: $request->integer('tachos'),
                observaciones: $request->string('observaciones')->toString() ?: null,
            );
        } catch (EntregaInvalidaException|RuntimeException $e) {
            return back()->withErrors(['litros' => $e->getMessage()])->withInput();
        }

        $mensaje = 'Entrega registrada correctamente.';
        if ($resultado->advertenciaDesviacion) {
            $mensaje .= ' Atención: la cantidad se desvía más del 40% del promedio reciente de este proveedor.';
        }

        return redirect()->route('acopiador.home')->with('estado', $mensaje);
    }

    public function sumar(Request $request, CorregirEntregaUseCase $corregir): RedirectResponse
    {
        $request->validate([
            'entrega_id' => ['required', 'integer'],
            'litros' => ['required', 'numeric', 'gt:0'],
            'tachos' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $corregir->ejecutar(
                entregaId: $request->integer('entrega_id'),
                litros: (float) $request->input('litros'),
                tachos: $request->integer('tachos'),
                observaciones: null,
                motivo: 'Sumado a la entrega existente del mismo proveedor en esta jornada.',
                usuarioId: auth('operador')->id(),
            );
        } catch (EntregaInvalidaException|RuntimeException $e) {
            return redirect()->route('acopiador.home')->withErrors(['litros' => $e->getMessage()]);
        }

        return redirect()->route('acopiador.home')->with('estado', 'Entrega sumada correctamente.');
    }

    public function anular(Request $request, int $entrega, AnularEntregaUseCase $anular): RedirectResponse
    {
        $request->validate(['motivo' => ['required', 'string', 'max:255']]);

        try {
            $anular->ejecutar($entrega, $request->string('motivo')->toString(), auth('operador')->id());
        } catch (EntregaInvalidaException|RuntimeException $e) {
            return redirect()->route('acopiador.home')->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect()->route('acopiador.home')->with('estado', 'Entrega anulada.');
    }
}
