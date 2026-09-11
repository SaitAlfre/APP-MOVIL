<?php

namespace App\Domain\Vehiculos;

interface VehiculoRepositoryInterface
{
    /** @return list<Vehiculo> */
    public function activos(): array;

    /** @return list<Vehiculo> */
    public function todos(): array;

    public function buscarPorId(int $id): ?Vehiculo;

    public function buscarPorPlaca(string $placa): ?Vehiculo;

    public function guardar(Vehiculo $vehiculo): Vehiculo;
}
