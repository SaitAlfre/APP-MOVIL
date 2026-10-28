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
        $usuario = auth('operador')->user();
        if ($accion === 'gestionar' && $usuario?->perfil_rol_id !== null) {
            $accion = match ($request->method()) {
                'POST' => $request->routeIs('admin.usuarios.bloquear', 'admin.usuarios.desbloquear', 'admin.usuarios.restablecer') ? 'editar' : 'crear', 'DELETE' => 'eliminar', default => $request->isMethod('GET') && str_ends_with($request->path(), '/nuevo') ? 'crear' : 'editar'
            };
        }
        abort_unless($usuario !== null && $usuario->puede($modulo, $accion), 403);

        return $next($request);
    }
}
