<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Proveedores\ProveedorQr;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;

class ProveedorQrController extends Controller
{
    public function mostrar(int $proveedor, ProveedorRepositoryInterface $proveedores): Response
    {
        $entidad = $proveedores->buscarPorId($proveedor);
        abort_if($entidad === null, 404);

        $resultado = Builder::create()
            ->writer(new PngWriter)
            ->data(ProveedorQr::generar($entidad->id))
            ->size(300)
            ->margin(10)
            ->build();

        return response($resultado->getString(), 200, ['Content-Type' => $resultado->getMimeType()]);
    }
}
