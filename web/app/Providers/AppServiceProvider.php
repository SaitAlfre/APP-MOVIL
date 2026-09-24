<?php

namespace App\Providers;

use App\Infrastructure\Persistence\Eloquent\AuditarCambiosOperativos;
use App\Infrastructure\Persistence\Eloquent\Cliente;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Importacion;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Venta;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination::ecolecta');
        Paginator::defaultSimpleView('pagination::ecolecta');

        RateLimiter::for('login', function (Request $request) {
            $username = $request->input('username');

            return [
                Limit::perMinute(30)->by('ip:'.hash('sha256', (string) $request->ip())),
                Limit::perMinute(6)->by('cuenta:'.hash('sha256', is_string($username) ? mb_strtolower($username) : '')),
            ];
        });

        // App móvil: límites con nombre propio para que el envío de entregas no consuma el cupo de inicio
        // de sesión (con `throttle:N,1` sin nombre ambos compartían la clave dominio+IP). Por IP holgado,
        // porque varios celulares de un centro de acopio suelen salir por la misma red.
        RateLimiter::for('movil-sesion', function (Request $request) {
            $username = $request->input('username');

            return [
                Limit::perMinute(30)->by('ip:'.hash('sha256', (string) $request->ip())),
                Limit::perMinute(10)->by('cuenta:'.hash('sha256', is_string($username) ? mb_strtolower($username) : '')),
            ];
        });
        // Corre después de `movil.token`: el cupo es por cuenta autenticada, no por red.
        RateLimiter::for('movil-sync', fn (Request $request) => Limit::perMinute(600)
            ->by('usuario:'.($request->user()?->getKey() ?? hash('sha256', (string) $request->ip()))));
        foreach ([
            ControlCalidad::class,
            Liquidacion::class,
            Producto::class,
            MovimientoProducto::class,
            Sancion::class,
            ReclamoProveedor::class,
            Cliente::class,
            Venta::class,
            Comunicado::class,
            Importacion::class,
            Ruta::class,
            Zona::class,
            Vehiculo::class,
            Proveedor::class,
            Jornada::class,
        ] as $modelo) {
            $modelo::observe(AuditarCambiosOperativos::class);
        }
    }
}
