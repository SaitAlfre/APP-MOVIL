<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Proveedor;
use App\Domain\Usuarios\Usuario;

final class AnalisisRegistroProveedor
{
    public function __construct(
        public readonly SituacionRegistroProveedor $situacion,
        public readonly ?Proveedor $ficha = null,
        public readonly ?Usuario $cuenta = null,
        public readonly ?Proveedor $fichaDeLaCuenta = null,
    ) {}
}
