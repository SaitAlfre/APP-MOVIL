<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Historial de recojos: solo consulta. Los recojos se registran dentro del detalle de su jornada. */
class EntregaController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'fecha' => ['nullable', 'date'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'periodo' => ['nullable', 'in:hoy,ayer,semana'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'acopiador_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'origen' => ['nullable', 'in:celular,web'],
            'estado' => ['nullable', 'string', 'in:registrada,anulada'],
            'orden' => ['nullable', 'in:recientes,antiguos,litros_desc,litros_asc'],
            'por_pagina' => ['nullable', 'in:10,20,50'],
        ]);
        $orden = $request->input('orden', 'recientes');
        $hoy = CarbonImmutable::now('America/Lima')->startOfDay();
        $periodo = $request->filled('periodo') ? $request->string('periodo')->toString() : null;
        $fecha = fn (string $campo) => $request->filled($campo) ? CarbonImmutable::parse($request->input($campo), 'America/Lima') : null;
        [$desde, $hasta] = match ($periodo) {
            'hoy' => [$hoy, $hoy],
            'ayer' => [$hoy->subDay(), $hoy->subDay()],
            'semana' => [$hoy->startOfWeek(), $hoy],
            default => [$fecha('desde') ?? $fecha('fecha'), $fecha('hasta') ?? $fecha('fecha')],
        };

        $filtrada = fn ($query) => $query
            ->when($request->filled('buscar'), function ($interno) use ($request) {
                $termino = '%'.$request->string('buscar').'%';

                $interno->where(function ($condiciones) use ($termino) {
                    $condiciones->whereHas('proveedor', fn ($proveedores) => $proveedores->where('nombres', 'like', $termino)->orWhere('codigo', 'like', $termino))
                        ->orWhereHas('jornada.usuario', fn ($usuarios) => $usuarios->where('nombres', 'like', $termino));
                });
            })
            ->when($desde, fn ($interno) => $interno->where('registrado_en', '>=', $desde->startOfDay()->utc()))
            ->when($hasta, fn ($interno) => $interno->where('registrado_en', '<=', $hasta->endOfDay()->utc()))
            ->when($request->filled('zona_id'), fn ($interno) => $interno->where('zona_id', $request->integer('zona_id')))
            ->when($request->filled('acopiador_id'), fn ($interno) => $interno->where('usuario_id', $request->integer('acopiador_id')))
            ->when($request->input('origen') === 'celular', fn ($interno) => $interno->whereNotNull('uuid_movil'))
            ->when($request->input('origen') === 'web', fn ($interno) => $interno->whereNull('uuid_movil'));
        $conEstado = fn ($query) => $filtrada($query)
            ->when($request->filled('estado'), fn ($interno) => $interno->where('anulada', $request->string('estado')->toString() === 'anulada'));

        $entregas = $conEstado(Entrega::query()->with(['proveedor.zona', 'jornada.usuario']))
            ->when($orden === 'recientes', fn ($q) => $q->latest('registrado_en'))
            ->when($orden === 'antiguos', fn ($q) => $q->oldest('registrado_en'))
            ->when($orden === 'litros_desc', fn ($q) => $q->orderByDesc('litros')->latest('registrado_en'))
            ->when($orden === 'litros_asc', fn ($q) => $q->orderBy('litros')->latest('registrado_en'))
            ->paginate($request->integer('por_pagina', 20))
            ->withQueryString();

        return view('admin.entregas.index', [
            'entregas' => $entregas,
            'orden' => $orden,
            'periodo' => $periodo,
            'desde' => $desde?->toDateString(),
            'hasta' => $hasta?->toDateString(),
            'conteos' => [
                '' => $filtrada(Entrega::query())->count(),
                'registrada' => $filtrada(Entrega::query())->where('anulada', false)->count(),
                'anulada' => $filtrada(Entrega::query())->where('anulada', true)->count(),
            ],
            'litrosFiltrados' => (float) $conEstado(Entrega::query())->where('anulada', false)->sum('litros'),
            'litrosHoy' => (float) Entrega::whereBetween('registrado_en', [now('America/Lima')->startOfDay()->utc(), now('America/Lima')->endOfDay()->utc()])->where('anulada', false)->sum('litros'),
            'entregasHoy' => Entrega::whereBetween('registrado_en', [now('America/Lima')->startOfDay()->utc(), now('America/Lima')->endOfDay()->utc()])->where('anulada', false)->count(),
            'zonasDisponibles' => Zona::query()->orderBy('nombre')->get(),
            'acopiadores' => Usuario::query()->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->pluck('nombres', 'id')->all(),
        ]);
    }
}
