<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Jornadas\AbrirJornadaUseCase;
use App\Application\Jornadas\ObtenerJornadaEnCursoUseCase;
use App\Domain\Jornadas\Exceptions\ZonaOcupadaException;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function mostrar(ObtenerJornadaEnCursoUseCase $obtenerJornadaEnCurso, ZonaRepositoryInterface $zonas, VehiculoRepositoryInterface $vehiculos): View|RedirectResponse
    {
        if ($obtenerJornadaEnCurso->ejecutar(auth('operador')->id()) !== null) {
            return redirect()->route('acopiador.home');
        }

        return view('acopiador.onboarding', [
            'zonas' => $zonas->activas(),
            'vehiculos' => $vehiculos->activos(),
        ]);
    }

    public function abrir(Request $request, AbrirJornadaUseCase $abrirJornada): RedirectResponse
    {
        $request->validate([
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
        ]);

        try {
            $abrirJornada->ejecutar(auth('operador')->id(), $request->integer('zona_id'), $request->integer('vehiculo_id'));
        } catch (ZonaOcupadaException $e) {
            return back()->withErrors(['zona_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('acopiador.home');
    }
}
