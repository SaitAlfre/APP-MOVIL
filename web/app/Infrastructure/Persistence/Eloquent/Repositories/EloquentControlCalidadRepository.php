<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Calidad\ControlCalidad as ControlCalidadDominio;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Calidad\EstadoCalidad;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad as ControlCalidadEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentControlCalidadRepository implements ControlCalidadRepositoryInterface
{
    public function paginar(int $porPagina = 20, ?EstadoCalidad $resultado = null): LengthAwarePaginator
    {
        return ControlCalidadEloquent::query()
            ->when($resultado !== null, fn ($query) => $query->where('resultado', $resultado->value))
            ->orderByDesc('evaluado_en')
            ->paginate($porPagina)
            ->through(fn (ControlCalidadEloquent $c) => $this->aDominio($c));
    }

    public function buscarPorEntregaId(int $entregaId): ?ControlCalidadDominio
    {
        $control = ControlCalidadEloquent::query()->where('entrega_id', $entregaId)->first();

        return $control !== null ? $this->aDominio($control) : null;
    }

    public function guardar(ControlCalidadDominio $control): ControlCalidadDominio
    {
        $registro = $control->id !== null
            ? ControlCalidadEloquent::query()->findOrFail($control->id)
            : new ControlCalidadEloquent;

        $registro->fill([
            'entrega_id' => $control->entregaId,
            'usuario_id' => $control->usuarioId,
            'resultado' => $control->resultado,
            'temperatura_c' => $control->temperaturaC,
            'acidez' => $control->acidez,
            'observaciones' => $control->observaciones,
            'evaluado_en' => $control->evaluadoEn,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    /** @return array{aprobado: int, observado: int, rechazado: int} */
    public function contarPorResultado(): array
    {
        $conteos = ControlCalidadEloquent::query()
            ->selectRaw('resultado, COUNT(*) as total')
            ->groupBy('resultado')
            ->pluck('total', 'resultado');

        return [
            'aprobado' => (int) ($conteos[EstadoCalidad::Aprobado->value] ?? 0),
            'observado' => (int) ($conteos[EstadoCalidad::Observado->value] ?? 0),
            'rechazado' => (int) ($conteos[EstadoCalidad::Rechazado->value] ?? 0),
        ];
    }

    /** @return array{aprobado: int, observado: int, rechazado: int} */
    public function contarPorResultadoEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $conteos = ControlCalidadEloquent::query()
            ->whereBetween('evaluado_en', [$desde->format('Y-m-d 00:00:00'), $hasta->format('Y-m-d 23:59:59')])
            ->selectRaw('resultado, COUNT(*) as total')
            ->groupBy('resultado')
            ->pluck('total', 'resultado');

        return [
            'aprobado' => (int) ($conteos[EstadoCalidad::Aprobado->value] ?? 0),
            'observado' => (int) ($conteos[EstadoCalidad::Observado->value] ?? 0),
            'rechazado' => (int) ($conteos[EstadoCalidad::Rechazado->value] ?? 0),
        ];
    }

    private function aDominio(ControlCalidadEloquent $control): ControlCalidadDominio
    {
        return ControlCalidadDominio::reconstruir(
            id: $control->id,
            entregaId: $control->entrega_id,
            usuarioId: $control->usuario_id,
            resultado: $control->resultado instanceof EstadoCalidad ? $control->resultado : EstadoCalidad::from($control->resultado),
            temperaturaC: $control->temperatura_c !== null ? (float) $control->temperatura_c : null,
            acidez: $control->acidez !== null ? (float) $control->acidez : null,
            observaciones: $control->observaciones,
            evaluadoEn: DateTimeImmutable::createFromInterface($control->evaluado_en),
        );
    }
}
