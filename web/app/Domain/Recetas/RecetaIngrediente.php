<?php

namespace App\Domain\Recetas;

use App\Domain\Recetas\Exceptions\RecetaInvalidaException;

final class RecetaIngrediente
{
    private function __construct(
        public readonly int $insumoId,
        public readonly float $cantidad,
        public readonly string $unidad,
    ) {}

    public static function crear(int $insumoId, float $cantidad, string $unidad): self
    {
        if ($cantidad <= 0.0) {
            throw RecetaInvalidaException::ingredienteCantidadInvalida();
        }

        if (trim($unidad) === '') {
            throw RecetaInvalidaException::rendimientoUnidadVacia();
        }

        return new self($insumoId, $cantidad, trim($unidad));
    }
}
