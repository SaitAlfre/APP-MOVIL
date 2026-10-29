<?php

namespace Tests\Feature;

use App\Application\Liquidaciones\CalcularCastigoCalidadSemanal;
use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Application\Movil\ResolverReclamoProveedorUseCase;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class CastigoCalidadSemanalTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
        TarifaLeche::factory()->create(['precio' => 1.8, 'desde' => '2026-01-01', 'hasta' => '2026-12-31']);
    }

    private function infraccion(Proveedor $proveedor, float $tarifa, array $extra = []): InfraccionCalidad
    {
        $uuid = (string) Str::uuid();
        $analisis = AnalisisCalidad::create([
            'uuid' => $uuid, 'proveedor_id' => $proveedor->id, 'usuario_id' => $this->admin->id,
            'codigo_muestra' => 'AN-'.substr($uuid, 0, 12), 'estado' => 'RECHAZADO',
            'agua_anadida' => 4, 'alertas' => [], 'visita' => [], 'registrado_en' => '2026-10-22 14:00:00',
        ]);

        return InfraccionCalidad::create([
            'proveedor_id' => $proveedor->id, 'zona_id' => $proveedor->zona_id, 'analisis_calidad_id' => $analisis->id,
            'registrado_por' => $this->admin->id, 'estado' => 'confirmada', 'descuento_litro' => $tarifa,
            'semana_inicio' => '2026-10-19', 'semana_fin' => '2026-10-25',
            'confirmado_por' => $this->admin->id, 'confirmado_en' => now(), 'motivo' => 'Adulteración verificada en control sorpresa.',
            ...$extra,
        ]);
    }

    private function entrega(Proveedor $proveedor, string $fecha, float $litros = 10, array $extra = []): Entrega
    {
        return Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => $fecha, 'litros' => $litros, ...$extra]);
    }

    private function generar(Proveedor $proveedor, string $desde, string $hasta): Liquidacion
    {
        $liquidacion = app(GenerarLiquidacionUseCase::class)->ejecutar($proveedor->id, new DateTimeImmutable($desde), new DateTimeImmutable($hasta));

        return Liquidacion::findOrFail($liquidacion->id);
    }

    public function test_castiga_toda_la_semana_segun_fecha_peruana_sin_incluir_otras_fechas_o_entregas_anuladas(): void
    {
        $proveedor = Proveedor::factory()->create();
        $this->infraccion($proveedor, 0.3);
        $this->entrega($proveedor, '2026-10-19 04:59:00', 20);
        $this->entrega($proveedor, '2026-10-19 05:00:00', 10);
        $this->entrega($proveedor, '2026-10-26 04:59:00', 30);
        $this->entrega($proveedor, '2026-10-26 05:00:00', 20);
        $this->entrega($proveedor, '2026-10-22 14:00:00', 100, ['anulada' => true]);

        $liquidacion = $this->generar($proveedor, '2026-10-18', '2026-10-26');

        $this->assertEquals(80, $liquidacion->litros_totales);
        $this->assertEquals(12, $liquidacion->descuento_sanciones);
        $this->assertEquals(132, $liquidacion->monto_total);
        $this->assertSame(['2026-10-19', '2026-10-25'], array_column($liquidacion->detalle_castigo_calidad['dias'], 'fecha'));
    }

    public function test_varias_infracciones_aplican_solo_el_mayor_castigo_y_no_los_casos_pendientes_o_descartados(): void
    {
        $proveedor = Proveedor::factory()->create();
        $this->infraccion($proveedor, 0.2);
        $mayor = $this->infraccion($proveedor, 0.4);
        $this->infraccion($proveedor, 0.5, ['estado' => 'pendiente']);
        $this->infraccion($proveedor, 0.5, ['estado' => 'descartada']);
        $this->entrega($proveedor, '2026-10-20 14:00:00', 100);

        $liquidacion = $this->generar($proveedor, '2026-10-19', '2026-10-25');

        $this->assertEquals(40, $liquidacion->descuento_sanciones);
        $this->assertEquals(140, $liquidacion->monto_total);
        $this->assertSame([$mayor->id], $liquidacion->detalle_castigo_calidad['dias'][0]['infracciones']);
    }

    public function test_dividir_una_semana_en_periodos_no_consume_ni_duplica_el_castigo(): void
    {
        $proveedor = Proveedor::factory()->create();
        $this->infraccion($proveedor, 0.5);
        $this->entrega($proveedor, '2026-10-20 14:00:00', 10);
        $this->entrega($proveedor, '2026-10-24 14:00:00', 20);

        $primera = $this->generar($proveedor, '2026-10-19', '2026-10-22');
        $segunda = $this->generar($proveedor, '2026-10-23', '2026-10-25');

        $this->assertEquals(5, $primera->descuento_sanciones);
        $this->assertEquals(10, $segunda->descuento_sanciones);
        app(CalcularCastigoCalidadSemanal::class)->recalcularPendientes($proveedor->id, '2026-10-19', '2026-10-25');
        $this->assertEquals(5, $primera->fresh()->descuento_sanciones);
        $this->assertEquals(10, $segunda->fresh()->descuento_sanciones);
    }

    public function test_confirmacion_recalcula_pendiente_con_tarifas_originales_y_conserva_descuento_historico(): void
    {
        $proveedor = Proveedor::factory()->create();
        $this->entrega($proveedor, '2026-10-20 14:00:00', 100);
        $sancion = Sancion::create(['proveedor_id' => $proveedor->id, 'tipo' => 'calidad', 'severidad' => 'alta', 'descuento' => 10, 'motivo' => 'Sanción histórica', 'estado' => 'aprobada']);
        $liquidacion = $this->generar($proveedor, '2026-10-19', '2026-10-25');
        TarifaLeche::query()->update(['precio' => 9]);
        $this->infraccion($proveedor, 0.3);

        app(CalcularCastigoCalidadSemanal::class)->recalcularPendientes($proveedor->id, '2026-10-19', '2026-10-25');
        app(CalcularCastigoCalidadSemanal::class)->recalcularPendientes($proveedor->id, '2026-10-19', '2026-10-25');

        $this->assertEquals(140, $liquidacion->fresh()->monto_total);
        $this->assertEquals(40, $liquidacion->fresh()->descuento_sanciones);
        $this->assertEquals(30, $liquidacion->fresh()->detalle_castigo_calidad['descuento']);
        $this->assertSame($liquidacion->id, $sancion->fresh()->aplicada_liquidacion_id);
        $this->get('/admin/liquidaciones/'.$liquidacion->id)->assertOk()->assertSee('Castigo/L')->assertSee('0.30');
    }

    public function test_un_reclamo_de_litros_recalcula_el_castigo_semanal_sin_acumularlo(): void
    {
        $proveedor = Proveedor::factory()->create();
        $entrega = $this->entrega($proveedor, '2026-10-20 14:00:00', 10);
        $this->infraccion($proveedor, 0.3);
        $liquidacion = $this->generar($proveedor, '2026-10-19', '2026-10-25');
        $reclamo = ReclamoProveedor::create(['proveedor_id' => $proveedor->id, 'entrega_id' => $entrega->id, 'litros_originales' => 10, 'litros_solicitados' => 20, 'motivo' => 'Corregir litros', 'estado' => 'pendiente']);

        app(ResolverReclamoProveedorUseCase::class)->ejecutar($reclamo->id, $this->admin, 'resuelto', 'Comprobante verificado.');

        $this->assertEquals(20, $liquidacion->fresh()->litros_totales);
        $this->assertEquals(6, $liquidacion->fresh()->descuento_sanciones);
        $this->assertEquals(30, $liquidacion->fresh()->monto_total);
        app(CalcularCastigoCalidadSemanal::class)->recalcularPendientes($proveedor->id, '2026-10-19', '2026-10-25');
        $this->assertEquals(6, $liquidacion->fresh()->descuento_sanciones);
    }

    public function test_las_pagadas_se_conservan_y_recalcular_una_pagada_se_rechaza(): void
    {
        $proveedor = Proveedor::factory()->create();
        $this->entrega($proveedor, '2026-10-20 14:00:00', 10);
        $liquidacion = $this->generar($proveedor, '2026-10-19', '2026-10-25');
        $liquidacion->update(['estado' => 'pagada', 'pagada_en' => now()]);
        $this->infraccion($proveedor, 0.3);

        app(CalcularCastigoCalidadSemanal::class)->recalcularPendientes($proveedor->id, '2026-10-19', '2026-10-25');
        $this->assertEquals(18, $liquidacion->fresh()->monto_total);
        $this->expectException(RuntimeException::class);
        app(CalcularCastigoCalidadSemanal::class)->recalcular($liquidacion->fresh(), 18);
    }

    public function test_la_semana_guardada_puede_iniciar_otro_dia_y_el_descuento_no_deja_neto_negativo(): void
    {
        $proveedor = Proveedor::factory()->create();
        TarifaLeche::query()->update(['precio' => 0.3]);
        $this->infraccion($proveedor, 0.5, ['semana_inicio' => '2026-10-22', 'semana_fin' => '2026-10-28']);
        $this->entrega($proveedor, '2026-10-21 14:00:00', 10);
        $this->entrega($proveedor, '2026-10-22 14:00:00', 10);

        $liquidacion = $this->generar($proveedor, '2026-10-21', '2026-10-22');

        $this->assertEquals(5, $liquidacion->descuento_sanciones);
        $this->assertEquals(1, $liquidacion->monto_total);
        $this->assertCount(1, $liquidacion->detalle_castigo_calidad['dias']);
    }

    public function test_castigo_limitado_al_bruto_no_se_acumula_al_recalcular(): void
    {
        $proveedor = Proveedor::factory()->create();
        TarifaLeche::query()->update(['precio' => 0.3]);
        $this->infraccion($proveedor, 0.5);
        $this->entrega($proveedor, '2026-10-20 14:00:00', 10);

        $liquidacion = $this->generar($proveedor, '2026-10-19', '2026-10-25');
        app(CalcularCastigoCalidadSemanal::class)->recalcularPendientes($proveedor->id, '2026-10-19', '2026-10-25');

        $this->assertEquals(0, $liquidacion->fresh()->monto_total);
        $this->assertEquals(3, $liquidacion->fresh()->descuento_sanciones);
        $this->assertEquals(5, $liquidacion->fresh()->detalle_castigo_calidad['descuento_calculado']);
        $this->assertEquals(3, $liquidacion->fresh()->detalle_castigo_calidad['descuento']);
    }
}
