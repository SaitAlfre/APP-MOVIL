<?php

namespace App\Application\Productos;

use App\Domain\Productos\Producto;
use App\Domain\Productos\ProductoRepositoryInterface;

final class CrearProductoUseCase
{
    public function __construct(
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function ejecutar(
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
    ): Producto {
        return $this->productos->guardar(Producto::crear($nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad, $unidadContenido));
    }
}
