<?php

namespace App\Application\Recetas;

use App\Domain\Productos\Exceptions\ProductoInvalidoException;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Domain\Recetas\Receta;
use App\Domain\Recetas\RecetaRepositoryInterface;

final class CrearRecetaUseCase
{
    public function __construct(
        private readonly RecetaRepositoryInterface $recetas,
        private readonly ProductoRepositoryInterface $productos,
        private readonly ConstruyeIngredientesReceta $construyeIngredientes,
    ) {}

    /** @param list<array{insumo_id: int, cantidad: float, unidad: string}> $ingredientesInput */
    public function ejecutar(
        int $productoId,
        string $nombre,
        float $rendimientoBase,
        string $rendimientoUnidad,
        ?string $observaciones,
        array $ingredientesInput,
        int $usuarioId,
    ): Receta {
        if ($this->productos->buscarPorId($productoId) === null) {
            throw ProductoInvalidoException::noExiste();
        }

        $ingredientes = $this->construyeIngredientes->ejecutar($ingredientesInput);
        $siguienteVersion = $this->recetas->ultimaVersion($productoId) + 1;

        $receta = Receta::crear(
            productoId: $productoId,
            nombre: $nombre,
            version: $siguienteVersion,
            rendimientoBase: $rendimientoBase,
            rendimientoUnidad: $rendimientoUnidad,
            observaciones: $observaciones,
            creadoPorUsuarioId: $usuarioId,
            ingredientes: $ingredientes,
        );

        return $this->recetas->guardar($receta);
    }
}
