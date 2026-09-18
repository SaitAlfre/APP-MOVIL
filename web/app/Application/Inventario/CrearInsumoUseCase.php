<?php

namespace App\Application\Inventario;

use App\Domain\Inventario\Insumo;
use App\Domain\Inventario\InsumoRepositoryInterface;

final class CrearInsumoUseCase
{
    public function __construct(
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    public function ejecutar(string $nombre, string $unidad, float $stockMinimo): Insumo
    {
        return $this->insumos->guardar(Insumo::crear($nombre, $unidad, $stockMinimo));
    }
}
