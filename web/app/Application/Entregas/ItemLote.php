<?php

namespace App\Application\Entregas;

final class ItemLote
{
    public function __construct(
        public readonly int $proveedorId,
        public readonly float $litros,
        public readonly int $tachos,
        public readonly ?string $observaciones = null,
    ) {}
}
