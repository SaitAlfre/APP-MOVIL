<?php

namespace App\Domain\Recetas;

interface RecetaRepositoryInterface
{
    /** @return list<Receta> */
    public function listarPorProducto(int $productoId): array;

    public function buscarPorId(int $id): ?Receta;

    public function buscarActivaPorProducto(int $productoId): ?Receta;

    public function ultimaVersion(int $productoId): int;

    /** Crea una nueva versión (o la primera) de la receta, junto con sus ingredientes. */
    public function guardar(Receta $receta): Receta;

    /** Actualiza en sitio una receta ya persistida (solo válida para versiones en borrador sin uso). */
    public function actualizar(Receta $receta): Receta;

    /** Activa esta receta y archiva la anterior activa del mismo producto, en una sola transacción. */
    public function activar(int $recetaId): Receta;

    public function fueUtilizadaEnLotes(int $recetaId): bool;
}
