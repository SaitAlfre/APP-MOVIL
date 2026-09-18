<?php

namespace App\Application\Productos;

use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\Producto;
use App\Domain\Productos\ProductoRepositoryInterface;

final class ActualizarProductoUseCase
{
    public function __construct(
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function ejecutar(
        int $id,
        string $nombre,
        string $presentacion,
        string $unidadProduccion,
        ?float $contenidoPorUnidad,
        ?string $unidadContenido,
    ): Producto {
        $producto = $this->productos->buscarPorId($id);

        if ($producto === null) {
            throw ProductoInvalidoException::noExiste();
        }

        return $this->productos->guardar($producto->conDatosActualizados($nombre, $presentacion, $unidadProduccion, $contenidoPorUnidad, $unidadContenido));
    }
}
