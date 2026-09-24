<?php

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\TokenMovil;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API de la app móvil: exige `Authorization: Bearer <token>` vigente y una cuenta activa y sin bloqueo
 * (se revalida en cada petición, igual que el panel con EnsureCuentaActiva). Si se indican roles, el
 * usuario debe tener al menos uno.
 */
class AutenticarTokenMovil
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $token = $request->bearerToken();
        $registro = $token === null ? null : TokenMovil::query()
            ->with('usuario')
            ->where('token_hash', TokenMovil::hash($token))
            ->where('expira_en', '>', now())
            ->first();

        /** @var Usuario|null $usuario */
        $usuario = $registro?->usuario;

        if ($usuario === null || ! $usuario->activo || $usuario->estaBloqueada()) {
            return response()->json([
                'message' => 'La sesión del celular con el servidor no es válida. Vuelve a iniciar sesión con conexión.',
                'codigo' => 'token_invalido',
            ], 401);
        }

        if ($roles !== [] && collect($roles)->every(fn (string $rol) => ! $usuario->tieneRol($rol))) {
            return response()->json(['message' => 'Tu cuenta no tiene permiso para esta operación.', 'codigo' => 'sin_permiso'], 403);
        }

        $registro->forceFill(['ultimo_uso_en' => now()])->save();
        $request->setUserResolver(fn () => $usuario);

        return $next($request);
    }
}
