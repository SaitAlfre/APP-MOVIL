<?php

namespace App\Application\Entregas;

use App\Domain\Entregas\Entrega;

final class ResultadoRegistroEntrega
{
    public function __construct(
        public readonly Entrega $entrega,
        public readonly bool $advertenciaDesviacion,
    ) {}
}
