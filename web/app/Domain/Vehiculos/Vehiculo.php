<?php

namespace App\Domain\Vehiculos;

final class Vehiculo
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly string $placa,
        public readonly bool $activo,
    ) {}
}
