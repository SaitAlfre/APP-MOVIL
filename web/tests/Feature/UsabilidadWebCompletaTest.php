<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent as M;
use Database\Seeders\ProduccionCasosPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsabilidadWebCompletaTest extends TestCase
{
    use RefreshDatabase;

    private M\Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = M\Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_los_modulos_y_formularios_abren_sin_manuales_ni_bloques_de_guia(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        foreach (['usuarios.index', 'usuarios.create', 'proveedores.index', 'proveedores.create', 'acopiadores.catalogo', 'jornadas.index',
            'acopiadores.jornadas.create', 'zonas-vehiculos.index', 'zonas.create', 'vehiculos.create', 'entregas.index', 'recepcion.index',
            'calidad.index', 'calidad.create', 'sanciones.index', 'produccion.index', 'produccion.producir.index', 'produccion.historial.index',
            'produccion.productos.index', 'produccion.productos.create', 'inventario.index', 'ventas.index', 'liquidaciones.index',
            'liquidaciones.create', 'tarifas.index', 'comunicados.index', 'reportes.index', 'importaciones.index', 'auditoria.index', 'configuracion.index'] as $ruta) {
            $this->get(route('admin.'.$ruta))->assertOk()->assertSee('<h1', false)
                ->assertDontSee('data-page-guide', false)->assertDontSee('Cómo hacerlo, paso a paso')->assertDontSee('Para qué sirve:');
        }
    }

    public function test_calidad_entiende_cuando_solo_puede_consultar_sin_ver_acciones_de_administrador(): void
    {
        $this->actingAs(M\Usuario::factory()->create(['roles' => ['calidad']]), 'operador');
        $respuesta = $this->get(route('admin.recepcion.index'))->assertOk()
            ->assertDontSee(route('admin.usuarios.create'), false);
        preg_match('/<main\b[^>]*>.*?<\/main>/s', $respuesta->getContent(), $contenido);
        $this->assertNotEmpty($contenido);
        $this->assertStringNotContainsString('data-modal-open', $contenido[0]);
        $this->get(route('admin.calidad.index'))->assertOk()->assertSee(route('admin.calidad.create'), false);
    }

    public function test_el_calculador_respeta_la_eleccion_de_litros_aunque_quede_una_cantidad_anterior(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        $queso = M\Producto::where('nombre', 'Queso DEMO Producción')->firstOrFail();
        $this->get(route('admin.produccion.producir.index', ['fecha' => today()->toDateString(), 'producto_id' => $queso->id,
            'cantidad_producir' => 99, 'litros_asignados' => 100, 'modo_calculo' => 'litros']))
            ->assertOk()->assertViewHas('litrosAsignadosSeleccionados', 100)->assertViewHas('unidadesEstimadas', 10)
            ->assertViewHas('puedeCrear', true);
        $this->get(route('admin.produccion.producir.index', ['fecha' => today()->toDateString(), 'producto_id' => $queso->id,
            'cantidad_producir' => 5, 'litros_asignados' => 100, 'modo_calculo' => 'unidades']))
            ->assertOk()->assertViewHas('litrosAsignadosSeleccionados', 50)->assertViewHas('unidadesEstimadas', 5);
    }

    public function test_la_falta_de_receta_y_la_falta_de_insumos_tienen_una_salida_clara(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        $yogur = M\Producto::where('nombre', 'Yogur DEMO sin azúcar')->firstOrFail();
        $this->get(route('admin.produccion.producir.index', ['fecha' => today()->toDateString(), 'producto_id' => $yogur->id,
            'cantidad_producir' => 10]))->assertOk()->assertSee('Faltan ingredientes.')
            ->assertSee(route('admin.inventario.index', ['tab' => 'materiales']), false)->assertViewHas('puedeCrear', false);
        M\Producto::query()->update(['activo' => false]);
        $this->get(route('admin.produccion.producir.index'))->assertOk()->assertSee('Primero necesitas una receta activa')
            ->assertSee(route('admin.recetas.create'), false);
    }

    public function test_error_en_nuevo_cliente_reabre_solo_su_formulario_y_conserva_lo_escrito(): void
    {
        $this->followingRedirects()->from(route('admin.ventas.index'))->post(route('admin.ventas.clientes.store'), [
            '_modal_id' => 'nuevo-cliente', 'tipo' => 'minorista', 'nombre' => 'Cliente que debo conservar', 'documento' => '',
        ])->assertOk()->assertSeeInOrder(['id="nuevo-cliente"', 'data-restore-open', 'No se guardó todavía.'], false)
            ->assertSee('value="Cliente que debo conservar"', false);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_error_al_programar_un_aviso_conserva_la_edicion_y_no_cambia_el_registro(): void
    {
        $aviso = M\Comunicado::create(['codigo' => 'COM-UX', 'titulo' => 'Título original', 'contenido' => 'Texto original',
            'audiencia' => 'todos', 'estado' => 'borrador', 'autor_id' => $this->admin->id]);
        $this->followingRedirects()->from(route('admin.comunicados.index'))->put(route('admin.comunicados.update', $aviso), [
            '_modal_id' => 'comunicado-'.$aviso->id, 'titulo' => 'Título corregido sin guardar', 'contenido' => 'Este texto debe conservarse',
            'audiencia' => 'todos', 'estado' => 'programado',
        ])->assertOk()->assertSeeInOrder(['id="comunicado-'.$aviso->id.'"', 'data-restore-open'], false)
            ->assertSee('Título corregido sin guardar')->assertSee('Este texto debe conservarse');
        $this->assertSame('Título original', $aviso->fresh()->titulo);
    }

    public function test_los_botones_explican_el_efecto_del_pago_y_del_despacho_y_los_errores_no_usan_claves_internas(): void
    {
        $this->get(route('admin.liquidaciones.index'))->assertOk()->assertSee('Liquidaciones y pagos');
        $this->get(route('admin.ventas.index'))->assertOk()->assertSee('¿Ya se entregaron los productos?');
        $this->followingRedirects()->from(route('admin.produccion.producir.index'))->post(route('admin.produccion.producir.store'), ['fecha' => today()->toDateString(), 'producto_id' => 1, 'litros_asignados' => -1])
            ->assertOk()->assertSee('litros de leche para la producción')
            ->assertDontSee('El campo litros_asignados');
    }

    public function test_sin_sector_o_vehiculo_se_indica_como_preparar_la_jornada(): void
    {
        M\Usuario::factory()->create(['roles' => ['acopiador'], 'activo' => true]);
        $this->get(route('admin.acopiadores.jornadas.create'))->assertOk()->assertSee('Falta preparar el viaje')
            ->assertSee('Registrar un sector')->assertSee('Registrar un vehículo')
            ->assertDontSee('action="'.route('admin.acopiadores.jornadas.store').'"', false);
        M\Zona::factory()->create(['activo' => true]);
        M\Vehiculo::factory()->create(['activo' => true]);
        $this->get(route('admin.acopiadores.jornadas.create'))->assertOk()->assertDontSee('Falta preparar el viaje')
            ->assertSee('action="'.route('admin.acopiadores.jornadas.store').'"', false);
    }

    public function test_sin_existencias_la_venta_regresa_al_formulario_sin_descontar_ningun_producto(): void
    {
        $cliente = M\Cliente::create(['codigo' => 'CLI-UX', 'tipo' => 'minorista', 'nombre' => 'Cliente de prueba', 'documento' => '99000001']);
        $primero = M\Producto::factory()->create(['existencia' => 10, 'precio_cliente' => 20]);
        $segundo = M\Producto::factory()->create(['existencia' => 1, 'precio_cliente' => 10]);
        $response = $this->followingRedirects()->from(route('admin.ventas.index'))->post(route('admin.ventas.store'), [
            '_modal_id' => 'nueva-venta', 'tipo_comprador' => 'cliente', 'cliente_id' => $cliente->id, 'estado' => 'completada',
            'productos' => [['producto_id' => $primero->id, 'cantidad' => 2, 'precio_unitario' => 20],
                ['producto_id' => $segundo->id, 'cantidad' => 2, 'precio_unitario' => 10]],
        ]);
        $response->assertOk()->assertSee('No alcanza '.$segundo->nombre)->assertSee('data-restore-open', false);
        preg_match('/<script type="application\/json" data-modal-values>(.*?)<\/script>/s', $response->getContent(), $coincidencias);
        $datos = json_decode($coincidencias[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($primero->id, $datos['productos'][0]['producto_id']);
        $this->assertSame(2, $datos['productos'][1]['cantidad']);
        $this->assertEquals(10, $primero->fresh()->existencia);
        $this->assertEquals(1, $segundo->fresh()->existencia);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('movimientos_producto', 0);
    }

    public function test_los_productos_terminados_no_admiten_ajustes_manuales(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 2]);
        $this->post('/admin/inventario/ajustes', ['producto_id' => $producto->id, 'operacion' => 'salida', 'cantidad' => 1, 'motivo' => 'Prueba'])->assertNotFound();
        $this->assertEquals(2, $producto->fresh()->existencia);
        $this->assertDatabaseCount('movimientos_producto', 0);
    }

    public function test_inventario_ofrece_acciones_en_cada_ingrediente_y_formularios_compactos(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        $this->get(route('admin.inventario.index', ['tab' => 'materiales']))->assertOk()
            ->assertSee('Registrar entrada de Sal DEMO Producción')->assertDontSee('Registrar salida de Sal DEMO Producción')
            ->assertSee('data-stock-editor', false)->assertSee('data-balance="1000.000"', false)
            ->assertSee('Después de la compra')->assertDontSee('1. Registrar un ingrediente');
    }
}
