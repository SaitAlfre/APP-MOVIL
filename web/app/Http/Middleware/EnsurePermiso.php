<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica la matriz de config/permisos.php: `permiso:modulo,accion` (accion es "ver" o
 * "gestionar"). Esto es lo que de verdad bloquea el acceso en el servidor; las vistas solo
 * ocultan botones como cortesía visual, nunca como control real.
 */
class EnsurePermiso
{
    public function handle(Request $request, Closure $next, string $modulo, string $accion): Response
    {
        $rolesPermitidos = config("permisos.{$modulo}.{$accion}", []);
        $usuario = auth('operador')->user();

        abort_unless($usuario !== null && collect($usuario->roles ?? [])->intersect($rolesPermitidos)->isNotEmpty(), 403);

        return $next($request);
    }
}
