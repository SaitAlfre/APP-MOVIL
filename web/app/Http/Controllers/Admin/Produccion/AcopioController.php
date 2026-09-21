<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\ListarAcopiosRecientesUseCase;
use App\Application\Produccion\ObtenerAcopioDelDiaUseCase;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcopioController extends Controller
{
    public function index(Request $request, ObtenerAcopioDelDiaUseCase $obtenerAcopio, ListarAcopiosRecientesUseCase $listarRecientes): View
    {
        $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d']]);
        $fecha = $request->filled('fecha')
            ? new DateTimeImmutable($request->string('fecha')->toString())
            : new DateTimeImmutable('today');

        return view('admin.produccion.acopio.index', [
            'fecha' => $fecha,
            'acopio' => $obtenerAcopio->ejecutar($fecha),
            'recientes' => $listarRecientes->ejecutar(),
        ]);
    }
}
