<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\ProveedorRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarProveedoresUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(int $porPagina = 15): LengthAwarePaginator
    {
        return $this->proveedores->paginar($porPagina);
    }
}
