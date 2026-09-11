<?php

namespace App\Http\Controllers\Admin;

use App\Application\Entregas\ObtenerResumenJornadaUseCase;
use App\Application\Jornadas\ListarJornadasUseCase;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class JornadaController extends Controller
{
    public function index(
        ListarJornadasUseCase $listar,
        ObtenerResumenJornadaUseCase $obtenerResumen,
        UsuarioRepositoryInterface $usuarios,
        ZonaRepositoryInterface $zonas,
        VehiculoRepositoryInterface $vehiculos,
    ): View {
        $jornadas = $listar->ejecutar();

        $filas = collect($jornadas->items())->map(function ($jornada) use ($usuarios, $zonas, $vehiculos, $obtenerResumen) {
            $resumen = $obtenerResumen->ejecutar($jornada->id, 0);

            return [
                'jornada' => $jornada,
                'usuario' => $usuarios->buscarPorId($jornada->usuarioId),
                'zona' => $zonas->buscarPorId($jornada->zonaId),
                'vehiculo' => $vehiculos->buscarPorId($jornada->vehiculoId),
                'litros' => $resumen['litros'],
                'entregas' => $resumen['entregas'],
            ];
        });

        return view('admin.jornadas.index', [
            'filas' => $filas,
            'paginador' => $jornadas,
        ]);
    }
}
