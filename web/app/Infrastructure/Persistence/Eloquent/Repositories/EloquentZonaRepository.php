<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Zonas\Zona as ZonaDominio;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Zona as ZonaEloquent;

final class EloquentZonaRepository implements ZonaRepositoryInterface
{
    public function activas(): array
    {
        return ZonaEloquent::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (ZonaEloquent $zona) => $this->aDominio($zona))
            ->all();
    }

    public function todas(): array
    {
        return ZonaEloquent::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (ZonaEloquent $zona) => $this->aDominio($zona))
            ->all();
    }

    public function buscarPorId(int $id): ?ZonaDominio
    {
        $zona = ZonaEloquent::query()->find($id);

        return $zona !== null ? $this->aDominio($zona) : null;
    }

    private function aDominio(ZonaEloquent $zona): ZonaDominio
    {
        return new ZonaDominio(
            id: $zona->id,
            nombre: $zona->nombre,
            activo: $zona->activo,
        );
    }
}
