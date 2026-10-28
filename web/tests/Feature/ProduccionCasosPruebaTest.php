<?php

namespace Tests\Feature;

use App\Application\Produccion\ObtenerSaldoProduccionUseCase;
use App\Infrastructure\Persistence\Eloquent as M;
use Database\Seeders\ProduccionCasosPruebaSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProduccionCasosPruebaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(M\Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    private function saldo(): float
    {
        return app(ObtenerSaldoProduccionUseCase::class)->ejecutar(new DateTimeImmutable(today()->toDateString()))->litrosDisponibles();
    }

    public function test_sin_datos_se_explica_por_que_no_se_puede_producir(): void
    {
        $this->get(route('admin.produccion.producir.index'))->assertOk()
            ->assertSee('No hay acopios pendientes de producir');
        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => today()->toDateString(), 'producto_id' => 999, 'litros_asignados' => 100,
        ])->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 0);
    }

    public function test_demo_permite_producir_queso_y_conserva_consumos_al_repetir_la_carga(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        $this->assertSame(495.0, $this->saldo());
        $producto = M\Producto::where('nombre', 'Queso DEMO Producción')->firstOrFail();
        $this->get(route('admin.produccion.producir.index', [
            'fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'cantidad_producir' => 10,
        ]))->assertOk()->assertViewHas('puedeCrear', true)->assertViewHas('unidadesEstimadas', 10)
            ->assertViewHas('litrosAsignadosSeleccionados', 100);

        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => today()->toDateString(), 'producto_id' => $producto->id, 'litros_asignados' => 100,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.produccion.historial.index'));
        $lote = M\LoteProduccion::firstOrFail();
        $this->assertSame(395.0, $this->saldo());
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasNoErrors();
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), [
            'litros_usados' => 100, 'litros_merma_proceso' => 0, 'unidades_producidas' => 10,
        ])->assertSessionHasNoErrors();
        $this->assertSame('finalizado', $lote->fresh()->estado);
        $this->assertEquals(10, $producto->fresh()->existencia);
        $this->assertDatabaseHas('materiales', ['nombre' => 'Sal DEMO Producción', 'existencia' => 800]);
        $this->assertDatabaseHas('materiales', ['nombre' => 'Cuajo DEMO Producción', 'existencia' => 80]);

        $this->seed(ProduccionCasosPruebaSeeder::class);
        $this->assertDatabaseCount('entregas', 3);
        $this->assertDatabaseCount('recepciones_acopio', 1);
        $this->assertDatabaseCount('receta_ingredientes', 3);
        $this->assertDatabaseCount('lotes_produccion', 1);
        $this->assertEquals(10, $producto->fresh()->existencia);
        $this->assertSame(395.0, $this->saldo());
        foreach (M\Material::all() as $material) {
            $this->assertEquals($material->existencia, DB::table('movimientos_material')->where('material_id', $material->id)->sum('cantidad'));
        }
    }

    public function test_demo_bloquea_yogur_sin_azucar_y_lotes_que_superan_la_leche_disponible(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        $yogur = M\Producto::where('nombre', 'Yogur DEMO sin azúcar')->firstOrFail();
        $this->get(route('admin.produccion.producir.index', [
            'fecha' => today()->toDateString(), 'producto_id' => $yogur->id, 'cantidad_producir' => 10,
        ]))->assertOk()->assertViewHas('puedeCrear', false)->assertSee('Faltan 1.000 kg');
        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => today()->toDateString(), 'producto_id' => $yogur->id, 'litros_asignados' => 10,
        ])->assertSessionHasErrors('ingredientes');

        $queso = M\Producto::where('nombre', 'Queso DEMO Producción')->firstOrFail();
        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => today()->toDateString(), 'producto_id' => $queso->id, 'litros_asignados' => 500,
        ])->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 0);
        $this->assertDatabaseMissing('movimientos_material', ['tipo' => 'consumo']);
        $this->assertSame(495.0, $this->saldo());
    }

    public function test_leche_rechazada_o_pendiente_no_habilita_produccion(): void
    {
        $this->seed(ProduccionCasosPruebaSeeder::class);
        M\ControlCalidad::where('resultado', 'aprobado')->delete();
        $this->assertSame(0.0, $this->saldo());
        $this->get(route('admin.produccion.producir.index'))->assertOk()
            ->assertSee('No hay acopios pendientes de producir');
        $queso = M\Producto::where('nombre', 'Queso DEMO Producción')->firstOrFail();
        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => today()->toDateString(), 'producto_id' => $queso->id, 'litros_asignados' => 10,
        ])->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 0);
    }

    public function test_registrar_entrada_de_azucar_habilita_el_yogur_sin_cambiar_los_datos_existentes(): void
    {
        $existente = M\Producto::factory()->create(['nombre' => 'Receta existente', 'existencia' => 17]);
        $admin = auth('operador')->user();
        $pin = $admin->pin_hash;
        $this->seed(ProduccionCasosPruebaSeeder::class);
        $this->assertEquals(17, $existente->fresh()->existencia);
        $this->assertSame($pin, $admin->fresh()->pin_hash);
        $azucar = M\Material::where('nombre', 'Azúcar DEMO Producción')->firstOrFail();
        $yogur = M\Producto::where('nombre', 'Yogur DEMO sin azúcar')->firstOrFail();
        $this->post(route('admin.inventario.materiales.movimientos.store'), [
            'material_id' => $azucar->id, 'operacion' => 'entrada', 'cantidad' => 1, 'motivo' => 'Reposición para prueba de yogur',
        ])->assertSessionHasNoErrors();
        $this->get(route('admin.produccion.producir.index', [
            'fecha' => today()->toDateString(), 'producto_id' => $yogur->id, 'cantidad_producir' => 10,
        ]))->assertOk()->assertViewHas('puedeCrear', true);
        $this->post(route('admin.produccion.producir.store'), [
            'fecha' => today()->toDateString(), 'producto_id' => $yogur->id, 'litros_asignados' => 10,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.produccion.historial.index'));
    }
}
