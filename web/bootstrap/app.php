<?php

use App\Http\Middleware\AutenticarTokenMovil;
use App\Http\Middleware\EnsureCuentaActiva;
use App\Http\Middleware\EnsurePermiso;
use App\Http\Middleware\EnsureRol;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function () {
            $usuario = auth('operador')->user();

            return $usuario?->accesoWeb() ? '/admin/dashboard' : '/login';
        });
        $middleware->alias([
            'rol' => EnsureRol::class,
            'permiso' => EnsurePermiso::class,
            'cuenta.activa' => EnsureCuentaActiva::class,
            'movil.token' => AutenticarTokenMovil::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (QueryException $error) {
            Log::error('Error de persistencia', [
                'tipo' => $error::class,
                'codigo' => (string) $error->getCode(),
                'ruta' => request()->route()?->getName(),
            ]);

            return false;
        });
        $exceptions->render(function (QueryException $error, Request $request) {
            $mensaje = 'No se pudo completar la operación. Inténtalo nuevamente o contacta al administrador.';

            return $request->expectsJson()
                ? response()->json(['message' => $mensaje], 500)
                : response($mensaje, 500);
        });
        $exceptions->dontFlash(['pin', 'pin_hash', 'pin_confirmation', 'nuevo_pin', 'token', 'secret', 'api_key']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
