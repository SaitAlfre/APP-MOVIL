<?php

namespace App\Application\Vehiculos;

use App\Domain\Vehiculos\Exceptions\VehiculoInvalidoException;
use App\Domain\Vehiculos\Vehiculo;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;

final class CrearVehiculoUseCase
{
    public function __construct(
        private readonly VehiculoRepositoryInterface $vehiculos,
    ) {}

    public function ejecutar(string $nombre, string $placa): Vehiculo
    {
        if ($this->vehiculos->buscarPorPlaca(strtoupper(trim($placa))) !== null) {
            throw VehiculoInvalidoException::placaDuplicada();
        }

        return $this->vehiculos->guardar(Vehiculo::crear($nombre, $placa));
    }
}
