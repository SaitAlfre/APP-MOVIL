<?php

namespace App\Http\Controllers\Proveedor;

use App\Application\Proveedores\ObtenerRutaAcopioUseCase;
use App\Domain\Proveedores\ProveedorQr;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Qr\GeneradorQrPng;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(ObtenerRutaAcopioUseCase $obtenerRuta, ProveedorRepositoryInterface $proveedores): View
    {
        $usuarioId = auth('operador')->id();

        return view('proveedor.panel', [
            'proveedor' => $proveedores->buscarPorUsuarioId($usuarioId),
            'ruta' => $obtenerRuta->ejecutar($usuarioId),
        ]);
    }

    public function qr(ProveedorRepositoryInterface $proveedores): Response
    {
        $proveedor = $proveedores->buscarPorUsuarioId(auth('operador')->id());
        abort_if($proveedor === null, 404);

        $resultado = GeneradorQrPng::generar(ProveedorQr::generar($proveedor->id));

        return response($resultado->getString(), 200, ['Content-Type' => $resultado->getMimeType()]);
    }
}
