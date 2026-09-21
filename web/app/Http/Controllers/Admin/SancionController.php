<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SancionController extends Controller
{
    public function index(): View
    {
        $this->crearPropuestasDesdeCalidad();

        return view('admin.sanciones.index', [
            'sanciones' => Sancion::with(['proveedor', 'controlCalidad'])->latest()->paginate(15, ['*'], 'sanciones'),
            'reclamos' => ReclamoProveedor::with(['proveedor', 'entrega'])->latest()->paginate(15, ['*'], 'reclamos'),
            'entregas' => Entrega::with('proveedor')->where('anulada', false)->latest('registrado_en')->limit(100)->get(),
        ]);
    }

    public function resolver(Request $request, Sancion $sancion): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['aprobada', 'rechazada'])]]);
        abort_if($sancion->estado !== 'pendiente', 409, 'La sanción ya fue resuelta.');
        $sancion->update(['estado' => $datos['estado'], 'resuelto_por' => auth('operador')->id(), 'resuelto_en' => now()]);

        return back()->with('estado', 'Propuesta de sanción '.$datos['estado'].'.');
    }

    public function storeReclamo(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'entrega_id' => ['required', 'integer', 'exists:entregas,id'],
            'litros_solicitados' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);
        $entrega = Entrega::findOrFail($datos['entrega_id']);
        ReclamoProveedor::create($datos + ['proveedor_id' => $entrega->proveedor_id, 'litros_originales' => $entrega->litros]);

        return back()->with('estado', 'Reclamo registrado correctamente.');
    }

    public function resolverReclamo(Request $request, ReclamoProveedor $reclamo): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['resuelto', 'rechazado'])],
            'respuesta' => ['required', 'string', 'max:500'],
        ]);
        abort_if($reclamo->estado !== 'pendiente', 409, 'El reclamo ya fue resuelto.');

        DB::transaction(function () use ($datos, $reclamo): void {
            if ($datos['estado'] === 'resuelto') {
                $entrega = Entrega::with('proveedor')->lockForUpdate()->findOrFail($reclamo->entrega_id);
                $capacidad = (float) $entrega->proveedor->capacidad_tacho_l * $entrega->proveedor->tachos;
                abort_if((float) $reclamo->litros_solicitados > $capacidad, 422, 'Los litros solicitados superan la capacidad registrada.');
                $entrega->update(['litros' => $reclamo->litros_solicitados, 'observaciones' => trim(($entrega->observaciones ? $entrega->observaciones.' ' : '').'Ajustada por reclamo #'.$reclamo->id.'.')]);
            }
            $reclamo->update($datos + ['resuelto_por' => auth('operador')->id(), 'resuelto_en' => now()]);
        });

        return back()->with('estado', 'Reclamo actualizado.');
    }

    private function crearPropuestasDesdeCalidad(): void
    {
        ControlCalidad::query()->with('entrega')->whereIn('resultado', ['observado', 'rechazado'])->get()
            ->each(function (ControlCalidad $control): void {
                if ($control->entrega === null) {
                    return;
                }
                $resultado = $control->resultado->value;
                Sancion::firstOrCreate(
                    ['control_calidad_id' => $control->id],
                    [
                        'proveedor_id' => $control->entrega->proveedor_id,
                        'severidad' => $resultado === 'rechazado' ? 'alta' : 'media',
                        'descuento' => $resultado === 'rechazado' ? 30.50 : 15,
                        'motivo' => $control->observaciones ?: 'Resultado de calidad '.$resultado,
                    ],
                );
            });
    }
}
