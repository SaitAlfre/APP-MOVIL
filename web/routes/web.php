<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CalidadController;
use App\Http\Controllers\Admin\JornadaController as AdminJornadaController;
use App\Http\Controllers\Admin\JornadaEntregaController;
use App\Http\Controllers\Admin\LiquidacionController;
use App\Http\Controllers\Admin\Produccion\EntradaInsumoController;
use App\Http\Controllers\Admin\Produccion\InsumoController;
use App\Http\Controllers\Admin\Produccion\InventarioController;
use App\Http\Controllers\Admin\Produccion\LoteProduccionController;
use App\Http\Controllers\Admin\Produccion\ProductoController;
use App\Http\Controllers\Admin\Produccion\RecetaController;
use App\Http\Controllers\Admin\Produccion\ResumenController as ProduccionResumenController;
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

        Route::prefix('produccion')->name('produccion.')->group(function () {
            Route::get('/', [ProduccionResumenController::class, 'index'])->name('index');

            Route::get('inventario', [InventarioController::class, 'index'])->name('inventario.index');
            Route::get('inventario/insumos/nuevo', [InsumoController::class, 'create'])->name('inventario.insumos.create');
            Route::post('inventario/insumos', [InsumoController::class, 'store'])->name('inventario.insumos.store');
            Route::get('inventario/insumos/{insumo}', [InsumoController::class, 'show'])->name('inventario.insumos.show');
            Route::post('inventario/insumos/{insumo}/ajuste', [InsumoController::class, 'ajustar'])->name('inventario.insumos.ajustar');
            Route::get('inventario/entradas/nueva', [EntradaInsumoController::class, 'create'])->name('inventario.entradas.create');
            Route::post('inventario/entradas', [EntradaInsumoController::class, 'store'])->name('inventario.entradas.store');

            Route::get('productos', [ProductoController::class, 'index'])->name('productos.index');
            Route::get('productos/nuevo', [ProductoController::class, 'create'])->name('productos.create');
            Route::post('productos', [ProductoController::class, 'store'])->name('productos.store');
            Route::get('productos/{producto}', [ProductoController::class, 'show'])->name('productos.show');
            Route::get('productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
            Route::put('productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
            Route::patch('productos/{producto}/estado', [ProductoController::class, 'cambiarEstado'])->name('productos.estado');

            Route::get('recetas', [RecetaController::class, 'index'])->name('recetas.index');
            Route::get('recetas/nueva', [RecetaController::class, 'create'])->name('recetas.create');
            Route::post('recetas', [RecetaController::class, 'store'])->name('recetas.store');
            Route::get('recetas/{receta}/editar', [RecetaController::class, 'edit'])->name('recetas.edit');
            Route::put('recetas/{receta}', [RecetaController::class, 'update'])->name('recetas.update');
            Route::patch('recetas/{receta}/activar', [RecetaController::class, 'activar'])->name('recetas.activar');

            Route::get('lotes', [LoteProduccionController::class, 'index'])->name('lotes.index');
            Route::get('lotes/nuevo', [LoteProduccionController::class, 'create'])->name('lotes.create');
            Route::post('lotes', [LoteProduccionController::class, 'store'])->name('lotes.store');
            Route::get('lotes/{lote}', [LoteProduccionController::class, 'show'])->name('lotes.show');
            Route::patch('lotes/{lote}/iniciar', [LoteProduccionController::class, 'iniciar'])->name('lotes.iniciar');
            Route::post('lotes/{lote}/consumo', [LoteProduccionController::class, 'registrarConsumo'])->name('lotes.consumo');
            Route::patch('lotes/{lote}/finalizar', [LoteProduccionController::class, 'finalizar'])->name('lotes.finalizar');
            Route::patch('lotes/{lote}/cancelar', [LoteProduccionController::class, 'cancelar'])->name('lotes.cancelar');
        });

        Route::get('liquidaciones', [LiquidacionController::class, 'index'])->name('liquidaciones.index');
        Route::get('liquidaciones/nueva', [LiquidacionController::class, 'create'])->name('liquidaciones.create');
        Route::post('liquidaciones', [LiquidacionController::class, 'store'])->name('liquidaciones.store');
        Route::patch('liquidaciones/{liquidacion}/pagar', [LiquidacionController::class, 'marcarPagada'])->name('liquidaciones.pagar');

        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });
});
