<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Proveedores\ProveedorQr;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Qr\GeneradorQrPng;
use Illuminate\Http\Response;

class ProveedorQrController extends Controller
{
    public function mostrar(int $proveedor, ProveedorRepositoryInterface $proveedores): Response
    {
        $entidad = $proveedores->buscarPorId($proveedor);
        abort_if($entidad === null, 404);

        $resultado = GeneradorQrPng::generar(ProveedorQr::generar($entidad->id));

        return response($resultado->getString(), 200, ['Content-Type' => $resultado->getMimeType()]);
    }
}
