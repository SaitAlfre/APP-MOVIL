<?php

namespace App\Application\Recetas;

use App\Domain\Recetas\Receta;
use App\Domain\Recetas\RecetaRepositoryInterface;

final class ListarRecetasPorProductoUseCase
{
    public function __construct(
        private readonly RecetaRepositoryInterface $recetas,
    ) {}

    /** @return list<Receta> */
    public function ejecutar(int $productoId): array
    {
        return $this->recetas->listarPorProducto($productoId);
    }
}
