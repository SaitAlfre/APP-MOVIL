<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
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

    public function test_un_admin_puede_registrar_un_control_de_calidad_para_una_entrega(): void
    {
        $this->comoAdmin();
        $entrega = Entrega::factory()->create();

        $response = $this->post('/admin/calidad', [
            'entrega_id' => $entrega->id,
            'resultado' => 'aprobado',
            'temperatura_c' => 4.5,
            'acidez' => 16.2,
        ]);

        $response->assertRedirect(route('admin.calidad.index'));
        $this->assertDatabaseHas('controles_calidad', [
            'entrega_id' => $entrega->id,
            'resultado' => 'aprobado',
        ]);
    }

    public function test_no_se_puede_registrar_dos_controles_de_calidad_para_la_misma_entrega(): void
    {
        $this->comoAdmin();
        $entrega = Entrega::factory()->create();

        $this->post('/admin/calidad', ['entrega_id' => $entrega->id, 'resultado' => 'aprobado'])
            ->assertRedirect(route('admin.calidad.index'));

        $response = $this->post('/admin/calidad', ['entrega_id' => $entrega->id, 'resultado' => 'observado']);

        $response->assertSessionHasErrors('entrega_id');
        $this->assertDatabaseCount('controles_calidad', 1);
    }

    public function test_el_listado_de_calidad_muestra_la_cantidad_de_entregas_pendientes(): void
    {
        $this->comoAdmin();
        Entrega::factory()->create();
        Entrega::factory()->create();

        $response = $this->get('/admin/calidad');

        $response->assertOk();
        $response->assertSee('2 entregas pendientes de evaluar');
    }

    public function test_no_se_puede_registrar_un_control_con_temperatura_fuera_de_rango(): void
    {
        $this->comoAdmin();
        $entrega = Entrega::factory()->create();

        $response = $this->post('/admin/calidad', [
            'entrega_id' => $entrega->id,
            'resultado' => 'aprobado',
            'temperatura_c' => 75,
        ]);

        $response->assertSessionHasErrors('temperatura_c');
        $this->assertDatabaseCount('controles_calidad', 0);
        $this->assertSame(
            'La temperatura debe estar entre -5°C y 60°C.',
            session('errors')->first('temperatura_c'),
        );
    }

    public function test_no_se_puede_registrar_un_control_con_acidez_fuera_de_rango(): void
    {
        $this->comoAdmin();
        $entrega = Entrega::factory()->create();

        $response = $this->post('/admin/calidad', [
            'entrega_id' => $entrega->id,
            'resultado' => 'aprobado',
            'acidez' => -3,
        ]);

        $response->assertSessionHasErrors('acidez');
        $this->assertDatabaseCount('controles_calidad', 0);
    }

    public function test_el_listado_de_calidad_muestra_los_conteos_y_la_tasa_de_aprobacion(): void
    {
        $this->comoAdmin();
        $entregas = Entrega::factory()->count(3)->create();

        $this->post('/admin/calidad', ['entrega_id' => $entregas[0]->id, 'resultado' => 'aprobado']);
        $this->post('/admin/calidad', ['entrega_id' => $entregas[1]->id, 'resultado' => 'aprobado']);
        $this->post('/admin/calidad', ['entrega_id' => $entregas[2]->id, 'resultado' => 'rechazado']);

        $response = $this->get('/admin/calidad');

        $response->assertOk();
        $response->assertSee('66.7%');
    }

    public function test_el_listado_de_calidad_se_puede_filtrar_por_resultado(): void
    {
        $this->comoAdmin();
        $entregaAprobada = Entrega::factory()->create();
        $entregaRechazada = Entrega::factory()->create();

        $this->post('/admin/calidad', ['entrega_id' => $entregaAprobada->id, 'resultado' => 'aprobado']);
        $this->post('/admin/calidad', ['entrega_id' => $entregaRechazada->id, 'resultado' => 'rechazado']);

        $response = $this->get('/admin/calidad?resultado=rechazado');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('filas'));
        $this->assertSame('rechazado', $response->viewData('filas')->first()['control']->resultado->value);
    }

    public function test_un_admin_puede_generar_una_liquidacion_calculada_automaticamente(): void
    {
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
