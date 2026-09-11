<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Seguimiento\ActivarSeguimientoUseCase;
use App\Application\Seguimiento\DesactivarSeguimientoUseCase;
use App\Application\Seguimiento\RegistrarPosicionUseCase;
use App\Domain\Jornadas\Jornada;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SeguimientoController extends Controller
{
    public function activar(Request $request, ActivarSeguimientoUseCase $activar): JsonResponse
    {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');

        try {
            $activar->ejecutar($jornada->id);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['estado' => 'activo']);
    }

    public function desactivar(Request $request, DesactivarSeguimientoUseCase $desactivar): JsonResponse
    {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');
        $desactivar->ejecutar($jornada->id);

        return response()->json(['estado' => 'inactivo']);
    }

    public function registrarPosicion(Request $request, RegistrarPosicionUseCase $registrar): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'precision_m' => ['nullable', 'numeric'],
        ]);

        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');

        try {
            $registrar->ejecutar(
                $jornada->id,
                (float) $request->input('lat'),
                (float) $request->input('lng'),
                $request->has('precision_m') ? (float) $request->input('precision_m') : null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['estado' => 'activo']);
    }
}
