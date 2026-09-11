<?php

namespace App\Application\Produccion;

use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarLotesProduccionUseCase
{
    public function __construct(
        private readonly LoteProduccionRepositoryInterface $lotes,
    ) {}

    public function ejecutar(int $porPagina = 20): LengthAwarePaginator
    {
        return $this->lotes->paginar($porPagina);
    }
}
