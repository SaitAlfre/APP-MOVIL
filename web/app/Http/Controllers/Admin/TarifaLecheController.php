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
        return view('admin.liquidaciones.tarifas', ['tarifas' => TarifaLeche::orderByDesc('desde')->get(), 'hoy' => today('America/Lima')->toDateString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->guardar($request);
    }

    public function update(Request $request, int $tarifa): RedirectResponse
    {
        return $this->guardar($request, $tarifa);
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
