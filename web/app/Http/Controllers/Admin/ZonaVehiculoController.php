<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ZonaVehiculoController extends Controller
{
    /** Zonas y vehículos en tarjetas: cobertura de proveedores, uso de cada vehículo y litros de la semana. */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->string('tab')->toString() === 'rutas') {
            return redirect()->route('admin.mapa-rutas.index');
        }
        $datos = $request->validate(['buscar' => ['nullable', 'string', 'max:120']]);
        $pestana = $request->string('tab')->toString() === 'vehiculos' ? 'vehiculos' : 'zonas';
        $buscar = mb_strtolower(trim($datos['buscar'] ?? ''));
        $coincide = fn (string $texto) => $buscar === '' || str_contains(mb_strtolower($texto), $buscar);
        $hoy = CarbonImmutable::now('America/Lima')->startOfDay();
        $desde = $hoy->subDays(6)->utc();
        $litrosPor = fn (string $campo) => Entrega::query()->where('anulada', false)->where('registrado_en', '>=', $desde)
            ->selectRaw($campo.', SUM(litros) as total')->groupBy($campo)->pluck('total', $campo);
        $abiertas = Jornada::query()->with(['usuario', 'zona', 'vehiculo'])->whereNull('cerrada_en')->get();

        $litrosZona = $litrosPor('zona_id');
        $rutas = Ruta::query()->with('acopiador')->get()->groupBy('zona_id');
        $acopiadoresZona = Usuario::query()->where('activo', true)->whereJsonContains('roles', 'acopiador')->whereNotNull('zona_id')->get()->groupBy('zona_id');
        $proveedores = Proveedor::query()->where('estado', 'activo')->get(['id', 'zona_id', 'latitud'])->groupBy('zona_id');
        $zonas = Zona::query()->orderBy('nombre')->get()->map(fn (Zona $zona) => [
            'zona' => $zona,
            'proveedores' => $proveedores->get($zona->id, collect())->count(),
            'ubicados' => $proveedores->get($zona->id, collect())->whereNotNull('latitud')->count(),
            'rutas' => $rutas->get($zona->id, collect()),
            'acopiadores' => $rutas->get($zona->id, collect())->pluck('acopiador.nombres')->filter()
                ->merge($acopiadoresZona->get($zona->id, collect())->pluck('nombres'))->unique()->values(),
            'enRuta' => $abiertas->where('zona_id', $zona->id)->count(),
            'litrosSemana' => round((float) ($litrosZona[$zona->id] ?? 0), 1),
        ]);

        $litrosVehiculo = $litrosPor('vehiculo_id');
        $jornadasMes = Jornada::query()->whereDate('fecha', '>=', $hoy->startOfMonth()->toDateString())
            ->selectRaw('vehiculo_id, COUNT(*) as total')->groupBy('vehiculo_id')->pluck('total', 'vehiculo_id');
        $ultimas = Jornada::query()->with(['usuario', 'zona'])->whereIn('id', Jornada::query()->selectRaw('MAX(id)')->groupBy('vehiculo_id'))->get()->keyBy('vehiculo_id');
        $vehiculos = Vehiculo::query()->orderBy('nombre')->get()->map(fn (Vehiculo $vehiculo) => [
            'vehiculo' => $vehiculo,
            'enUso' => $abiertas->firstWhere('vehiculo_id', $vehiculo->id),
            'ultima' => $ultimas->get($vehiculo->id),
            'litrosSemana' => round((float) ($litrosVehiculo[$vehiculo->id] ?? 0), 1),
            'jornadasMes' => (int) ($jornadasMes[$vehiculo->id] ?? 0),
        ]);

        return view('admin.zonas-vehiculos.index', [
            'pestana' => $pestana,
            'filasZonas' => $zonas->filter(fn (array $fila) => $coincide($fila['zona']->nombre))->values(),
            'vehiculos' => $vehiculos->filter(fn (array $fila) => $coincide($fila['vehiculo']->nombre.' '.$fila['vehiculo']->placa))->values(),
            'conteos' => ['zonas' => $zonas->count(), 'vehiculos' => $vehiculos->count()],
            'resumen' => [
                'zonasActivas' => $zonas->where('zona.activo', true)->count(),
                'vehiculosActivos' => $vehiculos->where('vehiculo.activo', true)->count(),
                'enRuta' => $abiertas->count(),
                'motocargas' => $vehiculos->where('vehiculo.tipo', 'motocarga')->count(),
            ],
            'grafico' => ($pestana === 'zonas' ? $zonas->map(fn (array $fila) => ['label' => $fila['zona']->nombre, 'value' => $fila['litrosSemana']])
                : $vehiculos->map(fn (array $fila) => ['label' => $fila['vehiculo']->nombre.' · '.$fila['vehiculo']->placa, 'value' => $fila['litrosSemana']]))
                ->sortByDesc('value')->values()->all(),
            'abiertas' => $abiertas,
        ]);
    }

    public function storeRuta(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'unique:rutas,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
        ]);
        $ruta = Ruta::create($datos + ['activo' => true]);

        return redirect()->route('admin.mapa-rutas.show', $ruta)->with('estado', 'Ruta registrada. Marca sus paradas y asigna un acopiador.');
    }

    public function updateRuta(Request $request, Ruta $ruta): RedirectResponse
    {
        $ruta = Ruta::whereKey($ruta->id)->lockForUpdate()->firstOrFail();
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('rutas', 'codigo')->ignore($ruta)],
            'nombre' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'activo' => ['required', 'boolean'],
        ]);
        if ((int) $datos['zona_id'] !== (int) $ruta->zona_id && (count($ruta->paradas ?? []) > 0 || count($ruta->trazado ?? []) > 0)) {
            throw ValidationException::withMessages(['zona_id' => 'La ruta tiene un recorrido guardado. Crea otra ruta para el nuevo sector o elimina primero sus paradas y trazado.']);
        }
        $ruta->update($datos);

        return back()->with('estado', 'Ruta actualizada.');
    }
}
