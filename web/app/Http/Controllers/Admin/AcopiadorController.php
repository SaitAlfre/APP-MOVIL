<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Equipo de acopio: quién está en ruta, qué ruta tiene asignada y cuánto recogió en la semana. */
class AcopiadorController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate([
            'estado' => ['nullable', Rule::in(['en_ruta', 'disponible', 'sin_ruta', 'inactivo'])],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);
        $hoy = CarbonImmutable::now('America/Lima')->startOfDay();
        $acopiadores = Usuario::query()->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get();
        $ids = $acopiadores->pluck('id');
        $jornadasActivas = Jornada::with(['zona', 'vehiculo'])->whereNull('cerrada_en')->whereIn('usuario_id', $ids)->get()->keyBy('usuario_id');
        $rutas = Ruta::query()->with('zona')->whereIn('acopiador_id', $ids)->where('activo', true)->get()->groupBy('acopiador_id');
        $litrosSemana = Entrega::query()->whereIn('usuario_id', $ids)->where('anulada', false)
            ->where('registrado_en', '>=', $hoy->subDays(6)->utc())->selectRaw('usuario_id, SUM(litros) as total')->groupBy('usuario_id')->pluck('total', 'usuario_id');
        $jornadasMes = Jornada::query()->whereIn('usuario_id', $ids)->whereDate('fecha', '>=', $hoy->startOfMonth()->toDateString())
            ->selectRaw('usuario_id, COUNT(*) as total')->groupBy('usuario_id')->pluck('total', 'usuario_id');
        $ultimaSalida = Jornada::query()->whereIn('usuario_id', $ids)->selectRaw('usuario_id, MAX(abierta_en) as ultima')->groupBy('usuario_id')->pluck('ultima', 'usuario_id');
        $zonas = Zona::query()->pluck('nombre', 'id');

        $fichas = $acopiadores->map(fn (Usuario $acopiador) => [
            'usuario' => $acopiador,
            'estado' => match (true) {
                ! $acopiador->activo || $acopiador->estaBloqueada() => 'inactivo',
                $jornadasActivas->has($acopiador->id) => 'en_ruta',
                ! $rutas->has($acopiador->id) => 'sin_ruta',
                default => 'disponible',
            },
            'jornada' => $jornadasActivas->get($acopiador->id),
            'rutas' => $rutas->get($acopiador->id, collect()),
            'zona' => $zonas[$acopiador->zona_id] ?? $rutas->get($acopiador->id)?->first()?->zona?->nombre,
            'litrosSemana' => round((float) ($litrosSemana[$acopiador->id] ?? 0), 1),
            'jornadasMes' => (int) ($jornadasMes[$acopiador->id] ?? 0),
            'ultimaSalida' => isset($ultimaSalida[$acopiador->id]) ? CarbonImmutable::parse($ultimaSalida[$acopiador->id])->setTimezone('America/Lima') : null,
        ]);
        $buscar = mb_strtolower(trim($datos['buscar'] ?? ''));
        $filtradas = $fichas->filter(fn (array $ficha) => $buscar === '' || str_contains(mb_strtolower(
            $ficha['usuario']->nombres.' '.$ficha['usuario']->username.' '.$ficha['usuario']->dni.' '.$ficha['zona']), $buscar));

        return view('admin.acopiadores.index', [
            'fichas' => $filtradas->when($datos['estado'] ?? null, fn ($coleccion, $estado) => $coleccion->where('estado', $estado))->values(),
            'estado' => $datos['estado'] ?? null,
            'conteos' => ['' => $filtradas->count()] + collect(['en_ruta', 'disponible', 'sin_ruta', 'inactivo'])
                ->mapWithKeys(fn (string $clave) => [$clave => $filtradas->where('estado', $clave)->count()])->all(),
            'resumen' => [
                'total' => $fichas->count(),
                'enRuta' => $fichas->where('estado', 'en_ruta')->count(),
                'sinRuta' => $fichas->where('estado', 'sin_ruta')->count(),
                'litrosSemana' => $fichas->sum('litrosSemana'),
            ],
            'litrosPorAcopiador' => $fichas->sortByDesc('litrosSemana')->take(8)
                ->map(fn (array $ficha) => ['label' => $ficha['usuario']->nombres, 'value' => $ficha['litrosSemana']])->values()->all(),
            'salidasHoy' => Jornada::query()->with(['usuario', 'zona'])->whereDate('fecha', $hoy->toDateString())->orderBy('abierta_en')->get(),
        ]);
    }
}
