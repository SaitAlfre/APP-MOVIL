<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;

final class CambiarEstadoProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(int $proveedorId, EstadoProveedor $estado): Proveedor
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor === null) {
            throw new \RuntimeException('El proveedor no existe.');
        }

        return $this->proveedores->guardar($proveedor->conEstado($estado));
    }
}
