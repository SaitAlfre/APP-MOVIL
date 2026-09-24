<?php

use App\Http\Controllers\Api\MovilController;
use Illuminate\Support\Facades\Route;

/*
 * API de la app móvil (prefijo /api). Autenticación por token propio de cada usuario (ver
 * AutenticarTokenMovil): el celular nunca lleva credenciales administrativas.
 */
Route::prefix('movil')->name('api.movil.')->group(function () {
    Route::post('sesion', [MovilController::class, 'iniciarSesion'])->middleware('throttle:movil-sesion')->name('sesion.store');

    Route::middleware('movil.token')->group(function () {
        Route::delete('sesion', [MovilController::class, 'cerrarSesion'])->name('sesion.destroy');
    });

    Route::middleware(['movil.token:acopiador,admin', 'throttle:movil-sync'])->group(function () {
        Route::put('entregas/{uuid}', [MovilController::class, 'sincronizarEntrega'])->name('entregas.sincronizar');
    });

    Route::middleware('movil.token:proveedor')->group(function () {
        Route::get('proveedor/liquidaciones', [MovilController::class, 'liquidacionesProveedor'])->name('proveedor.liquidaciones');
    });
});
