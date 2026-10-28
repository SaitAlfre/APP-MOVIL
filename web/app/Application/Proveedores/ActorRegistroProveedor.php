<?php

namespace App\Application\Proveedores;

/** Permisos efectivos de quien registra: crear cuentas de proveedor solo lo hace un administrador oficial. */
final class ActorRegistroProveedor
{
    public function __construct(
        public readonly int $id,
        public readonly bool $esAdministradorOficial,
        public readonly bool $puedeGestionarCuentas,
        public readonly bool $puedeVincularCuentas,
    ) {}
}
