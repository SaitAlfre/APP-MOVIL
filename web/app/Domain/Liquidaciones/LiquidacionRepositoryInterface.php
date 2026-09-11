<?php

namespace App\Domain\Liquidaciones;

use Illuminate\Pagination\LengthAwarePaginator;

interface LiquidacionRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator;

    public function buscarPorId(int $id): ?Liquidacion;

    public function guardar(Liquidacion $liquidacion): Liquidacion;
}
