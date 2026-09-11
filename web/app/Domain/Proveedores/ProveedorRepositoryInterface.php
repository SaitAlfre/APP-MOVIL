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

    public function guardar(Proveedor $proveedor): Proveedor;
}
