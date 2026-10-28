<?php

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\Usuario;
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
            if ($modulo === 'usuarios' && $request->route('usuario') !== null) {
                $destino = Usuario::find($request->route('usuario'));
                abort_if($destino?->tieneRol('admin'), 403, 'Solo un administrador puede gestionar cuentas de administradores.');
            }
            if ($request->routeIs('admin.usuarios.consultar-dni', 'admin.proveedores.consultar-dni', 'admin.proveedores.duplicados')) {
                abort_unless($usuario->puede($modulo, 'crear') || $usuario->puede($modulo, 'editar'), 403);

                return $next($request);
            }
            $accion = match ($request->method()) {
                'POST' => $request->routeIs('admin.usuarios.bloquear', 'admin.usuarios.desbloquear', 'admin.usuarios.restablecer', 'admin.proveedores.vincular', 'admin.proveedores.desvincular', 'admin.proveedores.cuenta', 'admin.produccion.lotes.*', 'admin.tarifas.actual') ? 'editar' : 'crear',
                'DELETE' => 'eliminar',
                default => $request->isMethod('GET') && ($request->routeIs('*.create') || str_ends_with($request->path(), '/nuevo')) ? 'crear' : 'editar'
            };
        }
        abort_unless($usuario !== null && $usuario->puede($modulo, $accion), 403);

        return $next($request);
    }
}
