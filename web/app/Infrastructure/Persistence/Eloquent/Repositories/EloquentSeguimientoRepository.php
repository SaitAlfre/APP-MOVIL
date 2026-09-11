<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Seguimiento\PosicionSeguimiento as PosicionDominio;
use App\Domain\Seguimiento\SeguimientoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\PosicionSeguimiento as PosicionEloquent;
use DateTimeImmutable;

final class EloquentSeguimientoRepository implements SeguimientoRepositoryInterface
{
    public function registrarPosicion(PosicionDominio $posicion): PosicionDominio
    {
        $registro = PosicionEloquent::query()->create([
            'jornada_id' => $posicion->jornadaId,
            'lat' => $posicion->lat,
            'lng' => $posicion->lng,
            'precision_m' => $posicion->precisionM,
            'capturada_en' => $posicion->capturadaEn,
        ]);

        return $this->aDominio($registro);
    }

    public function ultimaPosicion(int $jornadaId): ?PosicionDominio
    {
        $registro = PosicionEloquent::query()
            ->where('jornada_id', $jornadaId)
            ->orderByDesc('capturada_en')
            ->first();

        return $registro !== null ? $this->aDominio($registro) : null;
    }

    private function aDominio(PosicionEloquent $posicion): PosicionDominio
    {
        return new PosicionDominio(
            id: $posicion->id,
            jornadaId: $posicion->jornada_id,
            lat: (float) $posicion->lat,
            lng: (float) $posicion->lng,
            precisionM: $posicion->precision_m !== null ? (float) $posicion->precision_m : null,
            capturadaEn: DateTimeImmutable::createFromInterface($posicion->capturada_en),
        );
    }
}
