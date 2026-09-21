<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Revalida en cada petición que la cuenta siga activa y sin bloqueo (no qué rol tiene: eso lo
 * decide el middleware `permiso` por ruta). Laravel ya vuelve a leer el usuario desde la base de
 * datos en cada request (no desde una copia en memoria), así que una cuenta desactivada o
 * bloqueada pierde el acceso de inmediato, incluso si tenía una sesión abierta desde antes.
 */
class EnsureCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = auth('operador')->user();

        if ($usuario !== null && (! $usuario->activo || $usuario->estaBloqueada()
            || (int) $request->session()->get('version_sesion.'.$usuario->id, 0) !== (int) $usuario->version_sesion)) {
            auth('operador')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'La sesión ya no es válida. Inicia sesión nuevamente o contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}
