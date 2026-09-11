<?php

use App\Http\Middleware\EnsureJornadaAbierta;
use App\Http\Middleware\EnsureRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function () {
            $usuario = auth('operador')->user();

            return match (true) {
                $usuario?->tieneRol('admin') => '/admin/proveedores',
                $usuario?->tieneRol('acopiador') => '/acopiador/inicio',
                $usuario?->tieneRol('proveedor') => '/proveedor/panel',
                default => '/login',
            };
        });
        $middleware->alias([
            'jornada.abierta' => EnsureJornadaAbierta::class,
            'rol' => EnsureRol::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
