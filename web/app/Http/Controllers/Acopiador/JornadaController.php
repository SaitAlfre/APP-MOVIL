<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Jornadas\CerrarJornadaUseCase;
use App\Domain\Jornadas\Jornada;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JornadaController extends Controller
{
    public function cerrar(Request $request, CerrarJornadaUseCase $cerrarJornada): RedirectResponse
    {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');

        $cerrarJornada->ejecutar($jornada->id);

        return redirect()->route('acopiador.onboarding')->with('estado', 'Jornada cerrada correctamente.');
    }
}
