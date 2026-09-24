<?php

namespace Tests\Feature;

use App\Application\Produccion\ObtenerSaldoProduccionUseCase;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Material;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantaRecetasInventarioTest extends TestCase
{
    use RefreshDatabase;

    private Material $sal;

    private Material $cuajo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $this->sal = $this->material('Sal', 'g', 1000);
        $this->cuajo = $this->material('Cuajo', 'mL', 100);
        $entrega = Entrega::factory()->create(['litros' => 500, 'registrado_en' => now(), 'anulada' => false]);
        ControlCalidad::create([
            'entrega_id' => $entrega->id, 'usuario_id' => $entrega->usuario_id,
            'resultado' => 'aprobado', 'evaluado_en' => now(),
        ]);
    }

    private function material(string $nombre, string $unidad, float $stock): Material
    {
        $this->post(route('admin.inventario.materiales.store'), compact('nombre', 'unidad'))->assertSessionHasNoErrors();
        $material = Material::where('nombre', $nombre)->firstOrFail();
        $this->post(route('admin.inventario.materiales.movimientos.store'), [
            'material_id' => $material->id, 'operacion' => 'entrada', 'cantidad' => $stock, 'motivo' => 'Stock inicial',
        ])->assertSessionHasNoErrors();

        return $material->refresh();
    }

    private function datos(string $nombre = 'Queso fresco'): array
    {
        return [
            'nombre' => $nombre, 'presentacion' => '1 kg', 'unidad_produccion' => 'unidad', 'litros_por_unidad' => 10,
            'ingredientes' => [
                ['material_id' => $this->sal->id, 'cantidad' => 20],
                ['material_id' => $this->cuajo->id, 'cantidad' => 2],
            ],
        ];
    }

    private function receta(string $nombre = 'Queso fresco'): Producto
    {
        $this->post(route('admin.produccion.productos.store'), $this->datos($nombre))->assertSessionHasNoErrors();

        return Producto::where('nombre', $nombre)->firstOrFail();
    }

    private function lote(Producto $producto, float $litros = 100): LoteProduccion
    {
        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => now()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => $litros,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.produccion.historial.index'));

        return LoteProduccion::latest('id')->firstOrFail();
    }

    private function iniciar(LoteProduccion $lote): void
    {
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasNoErrors();
    }

    private function saldo(): float
    {
        return app(ObtenerSaldoProduccionUseCase::class)->ejecutar(new DateTimeImmutable('today'))->litrosDisponibles();
    }

    public function test_receta_reutilizable_descuenta_ingredientes_y_agrega_produccion_real_una_sola_vez(): void
    {
        $producto = $this->receta();
        $lote = $this->lote($producto);
        $this->assertEquals(1000, $this->sal->fresh()->existencia);
        $this->iniciar($lote);
        $this->assertEquals(800, $this->sal->fresh()->existencia);
        $this->assertEquals(80, $this->cuajo->fresh()->existencia);
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasErrors();
        $this->assertEquals(800, $this->sal->fresh()->existencia);

        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), [
            'litros_usados' => 90, 'litros_merma_proceso' => 2, 'unidades_producidas' => 8,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(8, $producto->fresh()->existencia);
        $this->assertEquals(408, $this->saldo());
        $this->assertEquals(800, $this->sal->fresh()->existencia);
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), ['litros_usados' => 90, 'unidades_producidas' => 8])->assertSessionHasErrors();
        $this->assertEquals(8, $producto->fresh()->existencia);

        $otro = $this->lote($producto, 50);
        $this->iniciar($otro);
        $this->assertEquals(700, $this->sal->fresh()->existencia);
        $this->assertEquals(70, $this->cuajo->fresh()->existencia);
        $this->assertDatabaseCount('receta_ingredientes', 2);
        $this->assertDatabaseHas('movimientos_material', ['lote_id' => $lote->id, 'material_id' => $this->sal->id, 'cantidad' => -200]);
    }

    public function test_no_crea_lote_si_falta_un_ingrediente_y_muestra_cantidad_faltante(): void
    {
        $producto = $this->receta();
        $this->cuajo->update(['existencia' => 1]);
        $this->from(route('admin.produccion.producir.index'))->post(route('admin.produccion.producir.store'), [
            'fecha' => now()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 100,
        ])->assertSessionHasErrors(['ingredientes']);
        $this->assertDatabaseCount('lotes_produccion', 0);
        $this->assertEquals(1000, $this->sal->fresh()->existencia);
        $this->get(route('admin.produccion.producir.index', [
            'fecha' => now()->toDateString(), 'producto_id' => $producto->id, 'cantidad_producir' => 10,
        ]))->assertOk()->assertSee('Faltan 19.000 mL')->assertViewHas('puedeCrear', false);
    }

    public function test_revalida_stock_al_iniciar_y_no_hace_descuentos_parciales(): void
    {
        $producto = $this->receta();
        $primero = $this->lote($producto);
        $segundo = $this->lote($producto);
        $this->cuajo->update(['existencia' => 30]);
        $this->iniciar($primero);
        $this->patch(route('admin.produccion.lotes.iniciar', $segundo->id))->assertSessionHasErrors('ingredientes');
        $this->assertEquals(800, $this->sal->fresh()->existencia);
        $this->assertEquals(10, $this->cuajo->fresh()->existencia);
        $this->assertSame('borrador', $segundo->fresh()->estado);
        $this->assertDatabaseMissing('movimientos_material', ['lote_id' => $segundo->id]);
    }

    public function test_editar_receta_no_cambia_ingredientes_de_lotes_existentes(): void
    {
        $producto = $this->receta();
        $lote = $this->lote($producto);
        $datos = $this->datos();
        $datos['ingredientes'][0]['cantidad'] = 40;
        $datos['litros_por_unidad'] = 5;
        $this->put(route('admin.produccion.productos.update', $producto->id), $datos)->assertSessionHasNoErrors();
        $this->iniciar($lote);
        $this->assertEquals(800, $this->sal->fresh()->existencia);
        $this->assertEquals(10, $lote->fresh()->litros_por_unidad_snapshot);
        $nuevo = $this->lote($producto, 50);
        $this->iniciar($nuevo);
        $this->assertEquals(400, $this->sal->fresh()->existencia);
        $this->get(route('admin.produccion.historial.index'))->assertOk()->assertSee('Ingredientes del lote')->assertSee('200.000');
    }

    public function test_cancelar_borrador_libera_leche_pero_cancelar_iniciado_conserva_consumos(): void
    {
        $producto = $this->receta();
        $borrador = $this->lote($producto);
        $this->post(route('admin.produccion.lotes.cancelar', $borrador->id), ['motivo' => 'Cambio de plan'])->assertSessionHasNoErrors();
        $this->assertEquals(500, $this->saldo());
        $this->assertEquals(1000, $this->sal->fresh()->existencia);
        $iniciado = $this->lote($producto);
        $this->iniciar($iniciado);
        $this->post(route('admin.produccion.lotes.cancelar', $iniciado->id), ['motivo' => 'Pérdida de la mezcla'])->assertSessionHasNoErrors();
        $this->assertEquals(400, $this->saldo());
        $this->assertEquals(800, $this->sal->fresh()->existencia);
        $this->post(route('admin.produccion.lotes.cancelar', $iniciado->id), ['motivo' => 'Repetido'])->assertSessionHasErrors();
        $this->assertEquals(800, $this->sal->fresh()->existencia);
    }

    public function test_recetas_sirven_para_yogur_y_se_calculan_por_cantidad_a_producir(): void
    {
        $azucar = $this->material('Azúcar', 'kg', 10);
        $datos = $this->datos('Yogur natural');
        $datos['litros_por_unidad'] = 1;
        $datos['ingredientes'] = [['material_id' => $azucar->id, 'cantidad' => 0.1]];
        $this->post(route('admin.produccion.productos.store'), $datos)->assertSessionHasNoErrors();
        $producto = Producto::where('nombre', 'Yogur natural')->firstOrFail();
        $this->get(route('admin.produccion.producir.index', [
            'fecha' => now()->toDateString(), 'producto_id' => $producto->id, 'cantidad_producir' => 25,
        ]))->assertOk()->assertViewHas('litrosAsignadosSeleccionados', 25)->assertViewHas('unidadesEstimadas', 25)->assertSee('2.500 kg')->assertViewHas('puedeCrear', true);
        $lote = $this->lote($producto, 25);
        $this->iniciar($lote);
        $this->assertEquals(7.5, $azucar->fresh()->existencia);
    }

    public function test_validacion_impide_ingredientes_duplicados_negativos_y_desconocidos(): void
    {
        $datos = $this->datos();
        $datos['ingredientes'][1]['material_id'] = $this->sal->id;
        $this->post(route('admin.produccion.productos.store'), $datos)->assertSessionHasErrors('ingredientes.0.material_id');
        $datos = $this->datos();
        $datos['ingredientes'][0]['cantidad'] = -1;
        $this->post(route('admin.produccion.productos.store'), $datos)->assertSessionHasErrors('ingredientes.0.cantidad');
        $datos['ingredientes'][0] = ['material_id' => 999999, 'cantidad' => 1];
        $this->post(route('admin.produccion.productos.store'), $datos)->assertSessionHasErrors('ingredientes.0.material_id');
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_materiales_validan_unidad_precision_y_evitan_stock_negativo(): void
    {
        $this->post(route('admin.inventario.materiales.store'), ['nombre' => 'Leche', 'unidad' => 'L'])->assertSessionHasErrors('nombre');
        $this->post(route('admin.inventario.materiales.store'), ['nombre' => 'Sal', 'unidad' => 'g'])->assertSessionHasErrors('nombre');
        $this->post(route('admin.inventario.materiales.store'), ['nombre' => 'Otro', 'unidad' => 'desconocida'])->assertSessionHasErrors('unidad');
        foreach ([1001, 0, -1, 0.0001] as $cantidad) {
            $this->post(route('admin.inventario.materiales.movimientos.store'), [
                'material_id' => $this->sal->id, 'operacion' => 'salida', 'cantidad' => $cantidad, 'motivo' => 'Prueba',
            ])->assertSessionHasErrors('cantidad');
        }
        $this->assertEquals(1000, $this->sal->fresh()->existencia);
        $this->assertDatabaseCount('movimientos_material', 2);
    }

    public function test_inactiva_no_permite_crear_lotes_y_datos_incompletos_no_rompen_calculo(): void
    {
        $producto = $this->receta();
        $producto->update(['activo' => false]);
        $this->post(route('admin.produccion.producir.store'), ['fecha' => now()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 100])->assertSessionHasErrors('producto_id');
        $this->get(route('admin.produccion.producir.index', ['producto_id' => $producto->id, 'litros_asignados' => 100]))->assertOk()->assertViewHas('unidadesEstimadas', null);
        $this->get(route('admin.produccion.producir.index', ['fecha' => 'invalida']))->assertSessionHasErrors('fecha');
    }

    public function test_formularios_ficha_y_pestanas_de_planta_muestran_receta_y_materiales(): void
    {
        $producto = $this->receta();
        foreach ([
            route('admin.produccion.index'), route('admin.produccion.producir.index'),
            route('admin.produccion.historial.index'), route('admin.produccion.productos.index'),
            route('admin.produccion.productos.create'), route('admin.produccion.productos.edit', $producto->id),
            route('admin.produccion.productos.show', $producto->id),
            route('admin.inventario.index'), route('admin.inventario.index', ['tab' => 'materiales']),
            route('admin.inventario.index', ['tab' => 'movimientos']), route('admin.inventario.index', ['tab' => 'movimientos-materiales']),
            route('admin.ventas.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.produccion.productos.show', $producto->id))->assertSee('Sal')->assertSee('Cuajo')->assertSee('20.000 g');
        $this->get(route('admin.inventario.index', ['tab' => 'materiales']))->assertSee('1,000.000')->assertSee('500.000 L');
        $this->get(route('admin.inventario.index', ['tab' => 'movimientos-materiales']))->assertSee('Stock inicial');
    }

    public function test_proveedor_no_puede_gestionar_materiales_recetas_ni_iniciar_produccion(): void
    {
        $producto = $this->receta();
        $lote = $this->lote($producto);
        $this->actingAs(Usuario::factory()->create(['roles' => ['proveedor']]), 'operador');
        $this->get(route('admin.inventario.index', ['tab' => 'materiales']))->assertForbidden();
        $this->post(route('admin.inventario.materiales.store'), ['nombre' => 'Otro', 'unidad' => 'g'])->assertForbidden();
        $this->post(route('admin.inventario.materiales.movimientos.store'), [])->assertForbidden();
        $this->post(route('admin.produccion.productos.store'), $this->datos())->assertForbidden();
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertForbidden();
        $this->assertEquals(1000, $this->sal->fresh()->existencia);
    }

    public function test_quitar_ingredientes_de_receta_no_borra_historial_ni_cambia_stock(): void
    {
        $producto = $this->receta();
        $datos = $this->datos();
        unset($datos['ingredientes']);
        $datos['editar_ingredientes'] = '1';
        $this->put(route('admin.produccion.productos.update', $producto->id), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('receta_ingredientes', 0);
        $this->assertEquals(1000, $this->sal->fresh()->existencia);
        $this->assertDatabaseCount('movimientos_material', 2);
    }

    public function test_no_permite_producto_terminado_sin_consumo_de_leche(): void
    {
        $lote = $this->lote($this->receta());
        $this->iniciar($lote);
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), ['litros_usados' => 0, 'unidades_producidas' => 10])->assertSessionHasErrors('unidades_producidas');
        $this->assertSame('en_proceso', $lote->fresh()->estado);
        $this->assertDatabaseCount('movimientos_producto', 0);
    }
}
