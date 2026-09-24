<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\ObtenerHistorialProduccionUseCase;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public function index(ObtenerHistorialProduccionUseCase $historial, ProductoRepositoryInterface $productos): View
    {
        $datos = $historial->ejecutar(20);
        $snapshots = LoteProduccion::whereIn('id', collect($datos['lotes']->items())->pluck('id'))->pluck('ingredientes_snapshot', 'id');

        $filas = collect($datos['lotes']->items())->map(fn ($lote) => [
            'lote' => $lote,
            'producto' => $productos->buscarPorId($lote->productoId),
            'ingredientes' => $snapshots[$lote->id] ?? [],
        ]);

        return view('admin.produccion.historial.index', [
            'litrosAcopiados' => $datos['litros_acopiados'],
            'unidadesProducidas' => $datos['unidades_producidas'],
            'eficiencia' => $datos['eficiencia_litros_por_unidad'],
            'filas' => $filas,
            'paginador' => $datos['lotes'],
        ]);
    }
}
