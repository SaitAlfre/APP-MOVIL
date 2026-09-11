<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    public function test_el_listado_de_acopiadores_muestra_las_jornadas_con_sus_totales(): void
    {
        $this->comoAdmin();
        $acopiador = Usuario::factory()->create(['nombres' => 'Juan Perez']);
        $zona = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $vehiculo = Vehiculo::factory()->create();
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'litros' => 30]);
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'litros' => 15]);

        $response = $this->get('/admin/acopiadores');

        $response->assertOk();
        $response->assertSee('Juan Perez');
        $response->assertSee('Zona Norte');
        $response->assertSee('45.0');
    }

    public function test_el_listado_de_zonas_y_vehiculos_muestra_los_registros_existentes(): void
    {
        $this->comoAdmin();
        Zona::factory()->create(['nombre' => 'Zona Este', 'activo' => true]);
        Vehiculo::factory()->create(['nombre' => 'Camioneta 3', 'placa' => 'ZZZ-999']);

        $response = $this->get('/admin/zonas-vehiculos');

        $response->assertOk();
        $response->assertSee('Zona Este');
        $response->assertSee('Camioneta 3');
        $response->assertSee('ZZZ-999');
    }

    public function test_el_listado_de_auditoria_muestra_los_registros_existentes(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['nombres' => 'Rosa Apaza']);
        Auditoria::query()->create([
            'entidad' => 'entrega',
            'entidad_id' => 1,
            'accion' => 'anular',
            'motivo' => 'Registro duplicado',
            'usuario_id' => $usuario->id,
            'ocurrido_en' => now(),
        ]);

        $response = $this->get('/admin/auditoria');

        $response->assertOk();
        $response->assertSee('Rosa Apaza');
        $response->assertSee('Registro duplicado');
    }

    public function test_las_secciones_de_calidad_liquidaciones_produccion_y_reportes_responden_correctamente(): void
    {
        $this->comoAdmin();

        foreach (['calidad', 'liquidaciones', 'produccion', 'reportes'] as $seccion) {
            $this->get("/admin/{$seccion}")->assertOk();
        }
    }

    public function test_un_acopiador_no_puede_ver_los_modulos_de_administracion(): void
    {
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);
        $this->actingAs($acopiador, 'operador');

        $this->get('/admin/acopiadores')->assertForbidden();
        $this->get('/admin/zonas-vehiculos')->assertForbidden();
        $this->get('/admin/auditoria')->assertForbidden();
        $this->get('/admin/calidad')->assertForbidden();
    }
}
