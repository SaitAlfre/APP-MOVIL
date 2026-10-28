<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TarifaLecheController extends Controller
{
    public function index(): View
    {
        return view('admin.liquidaciones.tarifas', ['tarifas' => TarifaLeche::orderByDesc('desde')->get(), 'hoy' => today('America/Lima')->toDateString(), 'actual' => TarifaLeche::vigenteEn()]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->guardar($request);
    }

    public function update(Request $request, int $tarifa): RedirectResponse
    {
        return $this->guardar($request, $tarifa);
    }

    public function cambiarActual(Request $request): RedirectResponse
    {
        $hoy = today('America/Lima')->toDateString();
        $datos = $request->validate(['precio' => ['required', 'numeric', 'gt:0', 'max:9999999', 'decimal:0,3'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$hoy]]);
        DB::transaction(function () use ($datos, $hoy): void {
            $version = Configuracion::whereKey('tarifas_version')->lockForUpdate()->firstOrFail();
            $actual = TarifaLeche::vigenteEn($hoy);
            if (Liquidacion::whereDate('periodo_inicio', '<=', $datos['hasta'])->whereDate('periodo_fin', '>=', $hoy)->exists()) {
                throw ValidationException::withMessages(['precio' => 'El periodo incluye liquidaciones ya generadas. Programa el cambio después de ese periodo.']);
            }
            if (TarifaLeche::when($actual !== null, fn ($q) => $q->whereKeyNot($actual->id))
                ->whereDate('desde', '<=', $datos['hasta'])->whereDate('hasta', '>=', $hoy)->exists()) {
                throw ValidationException::withMessages(['hasta' => 'El periodo se cruza con otro precio programado. Ajusta su fecha de fin.']);
            }
            if ($actual !== null && $datos['hasta'] < $actual->hasta->toDateString()) {
                throw ValidationException::withMessages(['hasta' => 'Conserva al menos el fin de la temporada actual para no dejar días sin precio.']);
            }
            $antes = $actual?->only(['id', 'precio', 'desde', 'hasta']);
            if ($actual !== null && $actual->desde->toDateString() === $hoy) {
                $actual->update($datos);
                $nueva = $actual;
            } else {
                if ($actual !== null) {
                    $actual->update(['hasta' => today('America/Lima')->subDay()->toDateString()]);
                }
                $nueva = TarifaLeche::create($datos + ['desde' => $hoy]);
            }
            $version->update(['valor' => (string) ((int) $version->valor + 1)]);
            Auditoria::create(['entidad' => 'tarifa_leche', 'entidad_id' => $nueva->id, 'usuario_id' => auth('operador')->id(),
                'accion' => 'actualizar', 'valor_antes' => $antes ? json_encode($antes) : null,
                'valor_despues' => json_encode($nueva->only(['precio', 'desde', 'hasta'])), 'motivo' => 'Cambio de precio desde hoy', 'ocurrido_en' => now()]);
        });

        return redirect()->route('admin.tarifas.index')->with('estado', 'Precio actualizado desde hoy. El historial anterior se conserva.');
    }

    private function guardar(Request $request, ?int $id = null): RedirectResponse
    {
        $datos = $request->validate([
            'precio' => ['required', 'numeric', 'gt:0', 'max:9999999', 'decimal:0,3'],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
        DB::transaction(function () use ($datos, $id): void {
            $version = Configuracion::whereKey('tarifas_version')->lockForUpdate()->firstOrFail();
            $tarifa = $id === null ? new TarifaLeche : TarifaLeche::findOrFail($id);
            $antes = $tarifa->exists ? $tarifa->only(['precio', 'desde', 'hasta']) : null;
            if ($tarifa->exists && $tarifa->desde->toDateString() <= today('America/Lima')->toDateString()) {
                if ((float) $datos['precio'] !== (float) $tarifa->precio || $datos['desde'] !== $tarifa->desde->toDateString()
                    || $datos['hasta'] < today('America/Lima')->toDateString()
                    || Liquidacion::whereDate('periodo_inicio', '<=', max($datos['hasta'], $tarifa->hasta->toDateString()))
                        ->whereDate('periodo_fin', '>', min($datos['hasta'], $tarifa->hasta->toDateString()))->exists()) {
                    throw ValidationException::withMessages(['precio' => 'Un precio iniciado conserva su importe y fecha de inicio. Solo puedes ajustar su fin desde hoy si no afecta periodos liquidados.']);
                }
            }
            if (TarifaLeche::when($id !== null, fn ($q) => $q->where('id', '!=', $id))
                ->whereDate('desde', '<=', $datos['hasta'])->whereDate('hasta', '>=', $datos['desde'])->exists()) {
                throw ValidationException::withMessages(['desde' => 'Estas fechas se cruzan con otro precio. El nuevo periodo debe empezar después del fin del anterior.']);
            }
            $tarifa->fill($datos)->save();
            $version->update(['valor' => (string) ((int) $version->valor + 1)]);
            Auditoria::create(['entidad' => 'tarifa_leche', 'entidad_id' => $tarifa->id, 'usuario_id' => auth('operador')->id(),
                'accion' => $id === null ? 'crear' : 'actualizar', 'valor_antes' => $antes ? json_encode($antes) : null,
                'valor_despues' => json_encode($datos), 'motivo' => 'Precio general por temporada', 'ocurrido_en' => now()]);
        });

        return redirect()->route('admin.tarifas.index')->with('estado', 'Precio guardado. Los celulares lo recibirán al sincronizar.');
    }
}
