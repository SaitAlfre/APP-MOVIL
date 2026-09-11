<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use RuntimeException;

final class VincularUsuarioProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {}

    public function ejecutar(int $proveedorId, int $usuarioId): Proveedor
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);
        if ($proveedor === null) {
            throw new RuntimeException('El proveedor no existe.');
        }

        $usuario = $this->usuarios->buscarPorId($usuarioId);
        if ($usuario === null || ! $usuario->tieneRol(Rol::Proveedor)) {
            throw ProveedorInvalidoException::usuarioSinRolProveedor();
        }

        $vinculadoActual = $this->proveedores->buscarPorUsuarioId($usuarioId);
        if ($vinculadoActual !== null && $vinculadoActual->id !== $proveedor->id) {
            throw ProveedorInvalidoException::usuarioYaVinculado();
        }

        return $this->proveedores->guardar($proveedor->vincularUsuario($usuarioId));
    }
}
