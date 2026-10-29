<?php

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $accion = $this->esBajaLogica($request) ? 'eliminar' : match ($request->method()) {
                'POST' => $request->routeIs('admin.usuarios.acceso', 'admin.usuarios.bloquear', 'admin.usuarios.desbloquear', 'admin.usuarios.restablecer', 'admin.proveedores.vincular', 'admin.proveedores.desvincular', 'admin.proveedores.cuenta', 'admin.produccion.lotes.*', 'admin.tarifas.actual') ? 'editar' : 'crear',
                'DELETE' => 'eliminar',
                default => $request->isMethod('GET') && ($request->routeIs('*.create') || str_ends_with($request->path(), '/nuevo')) ? 'crear' : 'editar'
            };
        }
        abort_unless($usuario !== null && $usuario->puede($modulo, $accion), 403);

        return $next($request);
    }

    /**
     * Operaciones que el permiso «Eliminar» de un rol personalizado protege: dan de baja un registro sin borrarlo
     * (anular, cancelar, deshacer, retirar o desactivar) y conservan su historial y auditoría. Reactivar sigue siendo
     * «Editar». Debe coincidir con los módulos que declaran «eliminar» en config/modulos_roles.php.
     */
    private function esBajaLogica(Request $request): bool
    {
        $desactiva = fn (string $campo): bool => $request->has($campo) && ! $request->boolean($campo);

        return match ($request->route()?->getName()) {
            'admin.acopiadores.entregas.anular', 'admin.acopiadores.marcas.deshacer', 'admin.produccion.lotes.cancelar' => true,
            'admin.proveedores.update', 'admin.proveedores.estado' => $request->input('estado') === 'retirado'
                && DB::table('proveedores')->where('id', $request->route('proveedor'))->value('estado') !== 'retirado',
            'admin.ventas.estado' => $request->input('estado') === 'anulada',
            'admin.rutas.update' => $desactiva('activo') && (bool) DB::table('rutas')->where('id', $request->route('ruta'))->value('activo'),
            'admin.zonas.estado', 'admin.vehiculos.estado', 'admin.produccion.productos.estado', 'admin.compradores.estado' => $desactiva('activo'),
            'admin.recetas.estado' => $desactiva('receta_activa'),
            default => false,
        };
    }
}
