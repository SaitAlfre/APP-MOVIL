<?php

namespace App\Application\Proveedores;

final class DatosProveedor
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $nombres,
        public readonly string $dni,
        public readonly ?string $telefono,
        public readonly ?string $direccion,
        public readonly int $zonaId,
        public readonly int $tachos,
        public readonly float $capacidadTachoL,
    ) {}
}
