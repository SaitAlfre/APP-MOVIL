<?php

namespace App\Http\Controllers\Admin;

use App\Application\Reportes\ObtenerResumenGeneralUseCase;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(ObtenerResumenGeneralUseCase $obtenerResumen, ZonaRepositoryInterface $zonas): View
    {
        $resumen = $obtenerResumen->ejecutar(dias: 7);
        $zonasPorId = collect($zonas->todas())->keyBy('id');

        $litrosPorZona = collect($resumen['litrosPorZona'])->map(fn ($fila) => [
            'zona' => $zonasPorId->get($fila['zona_id']),
            'litros' => $fila['litros'],
        ]);

        return view('admin.reportes.index', [
            'litros' => $resumen['litros'],
            'entregas' => $resumen['entregas'],
            'litrosPorZona' => $litrosPorZona,
        ]);
    }
}
