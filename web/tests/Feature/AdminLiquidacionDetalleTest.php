<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha de una liquidación: desglose día a día y comprobante imprimible.
 */
class AdminLiquidacionDetalleTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
        $this->proveedor = Proveedor::factory()->create(['nombres' => 'Celestino Apaza']);
    }

    /** @param array<string, mixed> $atributos */
    private function liquidacion(array $atributos = []): Liquidacion
    {
        return Liquidacion::query()->create(array_merge([
            'proveedor_id' => $this->proveedor->id,
            'periodo_inicio' => now()->subDays(2)->toDateString(),
            'periodo_fin' => now()->toDateString(),
            'litros_totales' => 100,
            'precio_litro' => 1.5,
            'monto_total' => 150,
            'estado' => 'pendiente',
            'generada_en' => now(),
        ], $atributos));
    }

    public function test_el_detalle_agrupa_las_entregas_por_dia_dentro_del_periodo(): void
    {
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 30, 'registrado_en' => now()->subDays(2)->setTime(7, 0)]);
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 20, 'registrado_en' => now()->subDays(2)->setTime(18, 0)]);
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 50, 'registrado_en' => now()->setTime(7, 0)]);

        $liquidacion = $this->liquidacion();

        $respuesta = $this->get("/admin/liquidaciones/{$liquidacion->id}");

        $respuesta->assertOk();
        $respuesta->assertViewHas('detalleDiario', function (array $detalle) {
            // Dos días: uno con dos entregas sumadas (50 L) y otro con una (50 L).
            return count($detalle) === 2
                && abs($detalle[0]['litros'] - 50.0) < 0.01
                && $detalle[0]['entregas'] === 2
                && abs($detalle[1]['litros'] - 50.0) < 0.01
                && $detalle[1]['entregas'] === 1;
        });
        $respuesta->assertSee('Detalle por día');
        $respuesta->assertSee('S/ 150.00');
    }

    public function test_el_detalle_excluye_entregas_anuladas_y_las_de_otros_proveedores(): void
    {
        $otro = Proveedor::factory()->create();

        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 40, 'registrado_en' => now()]);
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 60, 'registrado_en' => now(), 'anulada' => true]);
        Entrega::factory()->create(['proveedor_id' => $otro->id, 'litros' => 99, 'registrado_en' => now()]);

        $liquidacion = $this->liquidacion(['litros_totales' => 40, 'monto_total' => 60]);

        $respuesta = $this->get("/admin/liquidaciones/{$liquidacion->id}");

        $respuesta->assertViewHas('detalleDiario', function (array $detalle) {
            return count($detalle) === 1 && abs($detalle[0]['litros'] - 40.0) < 0.01;
        });
    }

    public function test_el_detalle_ignora_las_entregas_fuera_del_periodo_liquidado(): void
    {
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 25, 'registrado_en' => now()]);
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 70, 'registrado_en' => now()->subDays(10)]);

        $liquidacion = $this->liquidacion(['litros_totales' => 25, 'monto_total' => 37.5]);

        $respuesta = $this->get("/admin/liquidaciones/{$liquidacion->id}");

        $respuesta->assertViewHas('detalleDiario', fn (array $detalle) => count($detalle) === 1 && abs($detalle[0]['litros'] - 25.0) < 0.01);
    }

    public function test_avisa_cuando_el_detalle_actual_no_coincide_con_los_litros_congelados(): void
    {
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 40, 'registrado_en' => now()]);

        // La liquidación se generó con 100 L, pero hoy el detalle solo suma 40.
        $liquidacion = $this->liquidacion(['litros_totales' => 100]);

        $respuesta = $this->get("/admin/liquidaciones/{$liquidacion->id}");

        $respuesta->assertOk();
        $respuesta->assertSee('El importe pagado siempre usa los litros guardados', false);
    }

    public function test_una_liquidacion_sin_entregas_en_el_periodo_no_rompe_la_ficha(): void
    {
        $liquidacion = $this->liquidacion();

        $respuesta = $this->get("/admin/liquidaciones/{$liquidacion->id}");

        $respuesta->assertOk();
        $respuesta->assertSee('No hay entregas registradas en el periodo');
    }

    public function test_una_liquidacion_pendiente_ofrece_marcarla_pagada_y_una_pagada_no(): void
    {
        $pendiente = $this->liquidacion();
        $this->get("/admin/liquidaciones/{$pendiente->id}")->assertSee('Marcar pagada');

        $pagada = $this->liquidacion(['estado' => 'pagada', 'pagada_en' => now()]);
        $respuesta = $this->get("/admin/liquidaciones/{$pagada->id}");

        $respuesta->assertSee('Marcada como pagada');
        $respuesta->assertDontSee('Pendiente de pago');
    }

    public function test_una_liquidacion_inexistente_devuelve_404(): void
    {
        $this->get('/admin/liquidaciones/999999')->assertNotFound();
    }

    public function test_la_ruta_de_alta_no_se_confunde_con_la_ficha_de_una_liquidacion(): void
    {
        $this->get('/admin/liquidaciones/nueva')->assertOk()->assertSee('Generar liquidación');
    }

    public function test_un_rol_de_consulta_ve_la_ficha_pero_no_puede_marcarla_pagada(): void
    {
        $liquidacion = $this->liquidacion();
        $consulta = Usuario::factory()->create(['roles' => ['consulta']]);
        $this->actingAs($consulta, 'operador');

        $this->get("/admin/liquidaciones/{$liquidacion->id}")->assertOk()->assertDontSee('Marcar pagada');
        $this->patch("/admin/liquidaciones/{$liquidacion->id}/pagar")->assertForbidden();
    }
}
