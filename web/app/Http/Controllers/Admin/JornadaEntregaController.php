<?php

namespace App\Http\Controllers\Admin;

use App\Application\Entregas\AnularEntregaUseCase;
use App\Application\Entregas\CorregirEntregaUseCase;
use App\Application\Entregas\RegistrarEntregaUseCase;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** El admin registra entregas en nombre del acopiador (misma lógica de negocio que el flujo de campo). */
class JornadaEntregaController extends Controller
{
    public function store(
        int $jornada,
        Request $request,
        JornadaRepositoryInterface $jornadas,
        RegistrarEntregaUseCase $registrar,
        EntregaRepositoryInterface $entregas,
    ): RedirectResponse {
        $entidad = $jornadas->buscarPorId($jornada);
        abort_if($entidad === null || ! $entidad->estaAbierta(), 404);

        $request->validate([
            'proveedor_id' => ['required', 'integer'],
            'litros' => ['required', 'numeric', 'gt:0'],
            'tachos' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string', 'max:255'],
            'forzar' => ['sometimes', 'boolean'],
        ]);

        if (! $request->boolean('forzar')) {
            $existentes = $entregas->deJornadaYProveedor($entidad->id, $request->integer('proveedor_id'));
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
                jornadaId: $entidad->id,
                proveedorId: $request->integer('proveedor_id'),
                usuarioId: $entidad->usuarioId,
                zonaId: $entidad->zonaId,
                vehiculoId: $entidad->vehiculoId,
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

        return redirect()->route('admin.acopiadores.jornadas.show', $entidad->id)->with('estado', $mensaje);
    }

    public function sumar(Request $request, CorregirEntregaUseCase $corregir, EntregaRepositoryInterface $entregas): RedirectResponse
    {
        $request->validate([
            'entrega_id' => ['required', 'integer'],
            'litros' => ['required', 'numeric', 'gt:0'],
            'tachos' => ['required', 'integer', 'min:1'],
        ]);

        $entregaId = $request->integer('entrega_id');
        $jornadaId = $entregas->buscarPorId($entregaId)?->jornadaId;

        try {
            $corregir->ejecutar(
                entregaId: $entregaId,
                litros: (float) $request->input('litros'),
                tachos: $request->integer('tachos'),
                observaciones: null,
                motivo: 'Sumado a la entrega existente del mismo proveedor en esta jornada.',
                usuarioId: auth('operador')->id(),
            );
        } catch (EntregaInvalidaException|RuntimeException $e) {
            return redirect()->route('admin.acopiadores.index')->withErrors(['litros' => $e->getMessage()]);
        }

        return redirect()->route('admin.acopiadores.jornadas.show', $jornadaId)->with('estado', 'Entrega sumada correctamente.');
    }

    public function anular(Request $request, int $entrega, AnularEntregaUseCase $anular, EntregaRepositoryInterface $entregas): RedirectResponse
    {
        $request->validate(['motivo' => ['required', 'string', 'max:255']]);

        $jornadaId = $entregas->buscarPorId($entrega)?->jornadaId;

        try {
            $anular->ejecutar($entrega, $request->string('motivo')->toString(), auth('operador')->id());
        } catch (EntregaInvalidaException|RuntimeException $e) {
            return redirect()->route('admin.acopiadores.index')->withErrors(['motivo' => $e->getMessage()]);
        }

        return redirect()->route('admin.acopiadores.jornadas.show', $jornadaId)->with('estado', 'Entrega anulada.');
    }
}
