<?php

namespace App\Domain\Proveedores;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProveedorRepositoryInterface
{
    public function paginar(int $porPagina = 15): LengthAwarePaginator;

    public function buscarPorId(int $id): ?Proveedor;

    public function buscarPorCodigo(string $codigo): ?Proveedor;

    public function buscarPorDni(string $dni): ?Proveedor;

    /** @return list<Proveedor> */
    public function activosPorZona(int $zonaId): array;

    public function contarActivos(): int;

    public function contarTodos(): int;

    /** Listado paginado con los filtros de la pantalla de proveedores (búsqueda libre, zona y estado). */
    public function paginarFiltrado(?string $busqueda, ?int $zonaId, ?EstadoProveedor $estado, int $porPagina = 15): LengthAwarePaginator;

    public function buscarPorUsuarioId(int $usuarioId): ?Proveedor;

    public function guardar(Proveedor $proveedor): Proveedor;
}
