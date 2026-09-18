<?php

namespace App\Application\Productos;

use App\Domain\Productos\ProductoRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarProductosUseCase
{
    public function __construct(
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function ejecutar(int $porPagina = 20): LengthAwarePaginator
    {
        return $this->productos->paginar($porPagina);
    }
}
