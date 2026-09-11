<?php

namespace App\Domain\Jornadas;

use DateTimeImmutable;

final class Jornada
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $usuarioId,
        public readonly int $zonaId,
        public readonly int $vehiculoId,
        public readonly DateTimeImmutable $fecha,
        public readonly DateTimeImmutable $abiertaEn,
        public readonly ?DateTimeImmutable $cerradaEn,
        public readonly bool $seguimientoActivo,
    ) {}

    public function estaAbierta(): bool
    {
        return $this->cerradaEn === null;
    }
}
