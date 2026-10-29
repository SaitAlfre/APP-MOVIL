<?php

namespace Tests\Feature;

use App\Application\Calidad\CalificarEntregasConAnalisis;
use App\Domain\Calidad\EstadoAnalisis;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminZonasVehiculosCalidadProduccionLiquidacionesTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    public function test_un_admin_puede_crear_una_zona(): void
    {
        $this->comoAdmin();

        $response = $this->post('/admin/zonas', ['nombre' => 'Zona Sur']);

        $response->assertRedirect(route('admin.zonas-vehiculos.index'));
        $this->assertDatabaseHas('zonas', ['nombre' => 'Zona Sur', 'activo' => true]);
    }

    public function test_no_se_puede_crear_una_zona_con_nombre_vacio(): void
    {
        $this->comoAdmin();

        $response = $this->post('/admin/zonas', ['nombre' => '   ']);

        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseMissing('zonas', ['nombre' => '   ']);
    }

    public function test_un_admin_puede_actualizar_y_desactivar_una_zona(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create(['nombre' => 'Zona Vieja', 'activo' => true]);

        $this->put("/admin/zonas/{$zona->id}", ['nombre' => 'Zona Renombrada'])
            ->assertRedirect(route('admin.zonas-vehiculos.index'));
        $this->assertDatabaseHas('zonas', ['id' => $zona->id, 'nombre' => 'Zona Renombrada']);

        $this->patch("/admin/zonas/{$zona->id}/estado", ['activo' => '0'])
            ->assertRedirect(route('admin.zonas-vehiculos.index'));
        $this->assertDatabaseHas('zonas', ['id' => $zona->id, 'activo' => false]);
    }

    public function test_un_admin_puede_crear_un_vehiculo_y_la_placa_se_normaliza_en_mayusculas(): void
    {
        $this->comoAdmin();

        $response = $this->post('/admin/vehiculos', ['nombre' => 'Camioneta 5', 'placa' => 'abc-123']);

        $response->assertRedirect(route('admin.zonas-vehiculos.index'));
        $this->assertDatabaseHas('vehiculos', ['nombre' => 'Camioneta 5', 'placa' => 'ABC-123']);
    }

    public function test_un_admin_puede_actualizar_y_desactivar_un_vehiculo(): void
    {
        $this->comoAdmin();
        $vehiculo = Vehiculo::factory()->create(['nombre' => 'Camioneta Vieja', 'activo' => true]);

        $this->put("/admin/vehiculos/{$vehiculo->id}", ['nombre' => 'Camioneta Nueva', 'placa' => 'ZZZ-111'])
            ->assertRedirect(route('admin.zonas-vehiculos.index'));
        $this->assertDatabaseHas('vehiculos', ['id' => $vehiculo->id, 'nombre' => 'Camioneta Nueva', 'placa' => 'ZZZ-111']);

        $this->patch("/admin/vehiculos/{$vehiculo->id}/estado", ['activo' => '0'])
            ->assertRedirect(route('admin.zonas-vehiculos.index'));
        $this->assertDatabaseHas('vehiculos', ['id' => $vehiculo->id, 'activo' => false]);
    }

    /** @return array<string, mixed> */
    private function analisis(int $proveedorId, array $valores, array $extra = []): array
    {
        return [
            'proveedor_id' => $proveedorId, 'fecha' => now('America/Lima')->toDateString(), 'hora' => now('America/Lima')->format('H:i'),
            'unidad_congelacion' => '°C', 'valores' => $valores, ...$extra,
        ];
    }

    public function test_un_admin_registra_un_analisis_lactoscan_y_se_califica_como_en_la_app(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();

        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['temperatura' => '6,0', 'grasa' => '3.5', 'ph' => '6.7']))
            ->assertSessionHasNoErrors()->assertRedirect();

        $analisis = AnalisisCalidad::query()->sole();
        $this->assertSame('APROBADO', $analisis->estado->value);
        $this->assertSame(6.0, $analisis->temperatura);
        $this->assertSame([], $analisis->alertas);
        $this->assertStringStartsWith('AN-', $analisis->codigo_muestra);
        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()->assertSee('En referencia');
    }

    public function test_un_parametro_fuera_de_referencia_observa_y_el_agua_anadida_rechaza(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();

        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '2.1', 'ph' => '6.7']))->assertSessionHasNoErrors();
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '3.5', 'agua' => '4']))->assertSessionHasNoErrors();

        [$observado, $rechazado] = AnalisisCalidad::query()->orderBy('id')->get()->all();
        $this->assertSame('OBSERVADO', $observado->estado->value);
        $this->assertSame(['Grasa: 2.1 · Referencia: 3.0 a 6.0 %'], $observado->alertas);
        $this->assertSame('RECHAZADO', $rechazado->estado->value);
    }

    public function test_un_analisis_sin_resultados_o_con_valores_invalidos_no_se_guarda(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();

        $this->post('/admin/calidad', $this->analisis($proveedor->id, []))->assertSessionHasErrors('valores');
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => 'abc']))->assertSessionHasErrors('valores.grasa');
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '-2']))->assertSessionHasErrors('valores.grasa');
        $this->assertDatabaseCount('analisis_calidad', 0);
    }

    public function test_el_analisis_califica_las_entregas_del_proveedor_de_ese_dia_y_las_que_lleguen_despues(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        $antes = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()]);
        $otroDia = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()->subDays(3)]);

        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '2.1']))->assertSessionHasNoErrors();
        $despues = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()]);

        $this->assertDatabaseHas('controles_calidad', ['entrega_id' => $antes->id, 'resultado' => 'observado']);
        $this->assertDatabaseHas('controles_calidad', ['entrega_id' => $despues->id, 'resultado' => 'observado']);
        $this->assertDatabaseMissing('controles_calidad', ['entrega_id' => $otroDia->id]);
    }

    public function test_detalle_muestra_entregas_sin_crear_sanciones_por_el_resultado_tecnico(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        $entrega = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()]);
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['agua' => '4']))->assertSessionHasNoErrors();
        $analisis = AnalisisCalidad::query()->sole();

        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()
            ->assertSee('Entrega #'.$entrega->id)->assertSee('Reportar posible adulteración')
            ->assertDontSee('Sanción: #');
        $this->assertDatabaseCount('sanciones', 0);
        $this->assertDatabaseCount('infracciones_calidad', 0);
    }

    public function test_correccion_del_analisis_conserva_las_sanciones_historicas_sin_actualizarlas_ni_duplicarlas(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()]);
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '2.1']))->assertSessionHasNoErrors();
        $analisis = AnalisisCalidad::query()->sole();
        $calificar = app(CalificarEntregasConAnalisis::class);
        $this->assertDatabaseCount('sanciones', 0);
        $sancion = Sancion::query()->create([
            'control_calidad_id' => $analisis->controles()->sole()->id,
            'proveedor_id' => $proveedor->id,
            'severidad' => 'media', 'descuento' => 12.75,
            'motivo' => 'Propuesta histórica conservada', 'estado' => 'pendiente',
        ]);

        $analisis->update(['estado' => EstadoAnalisis::Rechazado]);
        $calificar->porAnalisis($analisis);
        $calificar->porAnalisis($analisis);
        $this->assertDatabaseCount('sanciones', 1);
        $this->assertSame('12.75', $sancion->fresh()->descuento);
        $this->assertSame('media', $sancion->fresh()->severidad);
        $this->assertSame('Propuesta histórica conservada', $sancion->fresh()->motivo);

        $analisis->update(['estado' => EstadoAnalisis::Observado]);
        $calificar->porAnalisis($analisis);
        $this->assertSame('12.75', $sancion->fresh()->descuento);
        $sancion->refresh()->update(['estado' => 'aplicada']);
        $analisis->update(['estado' => EstadoAnalisis::Rechazado]);
        $calificar->porAnalisis($analisis);
        $this->assertSame('12.75', $sancion->fresh()->descuento);
        $analisis->update(['estado' => EstadoAnalisis::Aprobado]);
        $calificar->porAnalisis($analisis);
        $this->assertDatabaseHas('sanciones', ['id' => $sancion->id, 'estado' => 'aplicada']);
        $this->assertDatabaseCount('infracciones_calidad', 0);
    }

    public function test_aprobar_analisis_no_elimina_una_propuesta_historica_pendiente(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()]);
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['agua' => '4']))->assertSessionHasNoErrors();
        $analisis = AnalisisCalidad::query()->sole();
        $this->assertDatabaseCount('sanciones', 0);
        $sancion = Sancion::query()->create([
            'control_calidad_id' => $analisis->controles()->sole()->id,
            'proveedor_id' => $proveedor->id,
            'severidad' => 'alta', 'descuento' => 18.25,
            'motivo' => 'Registro anterior al castigo por litro', 'estado' => 'pendiente',
        ]);
        $analisis->update(['estado' => EstadoAnalisis::Aprobado]);
        app(CalificarEntregasConAnalisis::class)->porAnalisis($analisis);
        $this->assertDatabaseCount('sanciones', 1);
        $this->assertDatabaseHas('sanciones', [
            'id' => $sancion->id, 'estado' => 'pendiente', 'descuento' => 18.25,
            'motivo' => 'Registro anterior al castigo por litro',
        ]);
        $this->assertDatabaseHas('controles_calidad', ['analisis_calidad_id' => $analisis->id, 'resultado' => 'aprobado']);
    }

    public function test_unidades_del_analizador_se_guardan_normalizadas_como_en_la_app(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        foreach (['0.530', '-0.530', '530', '-530'] as $congelacion) {
            $this->post('/admin/calidad', $this->analisis($proveedor->id, ['densidad' => '30', 'congelacion' => $congelacion]))
                ->assertSessionHasNoErrors()->assertRedirect();
            $analisis = AnalisisCalidad::query()->latest('id')->firstOrFail();
            $this->assertSame(1.03, $analisis->densidad);
            $this->assertSame(-0.53, $analisis->punto_congelacion);
            $this->assertSame('APROBADO', $analisis->estado->value);
        }
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['congelacion' => '-900']))
            ->assertSessionHasErrors('valores.congelacion');
        $this->assertDatabaseCount('analisis_calidad', 4);
    }

    public function test_el_listado_de_calidad_muestra_los_conteos_y_la_tasa_de_aprobacion(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();

        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '3.5']));
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['grasa' => '3.6']));
        $this->post('/admin/calidad', $this->analisis($proveedor->id, ['agua' => '5']));

        $this->get('/admin/calidad')->assertOk()->assertSee('66.7%')->assertSee($proveedor->nombres);
        $this->get('/admin/calidad?resultado=rechazado')->assertOk()->assertViewHas('analisis', fn ($p) => $p->total() === 1);
    }

    public function test_un_admin_puede_generar_una_liquidacion_calculada_automaticamente(): void
    {
        TarifaLeche::factory()->create(['precio' => 1.5, 'desde' => '2020-01-01', 'hasta' => '2035-12-31']);
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'litros' => 20,
            'registrado_en' => now(),
            'anulada' => false,
        ]);
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'litros' => 10,
            'registrado_en' => now(),
            'anulada' => true,
        ]);

        $response = $this->post('/admin/liquidaciones', [
            'proveedor_id' => $proveedor->id,
            'periodo_inicio' => now()->subDays(3)->toDateString(),
            'periodo_fin' => now()->toDateString(),
            'precio_litro' => 1.5,
        ]);

        $response->assertRedirect(route('admin.liquidaciones.index'));
        $this->assertDatabaseHas('liquidaciones', [
            'proveedor_id' => $proveedor->id,
            'litros_totales' => 20,
            'monto_total' => 30,
            'estado' => 'pendiente',
        ]);
    }

    public function test_no_se_puede_generar_una_liquidacion_sin_entregas_en_el_periodo(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();

        $response = $this->post('/admin/liquidaciones', [
            'proveedor_id' => $proveedor->id,
            'periodo_inicio' => now()->subDays(3)->toDateString(),
            'periodo_fin' => now()->toDateString(),
            'precio_litro' => 1.5,
        ]);

        $response->assertSessionHasErrors('proveedor_id');
        $this->assertDatabaseCount('liquidaciones', 0);
    }

    public function test_un_admin_puede_marcar_una_liquidacion_como_pagada(): void
    {
        TarifaLeche::factory()->create(['precio' => 2, 'desde' => '2020-01-01', 'hasta' => '2035-12-31']);
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 10, 'registrado_en' => now(), 'anulada' => false]);

        $this->post('/admin/liquidaciones', [
            'proveedor_id' => $proveedor->id,
            'periodo_inicio' => now()->subDay()->toDateString(),
            'periodo_fin' => now()->toDateString(),
            'precio_litro' => 2,
        ]);

        $liquidacion = Liquidacion::query()->where('proveedor_id', $proveedor->id)->firstOrFail();

        $this->patch("/admin/liquidaciones/{$liquidacion->id}/pagar")
            ->assertRedirect(route('admin.liquidaciones.index'));

        $this->assertDatabaseHas('liquidaciones', ['id' => $liquidacion->id, 'estado' => 'pagada']);
    }

    public function test_el_reporte_general_muestra_los_totales_de_los_ultimos_7_dias(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create(['nombre' => 'Zona Reporte']);
        Entrega::factory()->create(['zona_id' => $zona->id, 'litros' => 25, 'registrado_en' => now(), 'anulada' => false]);
        Entrega::factory()->create(['zona_id' => $zona->id, 'litros' => 100, 'registrado_en' => now()->subDays(20), 'anulada' => false]);

        $response = $this->get('/admin/reportes');

        $response->assertOk();
        $response->assertSee('Zona Reporte');
        $response->assertSee('25.0');
    }

    public function test_un_proveedor_no_puede_acceder_a_los_modulos_de_administracion(): void
    {
        $proveedor = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->actingAs($proveedor, 'operador');

        $this->get('/admin/zonas-vehiculos')->assertForbidden();
        $this->get('/admin/calidad')->assertForbidden();
        $this->get('/admin/produccion')->assertForbidden();
        $this->get('/admin/liquidaciones')->assertForbidden();
        $this->get('/admin/reportes')->assertForbidden();
    }
}
