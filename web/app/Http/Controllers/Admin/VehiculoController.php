<?php

namespace App\Http\Controllers\Admin;

use App\Application\Vehiculos\ActualizarVehiculoUseCase;
use App\Application\Vehiculos\CambiarEstadoVehiculoUseCase;
use App\Application\Vehiculos\CrearVehiculoUseCase;
use App\Domain\Vehiculos\Exceptions\VehiculoInvalidoException;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehiculoController extends Controller
{
    public function create(): View
    {
        return view('admin.vehiculos.form', ['vehiculo' => null]);
    }

    public function store(Request $request, CrearVehiculoUseCase $crear): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'placa' => ['required', 'string', 'max:20'],
        ]);

        try {
            $crear->ejecutar($request->string('nombre')->toString(), $request->string('placa')->toString());
        } catch (VehiculoInvalidoException $e) {
            return back()->withErrors(['placa' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.zonas-vehiculos.index')->with('estado', 'Vehículo registrado correctamente.');
    }

    public function edit(int $vehiculo, VehiculoRepositoryInterface $vehiculos): View
    {
        $entidad = $vehiculos->buscarPorId($vehiculo);
        abort_if($entidad === null, 404);

        return view('admin.vehiculos.form', ['vehiculo' => $entidad]);
    }

    public function update(int $vehiculo, Request $request, ActualizarVehiculoUseCase $actualizar): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'placa' => ['required', 'string', 'max:20'],
        ]);

        try {
            $actualizar->ejecutar($vehiculo, $request->string('nombre')->toString(), $request->string('placa')->toString());
        } catch (VehiculoInvalidoException $e) {
            return back()->withErrors(['placa' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.zonas-vehiculos.index')->with('estado', 'Vehículo actualizado correctamente.');
    }

    public function cambiarEstado(int $vehiculo, Request $request, CambiarEstadoVehiculoUseCase $cambiarEstado): RedirectResponse
    {
        $cambiarEstado->ejecutar($vehiculo, $request->boolean('activo'));

        return redirect()->route('admin.zonas-vehiculos.index')->with('estado', 'Estado del vehículo actualizado.');
    }
}
