<?php

namespace App\Domain\Seguimiento;

use DateTimeImmutable;

final class PosicionSeguimiento
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $jornadaId,
        public readonly float $lat,
        public readonly float $lng,
        public readonly ?float $precisionM,
        public readonly DateTimeImmutable $capturadaEn,
    ) {}
}
