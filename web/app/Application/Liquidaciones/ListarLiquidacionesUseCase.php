<?php

namespace App\Application\Liquidaciones;

use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarLiquidacionesUseCase
{
    public function __construct(
        private readonly LiquidacionRepositoryInterface $liquidaciones,
    ) {}

    public function ejecutar(int $porPagina = 20): LengthAwarePaginator
    {
        return $this->liquidaciones->paginar($porPagina);
    }
}
