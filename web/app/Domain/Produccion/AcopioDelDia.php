<?php

namespace App\Domain\Produccion;

use DateTimeImmutable;

final class AcopioDelDia
{
    /** @param list<array{vehiculoId: int, vehiculoNombre: string, litros: float}> $porVehiculo */
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly array $porVehiculo,
        public readonly bool $yaProducido,
    ) {}

    public function litrosTotal(): float
    {
        return round(array_sum(array_column($this->porVehiculo, 'litros')), 3);
    }

    public function tieneLitros(): bool
    {
        return $this->litrosTotal() > 0.0;
    }
}
