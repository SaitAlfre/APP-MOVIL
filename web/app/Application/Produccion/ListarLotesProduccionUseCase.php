<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\EstadoLoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarLotesProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $porPagina = 20, ?EstadoLoteProduccion $estado = null, ?int $productoId = null): LengthAwarePaginator
    {
        return $this->lotes->paginar($porPagina, $estado, $productoId);
    }
}
