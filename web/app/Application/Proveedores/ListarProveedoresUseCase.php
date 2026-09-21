<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListarProveedoresUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(
        int $porPagina = 15,
        ?string $busqueda = null,
        ?int $zonaId = null,
        ?EstadoProveedor $estado = null,
    ): LengthAwarePaginator {
        return $this->proveedores->paginarFiltrado($busqueda, $zonaId, $estado, $porPagina);
    }
}
