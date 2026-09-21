<?php

namespace App\Application\Produccion;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Produccion\AcopioDelDia;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use DateTimeImmutable;

final class ObtenerAcopioDelDiaUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(DateTimeImmutable $fecha): AcopioDelDia
    {
        $porVehiculo = $this->entregas->recepcionPorVehiculoEnFecha($fecha);

        return new AcopioDelDia(
            fecha: $fecha,
            porVehiculo: $porVehiculo,
            yaProducido: $this->lotes->existeAsignacionEnFecha($fecha),
        );
    }
}
