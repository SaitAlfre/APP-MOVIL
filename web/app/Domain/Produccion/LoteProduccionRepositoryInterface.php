<?php

namespace App\Domain\Produccion;

use Illuminate\Pagination\LengthAwarePaginator;

interface LoteProduccionRepositoryInterface
{
    public function paginar(int $porPagina = 20): LengthAwarePaginator;

    public function buscarPorId(int $id): ?LoteProduccion;

    public function buscarPorCodigo(string $codigo): ?LoteProduccion;

    public function guardar(LoteProduccion $lote): LoteProduccion;
}
