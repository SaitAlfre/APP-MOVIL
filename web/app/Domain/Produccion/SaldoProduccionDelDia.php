<?php

namespace App\Domain\Produccion;

final class SaldoProduccionDelDia
{
    public function __construct(
        public readonly AcopioDelDia $acopio,
        public readonly float $litrosAsignados,
    ) {}

    public function litrosDisponibles(): float
    {
        return round($this->acopio->litrosTotal() - $this->litrosAsignados, 3);
    }

    public function tieneSaldoDisponible(): bool
    {
        return $this->litrosDisponibles() > 0.0;
    }
}
