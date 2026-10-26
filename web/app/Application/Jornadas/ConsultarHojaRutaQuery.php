<?php

namespace App\Application\Jornadas;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsultarHojaRutaQuery
{
    public function ejecutar(Jornada $jornada): array
    {
        $rutas = Ruta::query()->where('acopiador_id', $jornada->usuario_id)->where('zona_id', $jornada->zona_id)
            ->where('activo', true)->orderBy('id')->get();
        $orden = $rutas->flatMap(fn (Ruta $ruta) => collect($ruta->paradas ?? [])->pluck('proveedor_id'))->filter()->unique()->values();
        $proveedores = Proveedor::query()->where('zona_id', $jornada->zona_id)->where('estado', 'activo')
            ->when($orden->isNotEmpty(), fn ($q) => $q->whereIn('id', $orden))->orderBy('nombres')->get();
        $fecha = CarbonImmutable::parse($jornada->fecha->toDateString(), 'America/Lima');
        $recojos = Entrega::query()->with('jornada.usuario')->where('zona_id', $jornada->zona_id)->where('anulada', false)
            ->whereBetween('registrado_en', [$fecha->startOfDay()->utc(), $fecha->endOfDay()->utc()])->orderBy('registrado_en')->get()->groupBy('proveedor_id');
        $marcas = SinRecojo::query()->where('zona_id', $jornada->zona_id)->whereDate('fecha', $jornada->fecha)
            ->where('deshecha', false)->orderByDesc('registrada_en')->get()->unique('proveedor_id')->keyBy('proveedor_id');
        $posiciones = $orden->flip();
        $filas = $proveedores->sortBy(fn (Proveedor $p) => $posiciones[$p->id] ?? PHP_INT_MAX)->values()->map(function (Proveedor $p, int $indice) use ($recojos, $marcas) {
            $entregas = $recojos->get($p->id, collect());
            $marca = $marcas->get($p->id);

            return ['proveedor' => $p, 'numero' => $indice + 1, 'estado' => $entregas->isNotEmpty() ? 'atendido' : ($marca ? 'no_acopiado' : 'pendiente'),
                'litros' => (float) $entregas->sum('litros'), 'ultimo' => $entregas->last(), 'marca' => $marca];
        });
        $pagina = LengthAwarePaginator::resolveCurrentPage('ruta_page');

        return ['rutas' => $rutas, 'atendidos' => $filas->where('estado', 'atendido')->count(),
            'pendientes' => $filas->where('estado', 'pendiente')->count(), 'noAcopiados' => $filas->where('estado', 'no_acopiado')->count(),
            'total' => $filas->count(), 'filas' => new LengthAwarePaginator($filas->forPage($pagina, 30)->values(), $filas->count(), 30, $pagina,
                ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'ruta_page'])];
    }
}
