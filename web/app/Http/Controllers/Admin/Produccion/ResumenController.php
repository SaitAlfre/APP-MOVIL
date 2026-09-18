<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Produccion\ObtenerResumenProduccionUseCase;
use App\Domain\Produccion\EstadoLoteProduccion;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ResumenController extends Controller
{
    public function index(
        ObtenerResumenProduccionUseCase $resumen,
        LoteProduccionRepositoryInterface $lotes,
        ProductoRepositoryInterface $productos,
    ): View {
        $pendientes = $lotes->paginar(5, EstadoLoteProduccion::Borrador);
        $enProceso = $lotes->paginar(5, EstadoLoteProduccion::EnProceso);

        $conProducto = fn ($paginador) => collect($paginador->items())->map(fn ($lote) => [
            'lote' => $lote,
            'producto' => $productos->buscarPorId($lote->productoId),
        ]);

        return view('admin.produccion.resumen', [
            'resumen' => $resumen->ejecutar(),
            'pendientes' => $conProducto($pendientes),
            'enProceso' => $conProducto($enProceso),
        ]);
    }
}
