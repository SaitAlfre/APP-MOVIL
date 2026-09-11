<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CalidadController;
use App\Http\Controllers\Admin\JornadaController as AdminJornadaController;
use App\Http\Controllers\Admin\JornadaEntregaController;
use App\Http\Controllers\Admin\LiquidacionController;
use App\Http\Controllers\Admin\ProduccionController;
use App\Http\Controllers\Admin\ProveedorController;
use App\Http\Controllers\Admin\ProveedorQrController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\VehiculoController;
use App\Http\Controllers\Admin\ZonaController;
use App\Http\Controllers\Admin\ZonaVehiculoController;
use App\Http\Controllers\Auth\LoginController;
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
        Route::get('acopiadores/jornadas/nueva', [AdminJornadaController::class, 'create'])->name('acopiadores.jornadas.create');
        Route::post('acopiadores/jornadas', [AdminJornadaController::class, 'store'])->name('acopiadores.jornadas.store');
        Route::get('acopiadores/jornadas/{jornada}', [AdminJornadaController::class, 'show'])->name('acopiadores.jornadas.show');
        Route::patch('acopiadores/jornadas/{jornada}/cerrar', [AdminJornadaController::class, 'cerrar'])->name('acopiadores.jornadas.cerrar');

        Route::post('acopiadores/jornadas/{jornada}/entregas', [JornadaEntregaController::class, 'store'])->name('acopiadores.entregas.store');
        Route::post('acopiadores/entregas/sumar', [JornadaEntregaController::class, 'sumar'])->name('acopiadores.entregas.sumar');
        Route::post('acopiadores/entregas/{entrega}/anular', [JornadaEntregaController::class, 'anular'])->name('acopiadores.entregas.anular');

        Route::get('zonas-vehiculos', [ZonaVehiculoController::class, 'index'])->name('zonas-vehiculos.index');
        Route::get('zonas/nueva', [ZonaController::class, 'create'])->name('zonas.create');
        Route::post('zonas', [ZonaController::class, 'store'])->name('zonas.store');
        Route::get('zonas/{zona}/editar', [ZonaController::class, 'edit'])->name('zonas.edit');
        Route::put('zonas/{zona}', [ZonaController::class, 'update'])->name('zonas.update');
        Route::patch('zonas/{zona}/estado', [ZonaController::class, 'cambiarEstado'])->name('zonas.estado');
        Route::get('vehiculos/nuevo', [VehiculoController::class, 'create'])->name('vehiculos.create');
        Route::post('vehiculos', [VehiculoController::class, 'store'])->name('vehiculos.store');
        Route::get('vehiculos/{vehiculo}/editar', [VehiculoController::class, 'edit'])->name('vehiculos.edit');
        Route::put('vehiculos/{vehiculo}', [VehiculoController::class, 'update'])->name('vehiculos.update');
        Route::patch('vehiculos/{vehiculo}/estado', [VehiculoController::class, 'cambiarEstado'])->name('vehiculos.estado');

        Route::get('calidad', [CalidadController::class, 'index'])->name('calidad.index');
        Route::get('calidad/nuevo', [CalidadController::class, 'create'])->name('calidad.create');
        Route::post('calidad', [CalidadController::class, 'store'])->name('calidad.store');

        Route::get('produccion', [ProduccionController::class, 'index'])->name('produccion.index');
        Route::get('produccion/nuevo', [ProduccionController::class, 'create'])->name('produccion.create');
        Route::post('produccion', [ProduccionController::class, 'store'])->name('produccion.store');
        Route::patch('produccion/{lote}/cerrar', [ProduccionController::class, 'cerrar'])->name('produccion.cerrar');

        Route::get('liquidaciones', [LiquidacionController::class, 'index'])->name('liquidaciones.index');
        Route::get('liquidaciones/nueva', [LiquidacionController::class, 'create'])->name('liquidaciones.create');
        Route::post('liquidaciones', [LiquidacionController::class, 'store'])->name('liquidaciones.store');
        Route::patch('liquidaciones/{liquidacion}/pagar', [LiquidacionController::class, 'marcarPagada'])->name('liquidaciones.pagar');

        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });
});
