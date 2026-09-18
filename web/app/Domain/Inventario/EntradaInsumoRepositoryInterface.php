<?php

namespace App\Domain\Inventario;

use Illuminate\Pagination\LengthAwarePaginator;

interface EntradaInsumoRepositoryInterface
{
    public function paginarPorInsumo(int $insumoId, int $porPagina = 20): LengthAwarePaginator;

    public function buscarPorEntregaId(int $entregaId): ?EntradaInsumo;

    /** Registra la entrada y aplica su efecto sobre la existencia del insumo en una sola transacción. */
    public function registrar(EntradaInsumo $entrada): EntradaInsumo;

    /** @return LengthAwarePaginator<int, MovimientoInsumo> */
    public function historialPorInsumo(int $insumoId, int $porPagina = 20): LengthAwarePaginator;
}
