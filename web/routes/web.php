<?php

use App\Http\Controllers\Acopiador\EntregaController;
use App\Http\Controllers\Acopiador\HomeController;
use App\Http\Controllers\Acopiador\JornadaController;
use App\Http\Controllers\Acopiador\LoteController;
use App\Http\Controllers\Acopiador\OnboardingController;
use App\Http\Controllers\Acopiador\QrController;
use App\Http\Controllers\Acopiador\SeguimientoController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\JornadaController as AdminJornadaController;
use App\Http\Controllers\Admin\ProveedorController;
use App\Http\Controllers\Admin\ProveedorQrController;
use App\Http\Controllers\Admin\ZonaVehiculoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Proveedor\PanelController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest:operador')->group(function () {
    Route::get('login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('login', [LoginController::class, 'iniciarSesion'])->name('login.store');
});

Route::middleware('auth:operador')->group(function () {
    Route::post('logout', [LoginController::class, 'cerrarSesion'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('rol:admin')->group(function () {
        Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('proveedores/nuevo', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::get('proveedores/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
        Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::patch('proveedores/{proveedor}/estado', [ProveedorController::class, 'cambiarEstado'])->name('proveedores.estado');
        Route::get('proveedores/{proveedor}/qr', [ProveedorQrController::class, 'mostrar'])->name('proveedores.qr');
        Route::post('proveedores/{proveedor}/vincular', [ProveedorController::class, 'vincularUsuario'])->name('proveedores.vincular');
        Route::post('proveedores/{proveedor}/desvincular', [ProveedorController::class, 'desvincularUsuario'])->name('proveedores.desvincular');

        Route::get('acopiadores', [AdminJornadaController::class, 'index'])->name('acopiadores.index');
        Route::get('zonas-vehiculos', [ZonaVehiculoController::class, 'index'])->name('zonas-vehiculos.index');
        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

        Route::view('calidad', 'admin.placeholder', [
            'titulo' => 'Calidad',
            'descripcion' => 'El control de calidad de las entregas todavía no está definido en el sistema.',
        ])->name('calidad.index');
        Route::view('liquidaciones', 'admin.placeholder', [
            'titulo' => 'Liquidaciones',
            'descripcion' => 'El cálculo de liquidaciones a proveedores todavía no está definido en el sistema.',
        ])->name('liquidaciones.index');
        Route::view('produccion', 'admin.placeholder', [
            'titulo' => 'Producción',
            'descripcion' => 'Los lotes de producción en planta todavía no están definidos en el sistema.',
        ])->name('produccion.index');
        Route::view('reportes', 'admin.placeholder', [
            'titulo' => 'Reportes',
            'descripcion' => 'Los reportes consolidados todavía no están definidos en el sistema.',
        ])->name('reportes.index');
    });

    Route::prefix('proveedor')->name('proveedor.')->middleware('rol:proveedor')->group(function () {
        Route::get('panel', [PanelController::class, 'index'])->name('panel');
        Route::get('qr', [PanelController::class, 'qr'])->name('qr');
    });

    Route::prefix('acopiador')->name('acopiador.')->middleware('rol:acopiador')->group(function () {
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
