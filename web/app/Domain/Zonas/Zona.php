<?php

namespace App\Domain\Zonas;

final class Zona
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly bool $activo,
    ) {}
}
