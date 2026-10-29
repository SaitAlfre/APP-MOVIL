<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\EstadoProveedor;
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
        private readonly SincronizarCuentaConFichaUseCase $sincronizarCuenta,
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

        if ($proveedor->usuarioId !== null && $proveedor->usuarioId !== $usuarioId) {
            throw ProveedorInvalidoException::fichaYaVinculada();
        }

        if (trim($usuario->dni) !== trim($proveedor->dni)) {
            throw ProveedorInvalidoException::dniNoCoincideConCuenta();
        }

        $vinculado = $this->proveedores->guardar($proveedor->vincularUsuario($usuarioId));
        // Una cuenta nueva o existente adopta el estado de la ficha: no queda activa junto a una ficha suspendida o retirada.
        if ($vinculado->estado !== EstadoProveedor::Activo) {
            $this->sincronizarCuenta->ejecutar($vinculado, $vinculado->estado, null, 'Vinculada a una ficha que no está activa.', null);
        }

        return $vinculado;
    }
}
