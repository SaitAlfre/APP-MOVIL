<?php

namespace App\Application\Vehiculos;

use App\Domain\Vehiculos\Vehiculo;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use RuntimeException;

final class CambiarEstadoVehiculoUseCase
{
    public function __construct(
        private readonly VehiculoRepositoryInterface $vehiculos,
    ) {}

    public function ejecutar(int $vehiculoId, bool $activo): Vehiculo
    {
        $vehiculo = $this->vehiculos->buscarPorId($vehiculoId);

        if ($vehiculo === null) {
            throw new RuntimeException('El vehículo no existe.');
        }

        if (! $activo && $this->vehiculos->tieneJornadasAbiertas($vehiculoId)) {
            throw new RuntimeException('No se puede desactivar: el vehículo está en una jornada abierta.');
        }

        return $this->vehiculos->guardar($vehiculo->conEstado($activo));
    }
}
