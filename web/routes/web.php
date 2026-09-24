<?php

use App\Http\Controllers\Admin\AcopiadorController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CalidadController;
use App\Http\Controllers\Admin\ComunicadoController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DesignSystemController;
use App\Http\Controllers\Admin\EntregaController;
use App\Http\Controllers\Admin\ImportacionController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\JornadaController as AdminJornadaController;
use App\Http\Controllers\Admin\JornadaEntregaController;
use App\Http\Controllers\Admin\LiquidacionController;
use App\Http\Controllers\Admin\Produccion\AcopioController;
use App\Http\Controllers\Admin\Produccion\HistorialController;
use App\Http\Controllers\Admin\Produccion\ProduccionController;
use App\Http\Controllers\Admin\Produccion\ProductoController;
use App\Http\Controllers\Admin\ProveedorController;
use App\Http\Controllers\Admin\ProveedorQrController;
use App\Http\Controllers\Admin\RecepcionController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\SancionController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\VehiculoController;
use App\Http\Controllers\Admin\VentaController;
use App\Http\Controllers\Admin\ZonaController;
use App\Http\Controllers\Admin\ZonaVehiculoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\TransaccionAdministrativa;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest:operador')->group(function () {
    Route::get('login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('login', [LoginController::class, 'iniciarSesion'])->middleware('throttle:login')->name('login.store');
});

