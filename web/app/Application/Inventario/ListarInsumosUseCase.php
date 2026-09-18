<?php

namespace App\Application\Inventario;

use App\Domain\Inventario\InsumoRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarInsumosUseCase
{
    public function __construct(
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    public function ejecutar(int $porPagina = 20): LengthAwarePaginator
    {
        return $this->insumos->paginar($porPagina);
    }
}
