<?php

namespace App\Domain\Produccion;

/** Cantidad de un insumo requerida por un lote, calculada a partir de la receta. */
final class NecesidadInsumo
{
    public function __construct(
        public readonly int $insumoId,
        public readonly string $unidad,
        public readonly float $cantidad,
    ) {}
}
