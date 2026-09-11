<?php

namespace App\Http\Middleware;

use App\Application\Jornadas\ObtenerJornadaEnCursoUseCase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sin jornada abierta, el flujo del acopiador siempre vuelve a la selección de zona/vehículo. */
class EnsureJornadaAbierta
{
    public function __construct(
        private readonly ObtenerJornadaEnCursoUseCase $obtenerJornadaEnCurso,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $jornada = $this->obtenerJornadaEnCurso->ejecutar(auth('operador')->id());

        if ($jornada === null) {
            return redirect()->route('acopiador.onboarding');
        }

        $request->attributes->set('jornada', $jornada);

        return $next($request);
    }
}
