<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarMapaRutaRequest;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MapaRutaController extends Controller
{
    private function rutasVisibles(): Builder
    {
        $usuario = auth('operador')->user();
        $query = Ruta::query();
        if (! $usuario->puede('zonas_vehiculos')) {
            $query->where('acopiador_id', $usuario->id)->where('activo', true)
                ->whereHas('zona', fn (Builder $zona) => $zona->where('activo', true));
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $datos = $request->validate(['zona_id' => ['nullable', 'integer', 'exists:zonas,id']]);
        $zonaIds = $this->rutasVisibles()->select('zona_id');
        $zonas = auth('operador')->user()->puede('zonas_vehiculos')
            ? Zona::orderBy('nombre')->get()
            : Zona::whereIn('id', $zonaIds)->orderBy('nombre')->get();
        $rutas = $this->rutasVisibles()->with(['zona', 'acopiador'])
            ->when($datos['zona_id'] ?? null, fn (Builder $query, int $id) => $query->where('zona_id', $id))
            ->orderBy('nombre')->paginate(18);

        return view('admin.zonas-vehiculos.mapas', compact('rutas', 'zonas'));
    }

    public function show(Ruta $ruta): View
    {
        abort_unless($this->rutasVisibles()->whereKey($ruta->id)->exists(), 403);
        $ruta->load(['zona', 'acopiador']);
        $puedeGestionar = auth('operador')->user()->puede('mapa_rutas', 'gestionar');
        $proveedoresSector = $puedeGestionar
            ? Proveedor::where('zona_id', $ruta->zona_id)->where('estado', 'activo')->orderBy('nombres')->get()
            : collect();
        $paradas = $this->puntosParaMapa($puedeGestionar ? old('paradas', $ruta->paradas) : $ruta->paradas, true);
        $trazado = $this->puntosParaMapa($puedeGestionar ? old('trazado', $ruta->trazado) : $ruta->trazado, false);
        $proveedores = Proveedor::whereIn('id', collect($paradas)->pluck('proveedor_id')->filter())
            ->get(['id', 'nombres', 'direccion', 'zona_id', 'estado']);
        $configMapa = [
            'paradas' => $paradas, 'trazado' => $trazado, 'zona_id' => (int) $ruta->zona_id,
            'teselas' => config('mapas.teselas'), 'centro' => config('mapas.centro'),
            'proveedores' => $proveedoresSector->merge($proveedores)->map(fn (Proveedor $proveedor) => [
                'id' => (int) $proveedor->id, 'nombres' => $proveedor->nombres, 'direccion' => $proveedor->direccion,
                'zona_id' => (int) $proveedor->zona_id, 'estado' => $proveedor->estado->value,
            ])->values()->all(),
        ];
        $acopiadores = $puedeGestionar ? Usuario::where('activo', true)->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get() : collect();

        return view('admin.zonas-vehiculos.mapa', compact('ruta', 'puedeGestionar', 'proveedoresSector', 'acopiadores', 'configMapa'));
    }

    public function update(GuardarMapaRutaRequest $request, Ruta $ruta): RedirectResponse
    {
        return DB::transaction(function () use ($request, $ruta): RedirectResponse {
            $actual = Ruta::whereKey($ruta->id)->lockForUpdate()->firstOrFail();
            if ($actual->mapa_version !== $request->integer('mapa_version') || $actual->zona_id !== $ruta->zona_id) {
                return redirect()->route('admin.mapa-rutas.show', $actual)->withErrors([
                    'mapa_version' => 'Otro usuario modificó la ruta. Se cargó la versión actual; vuelve a aplicar tus cambios.',
                ]);
            }
            $datos = $request->validated();
            $actual->update([
                'paradas' => $this->puntosParaMapa($datos['paradas'], true),
                'trazado' => $this->puntosParaMapa($datos['trazado'], false),
                'acopiador_id' => $datos['acopiador_id'] ?? null,
                'indicaciones' => $datos['indicaciones'] ?? null,
                'mapa_version' => $actual->mapa_version + 1,
            ]);

            return redirect()->route('admin.mapa-rutas.show', $actual)->with('estado', 'Recorrido y paradas guardados.');
        });
    }

    /** @return list<array<string, mixed>> */
    private function puntosParaMapa(mixed $datos, bool $paradas): array
    {
        if (! is_array($datos)) {
            return [];
        }

        return collect($datos)->filter(fn ($punto) => is_array($punto)
            && is_numeric($punto['latitud'] ?? null) && abs((float) $punto['latitud']) <= 85
            && is_numeric($punto['longitud'] ?? null) && abs((float) $punto['longitud']) <= 180
            && (! $paradas || is_string($punto['nombre'] ?? null)))
            ->take($paradas ? 100 : 2000)->map(fn (array $punto) => [
                'latitud' => (float) $punto['latitud'], 'longitud' => (float) $punto['longitud'],
            ] + ($paradas ? [
                'nombre' => mb_substr(trim($punto['nombre']), 0, 120),
                'proveedor_id' => is_numeric($punto['proveedor_id'] ?? null) ? (int) $punto['proveedor_id'] : null,
                'referencia' => is_string($punto['referencia'] ?? null) ? mb_substr($punto['referencia'], 0, 500) : '',
            ] : []))->values()->all();
    }
}
