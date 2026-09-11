<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Vehiculos\Vehiculo as VehiculoDominio;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Vehiculo as VehiculoEloquent;

final class EloquentVehiculoRepository implements VehiculoRepositoryInterface
{
    public function activos(): array
    {
        return VehiculoEloquent::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (VehiculoEloquent $v) => $this->aDominio($v))
            ->all();
    }

    public function todos(): array
    {
        return VehiculoEloquent::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (VehiculoEloquent $v) => $this->aDominio($v))
            ->all();
    }

    public function buscarPorId(int $id): ?VehiculoDominio
    {
        $vehiculo = VehiculoEloquent::query()->find($id);

        return $vehiculo !== null ? $this->aDominio($vehiculo) : null;
    }

    private function aDominio(VehiculoEloquent $vehiculo): VehiculoDominio
    {
        return new VehiculoDominio(
            id: $vehiculo->id,
            nombre: $vehiculo->nombre,
            placa: $vehiculo->placa,
            activo: $vehiculo->activo,
        );
    }
}
