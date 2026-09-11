<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\Proveedor;

final class ResultadoEscaneoQr
{
    private function __construct(
        public readonly string $estado,
        public readonly ?Proveedor $proveedor,
    ) {}

    public static function encontrado(Proveedor $proveedor): self
    {
        return new self('encontrado', $proveedor);
    }

    public static function qrInvalido(): self
    {
        return new self('qr_invalido', null);
    }

    public static function proveedorNoEncontrado(): self
    {
        return new self('proveedor_no_encontrado', null);
    }
}
