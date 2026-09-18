<?php

namespace App\Application\Productos;

use App\Domain\Productos\Producto;
use App\Domain\Productos\ProductoRepositoryInterface;

final class CambiarEstadoProductoUseCase
{
    public function __construct(
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function ejecutar(int $id, bool $activo): Producto
    {
        return $this->productos->cambiarEstado($id, $activo);
    }
}
