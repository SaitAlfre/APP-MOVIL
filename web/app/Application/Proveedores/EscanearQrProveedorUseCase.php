<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\ProveedorQr;
use App\Domain\Proveedores\ProveedorRepositoryInterface;

final class EscanearQrProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(string $contenidoQr): ResultadoEscaneoQr
    {
        $proveedorId = ProveedorQr::extraerId($contenidoQr);

        if ($proveedorId === null) {
            return ResultadoEscaneoQr::qrInvalido();
        }

        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor === null) {
            return ResultadoEscaneoQr::proveedorNoEncontrado();
        }

        return ResultadoEscaneoQr::encontrado($proveedor);
    }
}