Route::middleware(['auth:operador', 'cuenta.activa'])->group(function () {
    Route::post('logout', [LoginController::class, 'cerrarSesion'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware(TransaccionAdministrativa::class)->group(function () {
        Route::middleware('permiso:dashboard,ver')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        });

        Route::middleware('permiso:usuarios,ver')->group(function () {
            Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        });
        Route::middleware('permiso:usuarios,gestionar')->group(function () {
            Route::get('usuarios/nuevo', [UsuarioController::class, 'create'])->name('usuarios.create');
            Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
            Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
            Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');
            Route::post('usuarios/{usuario}/bloquear', [UsuarioController::class, 'bloquear'])->name('usuarios.bloquear');
            Route::post('usuarios/{usuario}/desbloquear', [UsuarioController::class, 'desbloquear'])->name('usuarios.desbloquear');
            Route::post('usuarios/{usuario}/restablecer-credenciales', [UsuarioController::class, 'restablecerCredenciales'])->name('usuarios.restablecer');
        });

        Route::middleware('permiso:proveedores,ver')->group(function () {
            Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
            Route::get('proveedores/{proveedor}', [ProveedorController::class, 'show'])->whereNumber('proveedor')->name('proveedores.show');
            Route::get('proveedores/{proveedor}/qr', [ProveedorQrController::class, 'mostrar'])->name('proveedores.qr');
        });
        Route::middleware('permiso:proveedores,gestionar')->group(function () {
            Route::get('proveedores/nuevo', [ProveedorController::class, 'create'])->name('proveedores.create');
            Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
            Route::get('proveedores/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
            Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
            Route::patch('proveedores/{proveedor}/estado', [ProveedorController::class, 'cambiarEstado'])->name('proveedores.estado');
            Route::post('proveedores/{proveedor}/vincular', [ProveedorController::class, 'vincularUsuario'])->name('proveedores.vincular');
            Route::post('proveedores/{proveedor}/desvincular', [ProveedorController::class, 'desvincularUsuario'])->name('proveedores.desvincular');
        });

        Route::middleware('permiso:acopiadores,ver')->group(function () {
            Route::get('acopiadores', [AdminJornadaController::class, 'index'])->name('acopiadores.index');
            Route::get('acopiadores/catalogo', [AcopiadorController::class, 'index'])->name('acopiadores.catalogo');
            Route::get('jornadas', [AdminJornadaController::class, 'index'])->name('jornadas.index');
            Route::get('acopiadores/jornadas/{jornada}', [AdminJornadaController::class, 'show'])->whereNumber('jornada')->name('acopiadores.jornadas.show');
        });
        Route::middleware('permiso:acopiadores,gestionar')->group(function () {
            Route::get('acopiadores/jornadas/nueva', [AdminJornadaController::class, 'create'])->name('acopiadores.jornadas.create');
            Route::post('acopiadores/jornadas', [AdminJornadaController::class, 'store'])->name('acopiadores.jornadas.store');
            Route::patch('acopiadores/jornadas/{jornada}/cerrar', [AdminJornadaController::class, 'cerrar'])->name('acopiadores.jornadas.cerrar');

            Route::post('acopiadores/jornadas/{jornada}/entregas', [JornadaEntregaController::class, 'store'])->name('acopiadores.entregas.store');
            Route::post('acopiadores/entregas/sumar', [JornadaEntregaController::class, 'sumar'])->name('acopiadores.entregas.sumar');
            Route::post('acopiadores/entregas/{entrega}/anular', [JornadaEntregaController::class, 'anular'])->name('acopiadores.entregas.anular');
        });

        Route::middleware('permiso:zonas_vehiculos,ver')->group(function () {
            Route::get('zonas-vehiculos', [ZonaVehiculoController::class, 'index'])->name('zonas-vehiculos.index');
        });
        Route::middleware('permiso:zonas_vehiculos,gestionar')->group(function () {
            Route::post('zonas-vehiculos/rutas', [ZonaVehiculoController::class, 'storeRuta'])->name('rutas.store');
            Route::put('zonas-vehiculos/rutas/{ruta}', [ZonaVehiculoController::class, 'updateRuta'])->name('rutas.update');
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
        });

        Route::middleware('permiso:recepcion,ver')->group(function () {
            Route::get('recepcion', [RecepcionController::class, 'index'])->name('recepcion.index');
        });
        Route::middleware('permiso:recepcion,gestionar')->group(function () {
            Route::post('recepcion/{jornada}/llegada', [RecepcionController::class, 'registrarLlegada'])->name('recepcion.llegada.store');
        });

        Route::middleware('permiso:calidad,ver')->group(function () {
            Route::get('calidad', [CalidadController::class, 'index'])->name('calidad.index');
        });
        Route::middleware('permiso:calidad,gestionar')->group(function () {
            Route::get('calidad/nuevo', [CalidadController::class, 'create'])->name('calidad.create');
            Route::post('calidad', [CalidadController::class, 'store'])->name('calidad.store');
        });

        Route::middleware('permiso:entregas,ver')->group(function () {
            Route::get('entregas', [EntregaController::class, 'index'])->name('entregas.index');
        });
        Route::middleware('permiso:entregas,gestionar')->group(function () {
            Route::post('entregas/lote', [EntregaController::class, 'storeBatch'])->name('entregas.lote.store');
        });

        Route::middleware('permiso:sanciones,ver')->group(function () {
            Route::get('sanciones', [SancionController::class, 'index'])->name('sanciones.index');
        });
        Route::middleware('permiso:sanciones,gestionar')->group(function () {
            Route::patch('sanciones/{sancion}', [SancionController::class, 'resolver'])->name('sanciones.resolver');
            Route::post('sanciones/reclamos', [SancionController::class, 'storeReclamo'])->name('sanciones.reclamos.store');
            Route::patch('sanciones/reclamos/{reclamo}', [SancionController::class, 'resolverReclamo'])->name('sanciones.reclamos.resolver');
        });

        Route::prefix('produccion')->name('produccion.')->group(function () {
            Route::middleware('permiso:produccion,ver')->group(function () {
                Route::get('/', [AcopioController::class, 'index'])->name('index');
                Route::get('producir', [ProduccionController::class, 'index'])->name('producir.index');
                Route::get('historial', [HistorialController::class, 'index'])->name('historial.index');
                Route::get('productos', [ProductoController::class, 'index'])->name('productos.index');
                Route::get('productos/{producto}', [ProductoController::class, 'show'])->whereNumber('producto')->name('productos.show');
            });
            Route::middleware('permiso:produccion,gestionar')->group(function () {
                Route::post('producir', [ProduccionController::class, 'store'])->name('producir.store');
                Route::patch('lotes/{lote}/iniciar', [ProduccionController::class, 'iniciar'])->name('lotes.iniciar');
                Route::post('lotes/{lote}/finalizar', [ProduccionController::class, 'finalizar'])->name('lotes.finalizar');
                Route::post('lotes/{lote}/cancelar', [ProduccionController::class, 'cancelar'])->name('lotes.cancelar');
                Route::get('productos/nuevo', [ProductoController::class, 'create'])->name('productos.create');
                Route::post('productos', [ProductoController::class, 'store'])->name('productos.store');
                Route::get('productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
                Route::put('productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
                Route::patch('productos/{producto}/estado', [ProductoController::class, 'cambiarEstado'])->name('productos.estado');
            });
        });

        Route::middleware('permiso:inventario,ver')->group(function () {
            Route::get('inventario', [InventarioController::class, 'index'])->name('inventario.index');
        });
        Route::middleware('permiso:inventario,gestionar')->group(function () {
            Route::post('inventario/materiales', [InventarioController::class, 'storeMaterial'])->name('inventario.materiales.store');
            Route::post('inventario/materiales/movimientos', [InventarioController::class, 'storeMovimientoMaterial'])->name('inventario.materiales.movimientos.store');
            Route::post('inventario/ajustes', [InventarioController::class, 'storeAjuste'])->name('inventario.ajustes.store');
        });

        Route::middleware('permiso:ventas,ver')->group(function () {
            Route::get('ventas', [VentaController::class, 'index'])->name('ventas.index');
        });
        Route::middleware('permiso:ventas,gestionar')->group(function () {
            Route::post('ventas', [VentaController::class, 'store'])->name('ventas.store');
            Route::patch('ventas/{venta}/estado', [VentaController::class, 'updateEstado'])->name('ventas.estado');
            Route::post('ventas/clientes', [VentaController::class, 'storeCliente'])->name('ventas.clientes.store');
        });

        Route::middleware('permiso:comunicados,ver')->group(function () {
            Route::get('comunicados', [ComunicadoController::class, 'index'])->name('comunicados.index');
        });
        Route::middleware('permiso:comunicados,gestionar')->group(function () {
            Route::post('comunicados', [ComunicadoController::class, 'store'])->name('comunicados.store');
            Route::put('comunicados/{comunicado}', [ComunicadoController::class, 'update'])->name('comunicados.update');
        });

        Route::middleware('permiso:liquidaciones,ver')->group(function () {
            Route::get('liquidaciones', [LiquidacionController::class, 'index'])->name('liquidaciones.index');
            Route::get('liquidaciones/{liquidacion}', [LiquidacionController::class, 'show'])->whereNumber('liquidacion')->name('liquidaciones.show');
        });
        Route::middleware('permiso:liquidaciones,gestionar')->group(function () {
            Route::get('liquidaciones/nueva', [LiquidacionController::class, 'create'])->name('liquidaciones.create');
            Route::post('liquidaciones', [LiquidacionController::class, 'store'])->name('liquidaciones.store');
            Route::patch('liquidaciones/{liquidacion}/pagar', [LiquidacionController::class, 'marcarPagada'])->name('liquidaciones.pagar');
        });

        Route::middleware('permiso:auditoria,ver')->group(function () {
            Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
            Route::get('auditoria/{auditoria}', [AuditoriaController::class, 'show'])->whereNumber('auditoria')->name('auditoria.show');
        });

        Route::middleware('permiso:reportes,ver')->group(function () {
            Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
            Route::get('reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
        });

        Route::middleware('permiso:importaciones,ver')->group(function () {
            Route::get('importaciones', [ImportacionController::class, 'index'])->name('importaciones.index');
            Route::get('importaciones/plantilla/{tipo}', [ImportacionController::class, 'plantilla'])->name('importaciones.plantilla');
            Route::get('importaciones/{importacion}/vista-previa', [ImportacionController::class, 'preview'])->name('importaciones.preview');
        });
        Route::middleware('permiso:importaciones,gestionar')->group(function () {
            Route::post('importaciones/validar', [ImportacionController::class, 'validar'])->name('importaciones.validar');
            Route::post('importaciones/{importacion}/confirmar', [ImportacionController::class, 'confirmar'])->name('importaciones.confirmar');
        });

        Route::middleware('permiso:configuracion,ver')->group(function () {
            Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
            Route::get('design-system', DesignSystemController::class)->name('design-system.index');
        });
        Route::middleware('permiso:configuracion,gestionar')->group(function () {
            Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        });
    });
});
