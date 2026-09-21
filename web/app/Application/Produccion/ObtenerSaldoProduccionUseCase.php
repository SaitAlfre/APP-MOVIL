<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Produccion\SaldoProduccionDelDia;
use DateTimeImmutable;

final class ObtenerSaldoProduccionUseCase
{
    public function __construct(
        private readonly ObtenerAcopioDelDiaUseCase $obtenerAcopio,
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(DateTimeImmutable $fecha): SaldoProduccionDelDia
    {
        return new SaldoProduccionDelDia(
            acopio: $this->obtenerAcopio->ejecutar($fecha),
            litrosAsignados: $this->lotes->litrosAsignadosEnFecha($fecha),
        );
    }
}
