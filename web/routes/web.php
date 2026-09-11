<?php

use App\Http\Controllers\Acopiador\EntregaController;
use App\Http\Controllers\Acopiador\HomeController;
use App\Http\Controllers\Acopiador\JornadaController;
use App\Http\Controllers\Acopiador\LoteController;
use App\Http\Controllers\Acopiador\OnboardingController;
use App\Http\Controllers\Acopiador\QrController;
use App\Http\Controllers\Acopiador\SeguimientoController;
use App\Http\Controllers\Admin\ProveedorController;
use App\Http\Controllers\Admin\ProveedorQrController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\OperadorLoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminLoginController::class, 'mostrar'])->name('login');
        Route::post('login', [AdminLoginController::class, 'iniciarSesion'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminLoginController::class, 'cerrarSesion'])->name('logout');

        Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('proveedores/nuevo', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::get('proveedores/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
        Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::patch('proveedores/{proveedor}/estado', [ProveedorController::class, 'cambiarEstado'])->name('proveedores.estado');
        Route::get('proveedores/{proveedor}/qr', [ProveedorQrController::class, 'mostrar'])->name('proveedores.qr');
    });
});

Route::prefix('acopiador')->name('acopiador.')->group(function () {
    Route::middleware('guest:operador')->group(function () {
        Route::get('login', [OperadorLoginController::class, 'mostrar'])->name('login');
        Route::post('login', [OperadorLoginController::class, 'iniciarSesion'])->name('login.store');
    });

    Route::middleware('auth:operador')->group(function () {
        Route::post('logout', [OperadorLoginController::class, 'cerrarSesion'])->name('logout');

        Route::get('onboarding', [OnboardingController::class, 'mostrar'])->name('onboarding');
        Route::post('onboarding', [OnboardingController::class, 'abrir'])->name('onboarding.store');

        Route::middleware('jornada.abierta')->group(function () {
            Route::get('inicio', [HomeController::class, 'index'])->name('home');

            Route::get('entregas/nueva', [EntregaController::class, 'create'])->name('entregas.create');
            Route::post('entregas', [EntregaController::class, 'store'])->name('entregas.store');
            Route::post('entregas/sumar', [EntregaController::class, 'sumar'])->name('entregas.sumar');
            Route::post('entregas/{entrega}/anular', [EntregaController::class, 'anular'])->name('entregas.anular');

            Route::get('lote', [LoteController::class, 'create'])->name('lote.create');
            Route::post('lote', [LoteController::class, 'store'])->name('lote.store');

            Route::post('qr/resolver', [QrController::class, 'resolver'])->name('qr.resolver');

            Route::post('seguimiento/activar', [SeguimientoController::class, 'activar'])->name('seguimiento.activar');
            Route::post('seguimiento/desactivar', [SeguimientoController::class, 'desactivar'])->name('seguimiento.desactivar');
            Route::post('seguimiento/posicion', [SeguimientoController::class, 'registrarPosicion'])->name('seguimiento.posicion');

            Route::post('jornada/cerrar', [JornadaController::class, 'cerrar'])->name('jornada.cerrar');
        });
    });
});
