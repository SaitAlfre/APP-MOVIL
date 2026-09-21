<?php

namespace App\Domain\Productos;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductoRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator;

    /** @return list<Producto> */
    public function todosActivos(): array;

    public function buscarPorId(int $id): ?Producto;

    public function buscarPorNombre(string $nombre): ?Producto;

    public function guardar(Producto $producto): Producto;

    public function cambiarEstado(int $id, bool $activo): Producto;
}
