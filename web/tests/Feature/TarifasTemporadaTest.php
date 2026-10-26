<?php

namespace Tests\Feature;

use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Application\Movil\ResolverReclamoProveedorUseCase;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarifasTemporadaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(15, 0));
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_admin_programa_precio_y_rechaza_superposicion_y_fechas_invalidas(): void
    {
        $this->post('/admin/precios-leche', ['precio' => 1.805, 'desde' => '2026-10-01', 'hasta' => '2026-10-31'])->assertSessionHasNoErrors();
        $this->post('/admin/precios-leche', ['precio' => 2, 'desde' => '2026-10-31', 'hasta' => '2026-11-30'])->assertSessionHasErrors('desde');
        $this->post('/admin/precios-leche', ['precio' => 2, 'desde' => '2026-11-01', 'hasta' => '2026-11-30'])->assertSessionHasNoErrors();
        $this->post('/admin/precios-leche', ['precio' => -1, 'desde' => '2026-12-31', 'hasta' => '2026-12-01'])->assertSessionHasErrors(['precio', 'hasta']);
        $this->assertDatabaseCount('tarifas_leche', 2);
        $this->get('/admin/precios-leche')->assertOk()->assertSee('Vigente')->assertSee('Programado');
    }

    public function test_precio_iniciado_no_se_reescribe_pero_puede_cerrar_temporada_desde_hoy(): void
    {
        $t = TarifaLeche::factory()->create(['desde' => '2026-10-01', 'hasta' => '2026-12-31']);
        $this->put('/admin/precios-leche/'.$t->id, ['precio' => 2, 'desde' => '2026-10-01', 'hasta' => '2026-12-31'])->assertSessionHasErrors('precio');
        $this->put('/admin/precios-leche/'.$t->id, ['precio' => 1.8, 'desde' => '2026-10-01', 'hasta' => '2026-10-26'])->assertSessionHasNoErrors();
        $this->post('/admin/precios-leche', ['precio' => 2, 'desde' => '2026-10-27', 'hasta' => '2026-12-31'])->assertSessionHasNoErrors();
    }

    public function test_proveedor_consulta_tarifa_sin_liquidaciones_y_no_puede_administrarla(): void
    {
        TarifaLeche::factory()->create();
        $u = Usuario::factory()->create(['roles' => ['proveedor'], 'pin_hash' => '1234']);
        Proveedor::factory()->create(['usuario_id' => $u->id]);
        $token = $this->postJson('/api/movil/sesion', ['username' => $u->username, 'pin' => '1234'])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->assertJsonCount(1, 'tarifas')->assertJsonPath('tarifas.0.precio', 1.8)->assertJsonCount(0, 'pagos');
        $this->actingAs($u, 'operador')->post('/admin/precios-leche', ['precio' => 3, 'desde' => '2027-01-01', 'hasta' => '2027-12-31'])->assertForbidden();
        $this->assertDatabaseCount('tarifas_leche', 1);
    }

    private function prepararEntregas(): Proveedor
    {
        $p = Proveedor::factory()->create();
        TarifaLeche::factory()->create(['precio' => 1.805, 'desde' => '2026-10-01', 'hasta' => '2026-10-20']);
        TarifaLeche::factory()->create(['precio' => 2, 'desde' => '2026-10-21', 'hasta' => '2026-10-31']);
        // 04:59 UTC aún corresponde al día 20 en Perú; 05:00 UTC inicia el día 21.
        Entrega::factory()->create(['proveedor_id' => $p->id, 'litros' => 20, 'registrado_en' => '2026-10-21 04:59:00']);
        Entrega::factory()->create(['proveedor_id' => $p->id, 'litros' => 30, 'registrado_en' => '2026-10-21 05:00:00']);
        Entrega::factory()->create(['proveedor_id' => $p->id, 'litros' => 90, 'registrado_en' => '2026-10-21 14:00:00', 'anulada' => true]);

        return $p;
    }

    public function test_liquidacion_usa_dos_temporadas_por_fecha_peruana_e_ignora_precio_enviado(): void
    {
        $p = $this->prepararEntregas();
        $this->post('/admin/liquidaciones', ['proveedor_id' => $p->id, 'periodo_inicio' => '2026-10-20', 'periodo_fin' => '2026-10-21', 'precio_litro' => 10])->assertSessionHasNoErrors();
        $l = Liquidacion::sole();
        $this->assertEquals(96.10, $l->monto_total);
        $this->assertEquals(50, $l->litros_totales);
        $this->assertCount(2, $l->tarifas_aplicadas);
        $this->get('/admin/liquidaciones/'.$l->id)->assertOk()->assertSee('1.805')->assertSee('2.000')->assertSee('96.10');
    }

    public function test_sin_tarifa_no_genera_liquidacion_ni_inventa_precio(): void
    {
        $p = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $p->id, 'registrado_en' => '2026-10-20 15:00:00']);
        $this->post('/admin/liquidaciones', ['proveedor_id' => $p->id, 'periodo_inicio' => '2026-10-20', 'periodo_fin' => '2026-10-20', 'precio_litro' => 2])->assertSessionHasErrors('proveedor_id');
        $this->assertDatabaseCount('liquidaciones', 0);
    }

    public function test_subtotales_diarios_suman_el_bruto_sin_perder_centimos(): void
    {
        $p = Proveedor::factory()->create();
        TarifaLeche::factory()->create(['precio' => 1.805]);
        foreach (['2026-10-20', '2026-10-21'] as $dia) {
            Entrega::factory()->create(['proveedor_id' => $p->id, 'litros' => 1, 'registrado_en' => $dia.' 15:00:00']);
        }
        $l = app(GenerarLiquidacionUseCase::class)->ejecutar($p->id, new DateTimeImmutable('2026-10-20'), new DateTimeImmutable('2026-10-21'));
        $this->assertEquals(3.62, $l->montoTotal);
        $this->assertEquals(3.62, array_sum(array_column(Liquidacion::find($l->id)->detalle_valorado, 'subtotal')));
    }

    public function test_reclamo_recalcula_con_precios_conservados_y_pago_conserva_importe(): void
    {
        $p = $this->prepararEntregas();
        $l = app(GenerarLiquidacionUseCase::class)->ejecutar($p->id, new DateTimeImmutable('2026-10-20'), new DateTimeImmutable('2026-10-21'));
        TarifaLeche::where('precio', '1.805')->update(['precio' => 9]);
        $e = Entrega::where('proveedor_id', $p->id)->orderBy('id')->first();
        $r = ReclamoProveedor::create(['proveedor_id' => $p->id, 'entrega_id' => $e->id, 'litros_originales' => 20, 'litros_solicitados' => 25, 'motivo' => 'Litros incorrectos', 'descripcion' => 'Solicita revisar la cantidad.', 'estado' => 'pendiente']);
        app(ResolverReclamoProveedorUseCase::class)->ejecutar($r->id, $this->admin, 'resuelto', 'Cantidad verificada');
        $this->assertEquals(105.13, Liquidacion::find($l->id)->monto_total);
        $this->patch('/admin/liquidaciones/'.$l->id.'/pagar')->assertSessionHasNoErrors();
        $this->assertEquals(105.13, Liquidacion::find($l->id)->monto_total);
        $this->assertCount(2, Liquidacion::find($l->id)->detalle_valorado);
    }
}
