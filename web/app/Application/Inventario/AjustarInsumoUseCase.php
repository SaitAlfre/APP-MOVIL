<?php

namespace App\Application\Inventario;

use App\Domain\Inventario\Insumo;
use App\Domain\Inventario\InsumoRepositoryInterface;

final class AjustarInsumoUseCase
{
    public function __construct(
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    public function ejecutar(int $insumoId, float $delta, string $motivo, int $usuarioId, ?string $observaciones): Insumo
    {
        return $this->insumos->ajustar($insumoId, $delta, $motivo, $usuarioId, $observaciones);
    }
}
