<?php

namespace App\Domain\Vehiculos;

interface VehiculoRepositoryInterface
{
    /** @return list<Vehiculo> */
    public function activos(): array;

    public function buscarPorId(int $id): ?Vehiculo;
}
