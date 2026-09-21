<?php

namespace App\Domain\Liquidaciones;

use Illuminate\Pagination\LengthAwarePaginator;

interface LiquidacionRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator;

    public function buscarPorId(int $id): ?Liquidacion;

    public function guardar(Liquidacion $liquidacion): Liquidacion;

    public function contarPendientes(): int;

    /**
     * Últimas liquidaciones de un proveedor, de la más reciente a la más antigua.
     *
     * @return list<Liquidacion>
     */
    public function ultimasDelProveedor(int $proveedorId, int $limite = 10): array;
}
