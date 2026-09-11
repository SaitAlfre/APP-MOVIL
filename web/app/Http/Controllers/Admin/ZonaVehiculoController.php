<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ZonaVehiculoController extends Controller
{
    public function index(ZonaRepositoryInterface $zonas, VehiculoRepositoryInterface $vehiculos, ProveedorRepositoryInterface $proveedores): View
    {
        $filasZonas = collect($zonas->todas())->map(fn ($zona) => [
            'zona' => $zona,
            'proveedores' => count($proveedores->activosPorZona($zona->id)),
        ]);

        return view('admin.zonas-vehiculos.index', [
            'filasZonas' => $filasZonas,
            'vehiculos' => $vehiculos->todos(),
        ]);
    }
}
