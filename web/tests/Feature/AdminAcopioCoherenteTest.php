<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAcopioCoherenteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        $usuario = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($usuario, 'operador');

        return $usuario;
    }

    public function test_revisar_jornada_muestra_ruta_y_formularios_sin_tachos(): void
    {
        $this->admin();
        $jornada = Jornada::factory()->create(['cerrada_en' => null]);
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'nombres' => 'Productora de la ruta']);
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'zona_id' => $jornada->zona_id, 'proveedor_id' => $proveedor->id]);
        $this->get(route('admin.acopiadores.jornadas.show', $jornada))->assertOk()->assertSee('Hoja de ruta')->assertSee('Productora de la ruta')->assertSee('Corregir recojo')->assertDontSee('name="tachos"', false);
        $this->get(route('admin.proveedores.create'))->assertOk()->assertDontSee('name="tachos"', false);
    }

    public function test_correccion_con_motivo_conserva_historico_y_rechaza_revision_obsoleta(): void
    {
        $admin = $this->admin();
        $entrega = Entrega::factory()->create(['litros' => 20, 'tachos' => 7]);
        $datos = ['litros' => 23.5, 'motivo' => 'Verificado con la productora', 'revision' => $entrega->revisionMovil()];
        $url = route('admin.acopiadores.entregas.corregir', $entrega);
        $this->patch($url, $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('entregas', ['id' => $entrega->id, 'litros' => 23.5, 'tachos' => 7]);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'entrega', 'entidad_id' => $entrega->id, 'usuario_id' => $admin->id, 'motivo' => $datos['motivo']]);
        $this->patch($url, [...$datos, 'litros' => 40])->assertSessionHasErrors('litros');
        $this->assertEquals(23.5, $entrega->fresh()->litros);
    }

    public function test_no_registra_proveedores_de_otra_zona_ni_abre_para_rol_incorrecto(): void
    {
        $this->admin();
        $jornada = Jornada::factory()->create(['cerrada_en' => null]);
        $ajeno = Proveedor::factory()->create();
        $this->post(route('admin.acopiadores.entregas.store', $jornada), ['proveedor_id' => $ajeno->id, 'litros' => 20])->assertSessionHasErrors('proveedor_id');
        $consulta = Usuario::factory()->create(['roles' => ['consulta']]);
        $this->post(route('admin.acopiadores.jornadas.store'), ['usuario_id' => $consulta->id, 'zona_id' => $jornada->zona_id, 'vehiculo_id' => $jornada->vehiculo_id])->assertSessionHasErrors('usuario_id');
        $this->assertDatabaseCount('entregas', 0);
    }

    public function test_abrir_de_nuevo_retoma_jornada_original(): void
    {
        $this->admin();
        $usuario = Usuario::factory()->create(['roles' => ['acopiador']]);
        $jornada = Jornada::factory()->create(['usuario_id' => $usuario->id, 'cerrada_en' => null]);
        $this->post(route('admin.acopiadores.jornadas.store'), ['usuario_id' => $usuario->id, 'zona_id' => Zona::factory()->create()->id, 'vehiculo_id' => Vehiculo::factory()->create()->id])->assertRedirect(route('admin.acopiadores.jornadas.show', $jornada));
        $this->assertDatabaseCount('jornadas', 1);
    }

    public function test_fecha_peru_incluye_noche_del_mismo_dia_local(): void
    {
        $this->admin();
        Entrega::factory()->create(['litros' => 12, 'registrado_en' => '2026-10-26 02:00:00']);
        Entrega::factory()->create(['litros' => 8, 'registrado_en' => '2026-10-25 04:59:00']);
        $this->get('/admin/entregas?fecha=2026-10-25')->assertOk()->assertViewHas('litrosFiltrados', 12.0);
    }

    public function test_consulta_no_puede_corregir(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['consulta']]), 'operador');
        $entrega = Entrega::factory()->create();
        $this->patch(route('admin.acopiadores.entregas.corregir', $entrega), ['litros' => 30, 'motivo' => 'Sin permiso', 'revision' => $entrega->revisionMovil()])->assertForbidden();
    }

    public function test_cierre_admin_se_exporta_y_envio_antiguo_no_reabre(): void
    {
        $this->admin();
        $usuario = Usuario::factory()->create(['roles' => ['acopiador'], 'username' => 'acop_cierre', 'pin_hash' => '2468']);
        $jornada = Jornada::factory()->create(['usuario_id' => $usuario->id, 'cerrada_en' => null]);
        $this->patch(route('admin.acopiadores.jornadas.cerrar', $jornada), ['motivo' => 'Terminado por supervisor'])->assertSessionHasNoErrors();
        $token = $this->postJson('/api/movil/sesion', ['username' => 'acop_cierre', 'pin' => '2468'])->assertOk()->json('token');
        $this->withToken($token)->putJson('/api/movil/cambios/jornada', ['uuid' => 'web-jornada-'.$jornada->id, 'zonaNombre' => $jornada->zona->nombre, 'vehiculoPlaca' => $jornada->vehiculo->placa, 'abiertaEn' => $jornada->abierta_en->getTimestampMs(), 'cerradaEn' => null])->assertOk();
        $this->assertNotNull($jornada->fresh()->cerrada_en);
        $datos = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json('jornadas');
        $this->assertTrue(collect($datos)->firstWhere('id', $jornada->id)['cierreAdministrativo']);
    }

    public function test_deshacer_no_acopiado_conserva_historial_y_aumenta_version(): void
    {
        $this->admin();
        $jornada = Jornada::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id]);
        $marca = SinRecojo::query()->create(['uuid_movil' => 'marca-web', 'jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'usuario_id' => $jornada->usuario_id, 'zona_id' => $jornada->zona_id, 'fecha' => $jornada->fecha, 'motivo' => 'SIN_LECHE', 'registrada_en' => now(), 'deshecha' => false, 'actualizado_en' => now()->getTimestampMs() + 100000]);
        $version = $marca->actualizado_en;
        $this->patch(route('admin.acopiadores.marcas.deshacer', $marca), ['motivo' => 'Proveedor disponible nuevamente'])->assertSessionHasNoErrors();
        $this->assertTrue($marca->fresh()->deshecha);
        $this->assertGreaterThan($version, $marca->fresh()->actualizado_en);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'sin_recojo', 'entidad_id' => $marca->id, 'motivo' => 'Proveedor disponible nuevamente']);
    }

    public function test_reportes_y_csv_usan_fecha_peru_sin_tachos(): void
    {
        $this->admin();
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(12, 0));
        Entrega::factory()->create(['litros' => 12, 'registrado_en' => '2026-10-26 02:00:00']);
        Entrega::factory()->create(['litros' => 8, 'registrado_en' => '2026-10-25 04:59:00']);
        $response = $this->get('/admin/reportes?desde=2026-10-25&hasta=2026-10-25')->assertOk();
        $this->assertEquals(12.0, $response->viewData('resumen')['litros']);
        $this->assertEquals(12.0, $response->viewData('tendencia')[0]['litros']);
        $response->assertDontSee('Tachos recibidos');
        $csv = $this->get(route('admin.reportes.exportar', ['desde' => '2026-10-25', 'hasta' => '2026-10-25']))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Tachos', $csv);
        $this->assertStringContainsString('12,00', $csv);
    }
}
