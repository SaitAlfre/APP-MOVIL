<?php

namespace App\Application\Vehiculos;

use App\Domain\Vehiculos\Exceptions\VehiculoInvalidoException;
use App\Domain\Vehiculos\Vehiculo;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use RuntimeException;

final class ActualizarVehiculoUseCase
{
    public function __construct(
        private readonly VehiculoRepositoryInterface $vehiculos,
    ) {}

    public function ejecutar(int $vehiculoId, string $nombre, string $placa): Vehiculo
    {
        $vehiculo = $this->vehiculos->buscarPorId($vehiculoId);

        if ($vehiculo === null) {
            throw new RuntimeException('El vehículo no existe.');
        }

        $existente = $this->vehiculos->buscarPorPlaca(strtoupper(trim($placa)));
        if ($existente !== null && $existente->id !== $vehiculo->id) {
            throw VehiculoInvalidoException::placaDuplicada();
        }

        return $this->vehiculos->guardar($vehiculo->actualizar($nombre, $placa));
    }
}
