<?php

namespace App\Http\Controllers\Admin;

use App\Application\Entregas\ObtenerResumenJornadaUseCase;
use App\Application\Reportes\ObtenerDashboardOperativoUseCase;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ObtenerDashboardOperativoUseCase $obtenerDashboard,
        ZonaRepositoryInterface $zonas,
        UsuarioRepositoryInterface $usuarios,
        ProveedorRepositoryInterface $proveedores,
        ObtenerResumenJornadaUseCase $obtenerResumenJornada,
    ): View {
        $hoy = new DateTimeImmutable('today');

        $validados = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$hoy->modify('-1 year')->format('Y-m-d')],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$hoy->format('Y-m-d'), 'after_or_equal:desde'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'agrupacion' => ['nullable', 'string', 'in:dia,semana,mes'],
        ]);

        $desde = new DateTimeImmutable($validados['desde'] ?? $hoy->modify('-29 days')->format('Y-m-d'));
        $hasta = new DateTimeImmutable($validados['hasta'] ?? $hoy->format('Y-m-d'));
        $zonaId = isset($validados['zona_id']) ? (int) $validados['zona_id'] : null;
        $agrupacion = $validados['agrupacion'] ?? 'dia';

        $dashboard = $obtenerDashboard->ejecutar($desde, $hasta, $zonaId, $agrupacion);

        $panel = $dashboard['panel'];

        // Las jornadas abiertas y las entregas sin evaluar llegan como entidades de dominio:
        // aquí se completan con los nombres que la pantalla necesita mostrar.
        $panel['jornadas_abiertas'] = collect($panel['jornadas_abiertas'])
            ->map(function ($jornada) use ($usuarios, $zonas, $obtenerResumenJornada) {
                $resumen = $obtenerResumenJornada->ejecutar($jornada->id, 0);

                return [
                    'jornada' => $jornada,
                    'acopiador' => $usuarios->buscarPorId($jornada->usuarioId),
                    'zona' => $zonas->buscarPorId($jornada->zonaId),
                    'litros' => $resumen['litros'],
                    'entregas' => $resumen['entregas'],
                ];
            })
            ->all();

        $panel['entregas_sin_calidad'] = collect($panel['entregas_sin_calidad'])
            ->map(fn ($entrega) => [
                'entrega' => $entrega,
                'proveedor' => $proveedores->buscarPorId($entrega->proveedorId),
            ])
            ->all();

        return view('admin.dashboard.index', array_merge($dashboard['graficos'], [
            'indicadores' => $dashboard['indicadores'],
            'alertas' => $dashboard['alertas'],
            'panel' => $panel,
            'desde' => $desde,
            'hasta' => $hasta,
            'zonaId' => $zonaId,
            'agrupacion' => $agrupacion,
            'zonasDisponibles' => $zonas->todas(),
        ]));
    }
}
