<?php

namespace App\Http\Controllers\Admin;

use App\Application\Entregas\ObtenerResumenJornadaUseCase;
use App\Application\Jornadas\AbrirJornadaUseCase;
use App\Application\Jornadas\CerrarJornadaUseCase;
use App\Application\Jornadas\ListarJornadasUseCase;
use App\Domain\Jornadas\Exceptions\ZonaOcupadaException;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(UsuarioRepositoryInterface $usuarios, ZonaRepositoryInterface $zonas, VehiculoRepositoryInterface $vehiculos): View
    {
        return view('admin.jornadas.create', [
            'acopiadores' => $usuarios->conRol(Rol::Acopiador),
            'zonas' => $zonas->activas(),
            'vehiculos' => $vehiculos->activos(),
        ]);
    }

    public function store(Request $request, AbrirJornadaUseCase $abrirJornada): RedirectResponse
    {
        $request->validate([
            'usuario_id' => ['required', 'integer'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
        ]);

        try {
            $jornada = $abrirJornada->ejecutar($request->integer('usuario_id'), $request->integer('zona_id'), $request->integer('vehiculo_id'));
        } catch (ZonaOcupadaException $e) {
            return back()->withErrors(['zona_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.acopiadores.jornadas.show', $jornada->id);
    }

    public function show(
        int $jornada,
        JornadaRepositoryInterface $jornadas,
        ObtenerResumenJornadaUseCase $obtenerResumen,
        UsuarioRepositoryInterface $usuarios,
        ZonaRepositoryInterface $zonas,
        VehiculoRepositoryInterface $vehiculos,
        ProveedorRepositoryInterface $proveedores,
    ): View {
        $entidad = $jornadas->buscarPorId($jornada);
        abort_if($entidad === null, 404);

        $resumen = $obtenerResumen->ejecutar($entidad->id);

        return view('admin.jornadas.show', [
            'jornada' => $entidad,
            'acopiador' => $usuarios->buscarPorId($entidad->usuarioId),
            'zona' => $zonas->buscarPorId($entidad->zonaId),
            'vehiculo' => $vehiculos->buscarPorId($entidad->vehiculoId),
            'litros' => $resumen['litros'],
            'entregas' => $resumen['entregas'],
            'recientes' => $resumen['recientes'],
            'proveedores' => collect($proveedores->activosPorZona($entidad->zonaId))->keyBy('id'),
        ]);
    }

    public function cerrar(int $jornada, CerrarJornadaUseCase $cerrarJornada): RedirectResponse
    {
        $cerrarJornada->ejecutar($jornada);

        return redirect()->route('admin.acopiadores.index')->with('estado', 'Jornada cerrada correctamente.');
    }
}
