<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use RuntimeException;

final class DesvincularUsuarioProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(int $proveedorId): Proveedor
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);
        if ($proveedor === null) {
            throw new RuntimeException('El proveedor no existe.');
        }

        return $this->proveedores->guardar($proveedor->desvincularUsuario());
    }
}
