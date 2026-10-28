<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent as M;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecetasCompartidasInventarioCompradoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(M\Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    private function receta(): M\Receta
    {
        $sal = M\Material::create(['nombre' => 'Sal de planta', 'unidad' => 'g', 'existencia' => 1000]);
        $this->post(route('admin.recetas.store'), ['nombre' => 'Paria base', 'unidad_base' => 'kg', 'litros_por_unidad' => 9,
            'ingredientes' => [['material_id' => $sal->id, 'cantidad' => 20]]])->assertRedirect(route('admin.recetas.index'))->assertSessionHasNoErrors();

        return M\Receta::where('nombre', 'Paria base')->firstOrFail();
    }

    private function producto(string $nombre, ?int $recetaId = null, float $cantidad = 1): M\Producto
    {
        $this->post(route('admin.produccion.productos.store'), ['nombre' => $nombre, 'presentacion' => 'Presentación de prueba', 'unidad_produccion' => 'unidad',
            'receta_id' => $recetaId, 'cantidad_receta' => $cantidad, 'precio_cliente' => 20])->assertRedirect()->assertSessionHasNoErrors();

        return M\Producto::where('nombre', $nombre)->firstOrFail();
    }

    public function test_receta_independiente_se_vincula_opcionalmente_a_varios_productos_y_escala_cantidades(): void
    {
        $receta = $this->receta();
        $entero = $this->producto('Paria 1 kg', $receta->id);
        $medio = $this->producto('Paria 500 g', $receta->id, 0.5);
        $sin = $this->producto('Producto sin receta');
        $this->assertEquals(9, $entero->litros_por_unidad);
        $this->assertEquals(4.5, $medio->litros_por_unidad);
        $this->assertNull($sin->receta_id);
        $this->assertDatabaseHas('receta_ingredientes', ['producto_id' => $medio->id, 'cantidad' => 10]);
        $this->assertDatabaseCount('recetas', 1);
        $this->assertEquals(1000, M\Material::sole()->existencia);
        $this->get(route('admin.produccion.productos.create'))->assertOk()->assertSee('Receta (opcional)')->assertSee('Paria base');
    }

    public function test_editar_receta_actualiza_productos_pero_conserva_snapshot_de_lote_existente(): void
    {
        $receta = $this->receta();
        $producto = $this->producto('Paria 1 kg', $receta->id);
        $medio = $this->producto('Paria 500 g', $receta->id, 0.5);
        $entrega = M\Entrega::factory()->create(['litros' => 100, 'registrado_en' => now()]);
        M\ControlCalidad::create(['entrega_id' => $entrega->id, 'usuario_id' => auth('operador')->id(), 'resultado' => 'aprobado', 'evaluado_en' => now()]);
        $this->post(route('admin.produccion.producir.store'), ['fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 90])->assertSessionHasNoErrors();
        $lote = M\LoteProduccion::sole();
        $this->put(route('admin.recetas.update', $receta), ['nombre' => 'Paria ajustado', 'unidad_base' => 'kg', 'litros_por_unidad' => 8,
            'ingredientes' => [['material_id' => M\Material::sole()->id, 'cantidad' => 30]]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(8, $producto->fresh()->litros_por_unidad);
        $this->assertEquals(4, $medio->fresh()->litros_por_unidad);
        $this->assertEquals(9, $lote->fresh()->litros_por_unidad_snapshot);
        $this->assertEquals(200, $lote->ingredientes_snapshot[0]['cantidad']);
        $this->patch(route('admin.produccion.lotes.iniciar', $lote))->assertSessionHasNoErrors();
        $this->assertEquals(800, M\Material::sole()->existencia);
    }

    public function test_receta_inactiva_no_se_vincula_y_producto_puede_desvincularse_sin_borrar_receta(): void
    {
        $receta = $this->receta();
        $producto = $this->producto('Producto vinculado', $receta->id);
        $this->patch(route('admin.recetas.estado', $receta), ['receta_activa' => false])->assertSessionHasNoErrors();
        $this->assertFalse($producto->fresh()->receta_activa);
        $this->post(route('admin.produccion.productos.store'), ['nombre' => 'Vínculo inválido', 'presentacion' => '1 kg', 'unidad_produccion' => 'unidad', 'receta_id' => $receta->id])->assertSessionHasErrors('receta_id');
        $this->assertDatabaseMissing('productos', ['nombre' => 'Vínculo inválido']);
        $this->put(route('admin.produccion.productos.update', $producto), ['nombre' => $producto->nombre, 'presentacion' => '1 kg', 'unidad_produccion' => 'unidad', 'receta_id' => null])->assertSessionHasNoErrors();
        $this->assertNull($producto->fresh()->litros_por_unidad);
        $this->assertDatabaseCount('recetas', 1);
        $this->assertDatabaseMissing('receta_ingredientes', ['producto_id' => $producto->id]);
    }

    public function test_filtro_de_inventario_muestra_solo_ingredientes_del_producto_elegido(): void
    {
        $receta = $this->receta();
        $producto = $this->producto('Paria mediano', $receta->id, 0.5);
        M\Material::create(['nombre' => 'Azúcar del yogur', 'unidad' => 'kg', 'existencia' => 5]);
        $this->get(route('admin.inventario.index', ['tab' => 'materiales', 'producto_id' => $producto->id]))->assertOk()
            ->assertViewHas('materiales', fn ($filas) => $filas->count() === 1 && $filas->first()->nombre === 'Sal de planta')->assertSee('4.500 L');
        $sin = $this->producto('Sin receta');
        $this->get(route('admin.inventario.index', ['tab' => 'materiales', 'producto_id' => $sin->id]))->assertOk()->assertViewHas('materiales', fn ($filas) => $filas->isEmpty());
        $this->get(route('admin.inventario.index', ['tab' => 'materiales', 'producto_id' => 999999]))->assertSessionHasErrors('producto_id');
    }

    public function test_solo_se_admiten_entradas_de_ingredientes_y_no_hay_ajustes_manuales_de_productos(): void
    {
        $material = M\Material::create(['nombre' => 'Cuajo', 'unidad' => 'mL', 'existencia' => 0]);
        $datos = ['material_id' => $material->id, 'cantidad' => 20, 'motivo' => 'Compra de cuajo'];
        $this->post(route('admin.inventario.materiales.movimientos.store'), $datos + ['operacion' => 'entrada'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.inventario.materiales.movimientos.store'), $datos + ['operacion' => 'salida'])->assertSessionHasErrors('operacion');
        $this->post('/admin/inventario/ajustes', [])->assertNotFound();
        $this->assertEquals(20, $material->fresh()->existencia);
        $this->assertDatabaseCount('movimientos_material', 1);
        $this->get(route('admin.inventario.index', ['tab' => 'materiales']))->assertOk()->assertDontSee('− Salida')->assertDontSee('Entradas y salidas de productos')->assertSee('Nuevo ingrediente');
    }

    public function test_nuevo_ingrediente_registra_cantidad_inicial_y_movimiento_en_una_sola_operacion(): void
    {
        $this->post(route('admin.inventario.materiales.store'), ['nombre' => 'Fermento', 'unidad' => 'g', 'cantidad_inicial' => 50])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materiales', ['nombre' => 'Fermento', 'existencia' => 50]);
        $this->assertDatabaseHas('movimientos_material', ['tipo' => 'entrada', 'cantidad' => 50]);
        $this->post(route('admin.inventario.materiales.store'), ['nombre' => 'Ingrediente inválido', 'unidad' => 'g', 'cantidad_inicial' => -1])->assertSessionHasErrors('cantidad_inicial');
        $this->assertDatabaseMissing('materiales', ['nombre' => 'Ingrediente inválido']);
    }

    public function test_inicio_muestra_precio_de_hoy_en_peru_independientemente_del_filtro_del_reporte(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 02:00:00', 'UTC'));
        M\TarifaLeche::factory()->create(['precio' => 1.8, 'desde' => '2026-10-01', 'hasta' => '2026-10-27']);
        M\TarifaLeche::factory()->create(['precio' => 2, 'desde' => '2026-10-28', 'hasta' => '2026-11-30']);
        $this->get(route('admin.dashboard.index', ['desde' => '2026-10-01', 'hasta' => '2026-10-02']))->assertOk()->assertSee('Precio de la leche hoy')->assertSee('S/ 1.800')->assertViewHas('proximaTarifa', fn ($t) => (float) $t->precio === 2.0);
        $this->travelBack();
    }

    public function test_cambiar_precio_de_hoy_conserva_dias_anteriores_y_se_sincroniza_por_version(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 15:00:00', 'UTC'));
        $anterior = M\TarifaLeche::factory()->create(['precio' => 1.8, 'desde' => '2026-10-01', 'hasta' => '2026-10-31']);
        $version = (int) M\Configuracion::findOrFail('tarifas_version')->valor;
        $this->post(route('admin.tarifas.actual'), ['precio' => 2, 'hasta' => '2026-10-31'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2026-10-26', $anterior->fresh()->hasta->toDateString());
        $this->assertEquals(1.8, M\TarifaLeche::vigenteEn('2026-10-26')->precio);
        $this->assertEquals(2, M\TarifaLeche::vigenteEn()->precio);
        $this->assertEquals($version + 1, M\Configuracion::findOrFail('tarifas_version')->valor);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'tarifa_leche', 'motivo' => 'Cambio de precio desde hoy']);
        $this->travelBack();
    }

    public function test_precio_nuevo_no_puede_cruzar_programacion_futura(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 15:00:00', 'UTC'));
        $tarifa = M\TarifaLeche::factory()->create(['precio' => 1.8, 'desde' => '2026-10-01', 'hasta' => '2026-10-31']);
        M\TarifaLeche::factory()->create(['precio' => 2.1, 'desde' => '2026-11-01', 'hasta' => '2026-11-30']);
        $this->post(route('admin.tarifas.actual'), ['precio' => 2, 'hasta' => '2026-11-15'])->assertSessionHasErrors('hasta');
        $this->assertEquals(1.8, $tarifa->fresh()->precio);
        $this->assertSame('2026-10-31', $tarifa->fresh()->hasta->toDateString());
        $this->travelBack();
    }

    public function test_cambio_de_precio_no_reescribe_periodos_que_ya_tienen_liquidacion(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 15:00:00', 'UTC'));
        $tarifa = M\TarifaLeche::factory()->create(['precio' => 1.8, 'desde' => '2026-10-01', 'hasta' => '2026-10-31']);
        $proveedor = M\Proveedor::factory()->create();
        M\Liquidacion::create(['proveedor_id' => $proveedor->id, 'periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-10-27',
            'litros_totales' => 20, 'precio_litro' => 1.8, 'monto_total' => 36, 'estado' => 'pendiente', 'generada_en' => now()]);
        $this->post(route('admin.tarifas.actual'), ['precio' => 2, 'hasta' => '2026-10-31'])->assertSessionHasErrors('precio');
        $this->assertDatabaseCount('tarifas_leche', 1);
        $this->assertEquals(1.8, $tarifa->fresh()->precio);
        $this->assertEquals(36, M\Liquidacion::sole()->monto_total);
        $this->travelBack();
    }

    public function test_se_puede_configurar_precio_actual_sin_temporada_previa_y_programar_la_siguiente(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 15:00:00', 'UTC'));
        $this->post(route('admin.tarifas.actual'), ['precio' => 1.85, 'hasta' => '2026-10-31'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.tarifas.store'), ['precio' => 1.9, 'desde' => '2026-11-01', 'hasta' => '2026-11-30'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(1.85, M\TarifaLeche::vigenteEn()->precio);
        $this->assertEquals(1.9, M\TarifaLeche::vigenteEn('2026-11-01')->precio);
        $this->assertDatabaseCount('tarifas_leche', 2);
        $this->travelBack();
    }

    public function test_directorio_distingue_clientes_mayoristas_y_proveedores_sin_duplicarlos(): void
    {
        foreach ([['Cliente Huata', 'minorista', '99000001'], ['Distribuidor Arequipa', 'mayorista', '99000002']] as [$nombre, $tipo, $documento]) {
            $this->post(route('admin.compradores.store'), compact('nombre', 'tipo', 'documento') + ['ciudad' => 'Arequipa'])->assertRedirect()->assertSessionHasNoErrors();
        }
        $proveedor = M\Proveedor::factory()->create(['nombres' => 'Proveedor comprador']);
        $this->get(route('admin.compradores.index'))->assertOk()->assertSee('Cliente Huata')->assertDontSee('Distribuidor Arequipa');
        $this->get(route('admin.compradores.index', ['tipo' => 'mayoristas', 'q' => 'Arequipa']))->assertOk()->assertSee('Distribuidor Arequipa')->assertDontSee('Cliente Huata');
        $this->get(route('admin.compradores.index', ['tipo' => 'proveedores']))->assertOk()->assertSee($proveedor->nombres);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_compradores_se_editan_desactivan_y_no_entran_a_ventas_si_estan_inactivos(): void
    {
        $cliente = M\Cliente::create(['codigo' => 'CLI-A', 'tipo' => 'minorista', 'nombre' => 'Nombre anterior', 'documento' => '99000001']);
        $this->put(route('admin.compradores.update', $cliente), ['tipo' => 'mayorista', 'nombre' => 'Mayorista Lima', 'documento' => '99000001'])->assertSessionHasNoErrors();
        $this->patch(route('admin.compradores.estado', $cliente), ['activo' => false])->assertSessionHasNoErrors();
        $this->get(route('admin.compradores.index', ['tipo' => 'mayoristas', 'estado' => 'inactivos']))->assertOk()->assertSee('Mayorista Lima');
        $this->get(route('admin.ventas.index'))->assertViewHas('clientesActivos', fn ($filas) => $filas->isEmpty());
    }

    public function test_roles_operativos_no_pueden_cambiar_precios_recetas_compras_ni_compradores(): void
    {
        foreach (['calidad', 'acopiador', 'proveedor'] as $rol) {
            $this->actingAs(M\Usuario::factory()->create(['roles' => [$rol]]), 'operador');
            foreach (['admin.tarifas.actual', 'admin.compradores.store', 'admin.recetas.store', 'admin.inventario.materiales.store'] as $ruta) {
                $this->post(route($ruta), [])->assertForbidden();
            }
        }
    }
}
