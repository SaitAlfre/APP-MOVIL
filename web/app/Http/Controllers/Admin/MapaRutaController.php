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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MapaRutaController extends Controller
{
    private function rutasVisibles(): Builder
    {
        $usuario = auth('operador')->user();
        $query = Ruta::query();
        if (! $usuario->puede('zonas_vehiculos')) {
            $query->where('acopiador_id', $usuario->id)->where('activo', true)
                ->whereJsonLength('paradas', '>', 0)
                ->whereHas('zona', fn (Builder $zona) => $zona->where('activo', true));
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $datos = $request->validate([
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);
        $zonaIds = $this->rutasVisibles()->select('zona_id');
        $zonas = auth('operador')->user()->puede('zonas_vehiculos')
            ? Zona::orderBy('nombre')->get()
            : Zona::whereIn('id', $zonaIds)->orderBy('nombre')->get();
        $rutas = $this->rutasVisibles()->with(['zona', 'acopiador'])
            ->when($datos['zona_id'] ?? null, fn (Builder $query, int $id) => $query->where('zona_id', $id))
            ->when($datos['buscar'] ?? null, fn (Builder $query, string $buscar) => $query->where(fn (Builder $ruta) => $ruta
                ->where('nombre', 'like', '%'.$buscar.'%')->orWhere('codigo', 'like', '%'.$buscar.'%')))
            ->orderBy('nombre')->paginate(18);

        return view('admin.zonas-vehiculos.mapas', compact('rutas', 'zonas'));
    }

    public function show(Ruta $ruta): View
    {
        abort_unless($this->rutasVisibles()->whereKey($ruta->id)->exists(), 403);

        return $this->editor($ruta->load(['zona', 'acopiador']));
    }

    public function create(Request $request): View
    {
        $datos = $request->validate(['zona_id' => ['nullable', 'integer', Rule::exists('zonas', 'id')->where('activo', true)]]);

        return $this->editor(new Ruta(['activo' => true, 'zona_id' => $datos['zona_id'] ?? null]));
    }

    public function store(GuardarMapaRutaRequest $request): RedirectResponse
    {
        $ruta = DB::transaction(fn () => Ruta::create($this->datosParaGuardar($request) + [
            'codigo' => 'R-'.Str::upper(Str::random(10)), 'mapa_version' => 1,
        ]));

        return redirect()->route('admin.mapa-rutas.show', $ruta)->with('estado', 'Ruta creada con sus paradas.');
    }

    private function editor(Ruta $ruta): View
    {
        $puedeGestionar = auth('operador')->user()->puede('mapa_rutas', 'gestionar');
        $sectorSeleccionado = (int) old('zona_id', $ruta->zona_id);
        $proveedoresSector = $puedeGestionar
            ? Proveedor::where('estado', 'activo')->orderBy('nombres')->get(['id', 'codigo', 'nombres', 'direccion', 'zona_id', 'estado'])
            : collect();
        $paradas = $this->puntosParaMapa($puedeGestionar ? $this->datosAnteriores('paradas', $ruta->paradas) : $ruta->paradas, true);
        $trazado = $this->puntosParaMapa($puedeGestionar ? $this->datosAnteriores('trazado', $ruta->trazado) : $ruta->trazado, false);
        $proveedores = Proveedor::whereIn('id', collect($paradas)->pluck('proveedor_id')->filter())
            ->get(['id', 'codigo', 'nombres', 'direccion', 'zona_id', 'estado']);
        $zonasCatalogo = $puedeGestionar ? Zona::where('activo', true)->orWhere('id', $ruta->zona_id)->orderBy('nombre')->get() : collect();
        $configMapa = [
            'paradas' => $paradas, 'trazado' => $trazado, 'zona_id' => $sectorSeleccionado,
            'cambios_pendientes' => $puedeGestionar && session()->hasOldInput('paradas_json'),
            'teselas' => config('mapas.teselas'), 'centro' => config('mapas.centro'),
            'ruteo' => $puedeGestionar ? config('mapas.ruteo') : null,
            'credito_ruteo' => str_contains((string) config('mapas.ruteo'), 'openstreetmap.de') ? 'OSRM / FOSSGIS' : 'OSRM',
            'resumen' => $puedeGestionar ? $this->datosAnteriores('resumen_recorrido', $ruta->resumen_recorrido) : $ruta->resumen_recorrido,
            'sectores' => $zonasCatalogo->map(fn (Zona $zona) => ['id' => (int) $zona->id, 'activo' => $zona->activo])->values()->all(),
            'proveedores' => $proveedoresSector->merge($proveedores)->unique('id')->map(fn (Proveedor $proveedor) => [
                'id' => (int) $proveedor->id, 'codigo' => $proveedor->codigo, 'nombres' => $proveedor->nombres, 'direccion' => $proveedor->direccion,
                'zona_id' => (int) $proveedor->zona_id, 'estado' => $proveedor->estado->value,
            ])->values()->all(),
        ];
        $acopiadores = $puedeGestionar ? Usuario::where('activo', true)->where('bloqueado_manualmente', false)
            ->where(fn (Builder $query) => $query->whereNull('bloqueado_hasta')->orWhere('bloqueado_hasta', '<=', now()))
            ->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get() : collect();

        return view('admin.zonas-vehiculos.mapa', compact('ruta', 'puedeGestionar', 'acopiadores', 'configMapa', 'zonasCatalogo'));
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
            $actual->update($this->datosParaGuardar($request) + [
                'mapa_version' => $actual->mapa_version + 1,
            ]);

            return redirect()->route('admin.mapa-rutas.show', $actual)->with('estado', 'Recorrido y paradas guardados.');
        });
    }

    /** @return array<string, mixed> */
    private function datosParaGuardar(GuardarMapaRutaRequest $request): array
    {
        $datos = $request->validated();

        return [
            'nombre' => trim($datos['nombre']), 'zona_id' => (int) $datos['zona_id'], 'activo' => (bool) $datos['activo'],
            'paradas' => $this->puntosParaMapa($datos['paradas'], true),
            'trazado' => $this->puntosParaMapa($datos['trazado'], false),
            'acopiador_id' => $datos['acopiador_id'] ?? null,
            'indicaciones' => $datos['indicaciones'] ?? null,
            'resumen_recorrido' => $datos['resumen_recorrido'] ?? null,
        ];
    }

    private function datosAnteriores(string $campo, mixed $actual): mixed
    {
        $json = old($campo.'_json');

        return is_string($json) ? json_decode($json, true) : old($campo, $actual);
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
