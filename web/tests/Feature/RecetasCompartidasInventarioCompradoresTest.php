<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent as M;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecetasCompartidasInventarioCompradoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(M\Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    /** Una tanda de 180 L rinde 20 kg y lleva 400 g de sal: 9 L y 20 g por kg. */
    private function receta(string $nombre = 'Paria base'): M\Receta
    {
        $sal = M\Material::firstOrCreate(['nombre' => 'Sal de planta'], ['unidad' => 'g', 'existencia' => 1000]);
        $this->post(route('admin.recetas.store'), ['nombre' => $nombre, 'unidad_base' => 'kg', 'litros_tanda' => 180, 'rinde_unidades' => 20,
            'ingredientes' => [['material_id' => $sal->id, 'cantidad' => 400]]])->assertRedirect(route('admin.recetas.index'))->assertSessionHasNoErrors();

        return M\Receta::where('nombre', $nombre)->firstOrFail();
    }

    private function producto(string $nombre, int $recetaId): M\Producto
    {
        $this->post(route('admin.produccion.productos.store'), ['nombre' => $nombre, 'presentacion' => 'Presentación de prueba',
            'receta_id' => $recetaId, 'precio_cliente' => 20])->assertRedirect()->assertSessionHasNoErrors();

        return M\Producto::where('nombre', $nombre)->firstOrFail();
    }

    private function lecheDeHoy(float $litros): void
    {
        $entrega = M\Entrega::factory()->create(['litros' => $litros, 'registrado_en' => now()]);
        M\ControlCalidad::create(['entrega_id' => $entrega->id, 'usuario_id' => auth('operador')->id(), 'resultado' => 'aprobado', 'evaluado_en' => now()]);
    }

    public function test_una_receta_sirve_a_varios_productos_segun_el_peso_de_cada_unidad(): void
    {
        $receta = $this->receta();
        $this->assertEquals(9, $receta->litros_por_unidad);
        $producto = $this->producto('Paria 1 kg', $receta->id);
        $this->assertEquals(9, $producto->litros_por_unidad);
        $this->assertSame('kg', $producto->unidad_produccion);
        $this->assertDatabaseHas('receta_ingredientes', ['producto_id' => $producto->id, 'cantidad' => 20]);
        $this->post(route('admin.produccion.productos.store'), ['nombre' => 'Paria 500 g', 'presentacion' => 'Molde de 500 g', 'receta_id' => $receta->id,
            'contenido_por_unidad' => 500, 'unidad_contenido' => 'g'])->assertSessionHasNoErrors();
        $medio = M\Producto::where('nombre', 'Paria 500 g')->firstOrFail();
        $this->assertEquals(4.5, $medio->litros_por_unidad);
        $this->assertSame('unidad', $medio->unidad_produccion);
        $this->assertDatabaseHas('receta_ingredientes', ['producto_id' => $medio->id, 'cantidad' => 10]);
        $this->post(route('admin.produccion.productos.store'), ['nombre' => 'Producto sin receta', 'presentacion' => '1 kg'])->assertSessionHasErrors('receta_id');
        $this->assertDatabaseCount('productos', 2);
        $this->assertEquals(1000, M\Material::sole()->existencia);
        $this->put(route('admin.recetas.update', $receta), ['nombre' => 'Paria base', 'unidad_base' => 'kg', 'litros_tanda' => 160, 'rinde_unidades' => 20,
            'ingredientes' => [['material_id' => M\Material::sole()->id, 'cantidad' => 400]]])->assertSessionHasNoErrors();
        $this->assertEquals(8, $producto->fresh()->litros_por_unidad);
        $this->assertEquals(4, $medio->fresh()->litros_por_unidad);
        $this->get(route('admin.recetas.index'))->assertOk()->assertSee('Paria base')->assertSee('Paria 1 kg')->assertSee('Paria 500 g')->assertSee('8 L');
    }

    public function test_la_imagen_del_producto_se_sirve_sin_enlace_publico(): void
    {
        Storage::fake('public');
        $producto = $this->producto('Paria con foto', $this->receta()->id);
        $this->put(route('admin.produccion.productos.update', $producto), ['nombre' => $producto->nombre, 'presentacion' => '1 kg', 'receta_id' => $producto->receta_id,
            'imagen' => UploadedFile::fake()->image('queso.jpg', 300, 200)])->assertSessionHasNoErrors();
        $producto->refresh();
        Storage::disk('public')->assertExists($producto->imagen);
        $this->assertStringContainsString('/admin/produccion/productos/'.$producto->id.'/imagen', $producto->urlImagen());
        $this->get($producto->urlImagen())->assertOk();
        $this->get(route('admin.produccion.productos.index'))->assertOk()->assertSee($producto->urlImagen(), false);
        $this->actingAs(M\Usuario::factory()->create(['roles' => ['proveedor']]), 'operador');
        $this->get(route('admin.produccion.productos.imagen', $producto))->assertForbidden();
    }

    public function test_editar_receta_actualiza_su_producto_pero_conserva_snapshot_de_lote_existente(): void
    {
        $receta = $this->receta();
        $producto = $this->producto('Paria 1 kg', $receta->id);
        $this->lecheDeHoy(100);
        $this->get(route('admin.produccion.producir.index', ['fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'cantidad_producir' => 10]))
            ->assertOk()->assertViewHas('litrosAsignadosSeleccionados', 90)->assertViewHas('unidadesEstimadas', 10)->assertSee('200.000 g');
        $this->post(route('admin.produccion.producir.store'), ['fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 90])->assertSessionHasNoErrors();
        $lote = M\LoteProduccion::sole();
        $this->put(route('admin.recetas.update', $receta), ['nombre' => 'Paria ajustado', 'unidad_base' => 'kg', 'litros_tanda' => 160, 'rinde_unidades' => 20,
            'ingredientes' => [['material_id' => M\Material::sole()->id, 'cantidad' => 600]]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(8, $producto->fresh()->litros_por_unidad);
        $this->assertEquals(9, $lote->fresh()->litros_por_unidad_snapshot);
        $this->assertEquals(200, $lote->ingredientes_snapshot[0]['cantidad']);
        $this->patch(route('admin.produccion.lotes.iniciar', $lote))->assertSessionHasNoErrors();
        $this->assertEquals(800, M\Material::sole()->existencia);
    }

    public function test_tanda_con_rendimiento_fraccionario_no_pierde_unidades_por_redondeo(): void
    {
        $sal = M\Material::create(['nombre' => 'Sal fina', 'unidad' => 'kg', 'existencia' => 50]);
        $this->post(route('admin.recetas.store'), ['nombre' => 'Queso pasteurizado', 'unidad_base' => 'unidad', 'litros_tanda' => 200, 'rinde_unidades' => 30,
            'ingredientes' => [['material_id' => $sal->id, 'cantidad' => 4]]])->assertSessionHasNoErrors();
        $producto = $this->producto('Queso pasteurizado 1 kg', M\Receta::where('nombre', 'Queso pasteurizado')->value('id'));
        $this->lecheDeHoy(300);
        $this->get(route('admin.produccion.producir.index', ['fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'cantidad_producir' => 30]))
            ->assertViewHas('unidadesEstimadas', 30)->assertViewHas('puedeCrear', true);
        $this->post(route('admin.produccion.producir.store'), ['fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 200])->assertSessionHasNoErrors();
        $lote = M\LoteProduccion::sole();
        $this->assertSame(30, $lote->unidades_estimadas);
        $this->assertEquals(4, $lote->ingredientes_snapshot[0]['cantidad']);
    }

    public function test_solo_se_produce_con_la_leche_de_hoy(): void
    {
        $producto = $this->producto('Paria 1 kg', $this->receta()->id);
        $entrega = M\Entrega::factory()->create(['litros' => 100, 'registrado_en' => now()->subDay()]);
        M\ControlCalidad::create(['entrega_id' => $entrega->id, 'usuario_id' => auth('operador')->id(), 'resultado' => 'aprobado', 'evaluado_en' => now()->subDay()]);
        $this->get(route('admin.produccion.producir.index'))->assertOk()->assertSee('No hay leche de hoy por producir');
        $this->post(route('admin.produccion.producir.store'), ['fecha' => today()->subDay()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 90])
            ->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 0);
    }

    public function test_receta_inactiva_no_se_vincula_y_el_producto_no_queda_sin_receta(): void
    {
        $receta = $this->receta();
        $producto = $this->producto('Producto vinculado', $receta->id);
        $this->patch(route('admin.recetas.estado', $receta), ['receta_activa' => false])->assertSessionHasNoErrors();
        $this->assertFalse($producto->fresh()->receta_activa);
        $otra = $this->receta('Otra receta');
        $this->patch(route('admin.recetas.estado', $otra), ['receta_activa' => false])->assertSessionHasNoErrors();
        $this->post(route('admin.produccion.productos.store'), ['nombre' => 'Vínculo inválido', 'presentacion' => '1 kg', 'receta_id' => $otra->id])->assertSessionHasErrors('receta_id');
        $this->assertDatabaseMissing('productos', ['nombre' => 'Vínculo inválido']);
        $this->put(route('admin.produccion.productos.update', $producto), ['nombre' => $producto->nombre, 'presentacion' => '1 kg', 'receta_id' => null])->assertSessionHasErrors('receta_id');
        $this->get(route('admin.produccion.productos.create'))->assertOk()->assertDontSee('Otra receta');
        $this->assertEquals($receta->id, $producto->fresh()->receta_id);
        $this->assertDatabaseCount('recetas', 2);
    }

    public function test_filtro_de_inventario_muestra_solo_ingredientes_del_producto_elegido(): void
    {
        $receta = $this->receta();
        $producto = $this->producto('Paria mediano', $receta->id);
        M\Material::create(['nombre' => 'Azúcar del yogur', 'unidad' => 'kg', 'existencia' => 5]);
        $this->get(route('admin.inventario.index', ['tab' => 'materiales', 'producto_id' => $producto->id]))->assertOk()
            ->assertViewHas('materiales', fn ($filas) => $filas->count() === 1 && $filas->first()->nombre === 'Sal de planta')->assertSee('9.000 L');
        $sin = M\Producto::factory()->create(['nombre' => 'Sin receta']);
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
