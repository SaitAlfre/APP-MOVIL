<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;

/** Clasifica un DNI frente a fichas y cuentas existentes; es la única fuente de reglas de duplicados del registro de proveedores. */
final class AnalizarRegistroProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly UsuarioRepositoryInterface $usuarios,
    ) {}

    public function ejecutar(string $dni): AnalisisRegistroProveedor
    {
        $dni = trim($dni);
        $ficha = $this->proveedores->buscarPorDni($dni);
        $cuentas = $this->usuarios->buscarPorDni($dni);

        if (count($cuentas) > 1) {
            return new AnalisisRegistroProveedor(SituacionRegistroProveedor::ConflictoDni, $ficha);
        }

        $cuenta = $cuentas[0] ?? null;

        if ($ficha?->usuarioId !== null) {
            $situacion = $cuenta?->id === $ficha->usuarioId ? SituacionRegistroProveedor::YaVinculado : SituacionRegistroProveedor::ConflictoDni;

            return new AnalisisRegistroProveedor($situacion, $ficha, $cuenta);
        }

        if ($cuenta !== null && ! $cuenta->tieneRol(Rol::Proveedor)) {
            return new AnalisisRegistroProveedor(SituacionRegistroProveedor::CuentaDeOtroRol, $ficha, $cuenta);
        }

        $fichaDeLaCuenta = $cuenta !== null ? $this->proveedores->buscarPorUsuarioId($cuenta->id) : null;
        if ($fichaDeLaCuenta !== null) {
            return new AnalisisRegistroProveedor(SituacionRegistroProveedor::CuentaVinculadaAOtraFicha, $ficha, $cuenta, $fichaDeLaCuenta);
        }

        $situacion = match (true) {
            $ficha !== null && $cuenta !== null => SituacionRegistroProveedor::FichaYCuentaSinVincular,
            $ficha !== null => SituacionRegistroProveedor::FichaSinCuenta,
            $cuenta !== null => SituacionRegistroProveedor::CuentaSinFicha,
            default => SituacionRegistroProveedor::Nuevo,
        };

        return new AnalisisRegistroProveedor($situacion, $ficha, $cuenta);
    }
}
