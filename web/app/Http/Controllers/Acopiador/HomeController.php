<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Entregas\ObtenerResumenJornadaUseCase;
use App\Domain\Jornadas\Jornada;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, ObtenerResumenJornadaUseCase $obtenerResumen, ProveedorRepositoryInterface $proveedores): View
    {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');
        $resumen = $obtenerResumen->ejecutar($jornada->id);

        return view('acopiador.home', [
            'jornada' => $jornada,
            'litrosHoy' => $resumen['litros'],
            'entregasHoy' => $resumen['entregas'],
            'recientes' => $resumen['recientes'],
            'proveedores' => collect($proveedores->activosPorZona($jornada->zonaId))->keyBy('id'),
        ]);
    }
}
