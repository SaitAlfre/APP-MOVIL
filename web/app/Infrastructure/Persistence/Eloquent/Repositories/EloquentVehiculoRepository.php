<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Vehiculos\Vehiculo as VehiculoDominio;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Jornada;
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

    public function buscarPorPlaca(string $placa): ?VehiculoDominio
    {
        $vehiculo = VehiculoEloquent::query()->where('placa', $placa)->first();

        return $vehiculo !== null ? $this->aDominio($vehiculo) : null;
    }

    public function guardar(VehiculoDominio $vehiculo): VehiculoDominio
    {
        $registro = $vehiculo->id !== null
            ? VehiculoEloquent::query()->findOrFail($vehiculo->id)
            : new VehiculoEloquent;

        $registro->fill([
            'nombre' => $vehiculo->nombre,
            'placa' => $vehiculo->placa,
            'activo' => $vehiculo->activo,
        ]);
        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    public function tieneJornadasAbiertas(int $id): bool
    {
        return Jornada::query()->where('vehiculo_id', $id)->whereNull('cerrada_en')->exists();
    }

    private function aDominio(VehiculoEloquent $vehiculo): VehiculoDominio
    {
        return VehiculoDominio::reconstruir(
            id: $vehiculo->id,
            nombre: $vehiculo->nombre,
            placa: $vehiculo->placa,
            activo: $vehiculo->activo,
        );
    }
}
