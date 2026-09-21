<?php

namespace App\Http\Controllers\Admin;

use App\Application\Reportes\ObtenerResumenGeneralUseCase;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request, ObtenerResumenGeneralUseCase $obtenerResumen, ZonaRepositoryInterface $zonas): View
    {
        [$desde, $hasta, $zonaId] = $this->filtros($request);
        $reporte = $obtenerResumen->ejecutar($desde, $hasta, $zonaId);

        return view('admin.reportes.index', array_merge($reporte, [
            'desde' => $desde,
            'hasta' => $hasta,
            'zonaId' => $zonaId,
            'zonasDisponibles' => $zonas->todas(),
        ]));
    }

    public function exportar(Request $request, ObtenerResumenGeneralUseCase $obtenerResumen): StreamedResponse
    {
        [$desde, $hasta, $zonaId] = $this->filtros($request);
        $reporte = $obtenerResumen->ejecutar($desde, $hasta, $zonaId, null);
        $nombre = "reporte-acopio-{$desde->format('Y-m-d')}-{$hasta->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($reporte) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Fecha', 'Código', 'Proveedor', 'Zona', 'Vehículo', 'Placa', 'Litros', 'Tachos'], ';');

            foreach ($reporte['entregas'] as $entrega) {
                fputcsv($salida, [
                    $entrega['fecha'],
                    $entrega['proveedor_codigo'],
                    $entrega['proveedor'],
                    $entrega['zona'],
                    $entrega['vehiculo'],
                    $entrega['placa'],
                    number_format($entrega['litros'], 2, ',', ''),
                    $entrega['tachos'],
                ], ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{DateTimeImmutable, DateTimeImmutable, int|null} */
    private function filtros(Request $request): array
    {
        $hoy = new DateTimeImmutable('today');
        $validados = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$hoy->modify('-1 year')->format('Y-m-d')],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$hoy->format('Y-m-d'), 'after_or_equal:desde'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
        ]);

        return [
            new DateTimeImmutable($validados['desde'] ?? $hoy->modify('-29 days')->format('Y-m-d')),
            new DateTimeImmutable($validados['hasta'] ?? $hoy->format('Y-m-d')),
            isset($validados['zona_id']) ? (int) $validados['zona_id'] : null,
        ];
    }
}
