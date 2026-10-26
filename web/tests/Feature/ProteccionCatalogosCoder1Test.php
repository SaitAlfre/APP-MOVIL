<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProteccionCatalogosCoder1Test extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '2468']);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_zona_con_proveedores_activos_rechaza_desactivacion_y_conserva_relaciones(): void
    {
        $zona = Zona::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'estado' => 'activo']);
        $this->from('/admin/zonas-vehiculos')->patch(route('admin.zonas.estado', $zona->id), ['activo' => false])
            ->assertRedirect('/admin/zonas-vehiculos')->assertSessionHasErrors([
                'activo' => 'No se puede desactivar: hay proveedores activos en esta zona.',
            ]);
        $this->assertTrue($zona->fresh()->activo);
        $this->assertSame($zona->id, $proveedor->fresh()->zona_id);
    }

    public function test_zona_y_vehiculo_con_jornada_abierta_no_se_desactivan(): void
    {
        $jornada = Jornada::factory()->create();
        $this->patch(route('admin.zonas.estado', $jornada->zona_id), ['activo' => false])->assertSessionHasErrors('activo');
        $this->patch(route('admin.vehiculos.estado', $jornada->vehiculo_id), ['activo' => false])->assertSessionHasErrors('activo');
        $this->assertTrue(Zona::findOrFail($jornada->zona_id)->activo);
        $this->assertTrue(Vehiculo::findOrFail($jornada->vehiculo_id)->activo);
        $this->assertNull($jornada->fresh()->cerrada_en);
    }

    public function test_jornada_cerrada_y_proveedor_retirado_permiten_desactivar_y_reactivar_sin_borrar(): void
    {
        $jornada = Jornada::factory()->cerrada()->create();
        Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'estado' => 'retirado']);
        foreach ([['zonas', $jornada->zona_id], ['vehiculos', $jornada->vehiculo_id]] as [$catalogo, $id]) {
            $this->patch(route("admin.{$catalogo}.estado", $id), ['activo' => false])->assertSessionHasNoErrors();
            $this->assertDatabaseHas($catalogo, ['id' => $id, 'activo' => false]);
            $this->patch(route("admin.{$catalogo}.estado", $id), ['activo' => true])->assertSessionHasNoErrors();
            $this->assertDatabaseHas($catalogo, ['id' => $id, 'activo' => true]);
        }
        $this->assertDatabaseCount('jornadas', 1);
        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_estado_es_obligatorio_y_booleano(): void
    {
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        foreach ([[], ['activo' => 'desactivar']] as $datos) {
            $this->patch(route('admin.zonas.estado', $zona->id), $datos)->assertSessionHasErrors('activo');
            $this->patch(route('admin.vehiculos.estado', $vehiculo->id), $datos)->assertSessionHasErrors('activo');
        }
        $this->assertTrue($zona->fresh()->activo);
        $this->assertTrue($vehiculo->fresh()->activo);
    }

    public function test_la_api_aplica_las_mismas_guardas_y_revierte_el_renombrado_en_caso_de_rechazo(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'ZONA PROTEGIDA']);
        Proveedor::factory()->create(['zona_id' => $zona->id, 'estado' => 'activo']);
        $jornada = Jornada::factory()->create();
        $token = $this->postJson('/api/movil/sesion', ['username' => $this->admin->username, 'pin' => '2468'])->assertOk()->json('token');
        $this->withToken($token)->putJson('/api/movil/cambios/zona', [
            'servidorId' => $zona->id, 'nombre' => 'NO DEBE GUARDARSE', 'activo' => false,
        ])->assertUnprocessable()->assertJsonPath('definitivo', true);
        $vehiculo = Vehiculo::findOrFail($jornada->vehiculo_id);
        $this->withToken($token)->putJson('/api/movil/cambios/vehiculo', [
            'servidorId' => $vehiculo->id, 'nombre' => 'NO GUARDAR', 'placa' => $vehiculo->placa, 'activo' => false,
        ])->assertUnprocessable()->assertJsonPath('definitivo', true);
        $this->assertSame('ZONA PROTEGIDA', $zona->fresh()->nombre);
        $this->assertSame($vehiculo->nombre, $vehiculo->fresh()->nombre);
        $this->assertTrue($zona->fresh()->activo);
        $this->assertTrue($vehiculo->fresh()->activo);
    }

    public function test_consulta_no_puede_modificar_estados(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['consulta']]), 'operador');
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => false])->assertForbidden();
        $this->patch(route('admin.vehiculos.estado', $vehiculo->id), ['activo' => false])->assertForbidden();
        $this->assertTrue($zona->fresh()->activo);
        $this->assertTrue($vehiculo->fresh()->activo);
    }
}
