<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\ObtenerHistorialProduccionUseCase;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public function index(ObtenerHistorialProduccionUseCase $historial, ProductoRepositoryInterface $productos): View
    {
        $datos = $historial->ejecutar(20);

        $filas = collect($datos['lotes']->items())->map(fn ($lote) => [
            'lote' => $lote,
            'producto' => $productos->buscarPorId($lote->productoId),
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
