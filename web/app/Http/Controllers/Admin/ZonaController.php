<?php

namespace App\Http\Controllers\Admin;

use App\Application\Zonas\ActualizarZonaUseCase;
use App\Application\Zonas\CambiarEstadoZonaUseCase;
use App\Application\Zonas\CrearZonaUseCase;
use App\Domain\Zonas\Exceptions\ZonaInvalidaException;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZonaController extends Controller
{
    public function create(): View
    {
        return view('admin.zonas.form', ['zona' => null]);
    }

    public function store(Request $request, CrearZonaUseCase $crear): RedirectResponse
    {
        $request->validate(['nombre' => ['required', 'string', 'max:100']]);

        try {
            $crear->ejecutar($request->string('nombre')->toString());
        } catch (ZonaInvalidaException $e) {
            return back()->withErrors(['nombre' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.zonas-vehiculos.index')->with('estado', 'Zona registrada correctamente.');
    }

    public function edit(int $zona, ZonaRepositoryInterface $zonas): View
    {
        $entidad = $zonas->buscarPorId($zona);
        abort_if($entidad === null, 404);

        return view('admin.zonas.form', ['zona' => $entidad]);
    }

    public function update(int $zona, Request $request, ActualizarZonaUseCase $actualizar): RedirectResponse
    {
        $request->validate(['nombre' => ['required', 'string', 'max:100']]);

        try {
            $actualizar->ejecutar($zona, $request->string('nombre')->toString());
        } catch (ZonaInvalidaException $e) {
            return back()->withErrors(['nombre' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.zonas-vehiculos.index')->with('estado', 'Zona actualizada correctamente.');
    }

    public function cambiarEstado(int $zona, Request $request, CambiarEstadoZonaUseCase $cambiarEstado): RedirectResponse
    {
        $cambiarEstado->ejecutar($zona, $request->boolean('activo'));

        return redirect()->route('admin.zonas-vehiculos.index')->with('estado', 'Estado de la zona actualizado.');
    }
}
