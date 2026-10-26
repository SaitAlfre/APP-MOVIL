<?php

namespace App\Http\Controllers\Admin;

use App\Application\Entregas\AnularEntregaUseCase;
use App\Application\Entregas\CorregirEntregaUseCase;
use App\Application\Entregas\RegistrarEntregaUseCase;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            'litros' => ['required', 'numeric', 'gt:0', 'max:18500'],

            'tachos' => ['sometimes', 'integer', 'min:1'],

            'observaciones' => ['nullable', 'string', 'max:255'],

            'forzar' => ['sometimes', 'boolean'],

        ]);

        if (! Proveedor::query()->whereKey($request->integer('proveedor_id'))->where('zona_id', $entidad->zonaId)->where('estado', 'activo')->exists()) {

            return back()->withErrors(['proveedor_id' => 'Selecciona un proveedor activo de la zona de esta jornada.'])->withInput();

        }

        if (! $request->boolean('forzar')) {

            $existentes = $entregas->deJornadaYProveedor($entidad->id, $request->integer('proveedor_id'));

            if ($existentes !== []) {

                return back()->withInput()->with('duplicado', [

                    'entrega_id' => $existentes[0]->id,

                    'litros_existentes' => $existentes[0]->litros,

                    'tachos_existentes' => $existentes[0]->tachos,

                    'litros_nuevos' => (float) $request->input('litros'),

                    'tachos_nuevos' => $request->integer('tachos', 1),

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

                tachos: $request->integer('tachos', 1),

                observaciones: $request->string('observaciones')->toString() ?: null,

            );

        } catch (EntregaInvalidaException|RuntimeException $e) {

            return back()->withErrors(['litros' => $this->mensajeSeguro($e)])->withInput();

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

            'litros' => ['required', 'numeric', 'gt:0', 'max:18500'],

            'tachos' => ['sometimes', 'integer', 'min:1'],

        ]);

        $entregaId = $request->integer('entrega_id');

        $existente = $entregas->buscarPorId($entregaId);
        $jornadaId = $existente?->jornadaId;

        try {

            $corregir->ejecutar(

                entregaId: $entregaId,

                litros: (float) $request->input('litros'),

                tachos: $request->integer('tachos', $existente?->tachos ?? 1),

                observaciones: null,

                motivo: 'Sumado a la entrega existente del mismo proveedor en esta jornada.',

                usuarioId: auth('operador')->id(),

            );

        } catch (EntregaInvalidaException|RuntimeException $e) {

            return redirect()->route('admin.acopiadores.index')->withErrors(['litros' => $this->mensajeSeguro($e)]);

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

            return redirect()->route('admin.acopiadores.index')->withErrors(['motivo' => $this->mensajeSeguro($e)]);

        }

        return redirect()->route('admin.acopiadores.jornadas.show', $jornadaId)->with('estado', 'Entrega anulada.');

    }

    public function corregir(Request $request, int $entrega, CorregirEntregaUseCase $corregir): RedirectResponse
    {

        $datos = $request->validate(['litros' => ['required', 'numeric', 'gt:0', 'max:18500'],

            'observaciones' => ['nullable', 'string', 'max:255'], 'motivo' => ['required', 'string', 'min:5', 'max:255'],

            'revision' => ['required', 'string', 'size:64']]);

        return DB::transaction(function () use ($entrega, $datos, $corregir): RedirectResponse {

            $registro = Entrega::query()->lockForUpdate()->findOrFail($entrega);

            if ($registro->anulada || ! hash_equals($registro->revisionMovil(), $datos['revision'])) {

                return back()->withErrors(['litros' => 'El recojo cambió. Actualiza la página antes de corregirlo.']);

            }

            $corregir->ejecutar($registro->id, (float) $datos['litros'], $registro->tachos, $datos['observaciones'] ?? null, $datos['motivo'], auth('operador')->id());

            return redirect()->route('admin.acopiadores.jornadas.show', $registro->jornada_id)->with('estado', 'Recojo corregido. Se descargará en el móvil al sincronizar.');

        });

    }

    public function deshacerMarca(Request $request, int $marca): RedirectResponse
    {

        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);

        return DB::transaction(function () use ($marca, $datos): RedirectResponse {

            $registro = SinRecojo::query()->lockForUpdate()->findOrFail($marca);

            if (! $registro->deshecha) {

                $registro->update(['deshecha' => true, 'deshecha_en' => now(), 'actualizado_en' => max((int) now()->getTimestampMs(), $registro->actualizado_en + 1)]);

                Auditoria::query()->create(['entidad' => 'sin_recojo', 'entidad_id' => $registro->id, 'accion' => 'deshacer',

                    'motivo' => $datos['motivo'], 'usuario_id' => auth('operador')->id(), 'ocurrido_en' => now(), 'valor_antes' => 'deshecha=false', 'valor_despues' => 'deshecha=true']);

            }

            return redirect()->route('admin.acopiadores.jornadas.show', $registro->jornada_id)->with('estado', 'Marca revisada. El móvil recibirá el cambio al sincronizar.');

        });

    }
}
