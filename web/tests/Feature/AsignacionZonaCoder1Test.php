<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionZonaCoder1Test extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '2468']);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_crear_reasignar_y_rechazar_quitar_zona_de_calidad_conserva_el_contrato(): void
    {
        $primera = Zona::factory()->create();
        $segunda = Zona::factory()->create();
        $this->post(route('admin.usuarios.store'), [
            'username' => 'calidad_prueba', 'nombres' => 'Cuenta ficticia', 'dni' => '99007777',
            'pin' => '1357', 'roles' => ['calidad'], 'zona_id' => $primera->id,
        ])->assertSessionHasNoErrors();
        $cuenta = Usuario::where('username', 'calidad_prueba')->sole();
        $this->assertSame($primera->id, $cuenta->zona_id);
        $this->put(route('admin.usuarios.update', $cuenta->id), [
            'nombres' => $cuenta->nombres, 'dni' => $cuenta->dni, 'roles' => ['calidad'], 'zona_id' => $segunda->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($segunda->id, $cuenta->fresh()->zona_id);
        $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk()
            ->assertJsonPath('usuario.zonaId', $segunda->id)->assertJsonPath('usuario.asignacionZonaIncluida', true);
        $this->put(route('admin.usuarios.update', $cuenta->id), [
            'nombres' => $cuenta->nombres, 'dni' => $cuenta->dni, 'roles' => ['calidad'], 'zona_id' => null,
        ])->assertSessionHasErrors();
        $this->assertSame($segunda->id, $cuenta->fresh()->zona_id);
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_clientes_anteriores_preservan_zona_y_los_actuales_pueden_reasignar_y_quitar(): void
    {
        $zona = Zona::factory()->create();
        $otra = Zona::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => ['acopiador'], 'zona_id' => $zona->id]);
        $token = $this->postJson('/api/movil/sesion', ['username' => $this->admin->username, 'pin' => '2468'])->assertOk()->json('token');
        $datos = ['servidorId' => $cuenta->id, 'username' => $cuenta->username, 'nombres' => 'Nombre corregido',
            'dni' => $cuenta->dni, 'roles' => ['ACOPIADOR'], 'activo' => true];
        $this->withToken($token)->putJson('/api/movil/cambios/usuario', $datos)->assertOk();
        $this->assertSame($zona->id, $cuenta->fresh()->zona_id);
        $this->withToken($token)->putJson('/api/movil/cambios/usuario', [...$datos,
            'asignacionZonaIncluida' => true, 'zonaId' => $otra->id, 'zonaNombre' => $otra->nombre,
        ])->assertOk();
        $this->assertSame($otra->id, $cuenta->fresh()->zona_id);
        $this->withToken($token)->putJson('/api/movil/cambios/usuario', [...$datos,
            'asignacionZonaIncluida' => true, 'zonaId' => null, 'zonaNombre' => null,
        ])->assertOk();
        $this->assertNull($cuenta->fresh()->zona_id);
    }

    public function test_no_asigna_zona_inactiva_inexistente_o_a_un_perfil_ajeno(): void
    {
        $inactiva = Zona::factory()->create(['activo' => false]);
        $activa = Zona::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => ['consulta']]);
        foreach ([[$inactiva->id, ['calidad']], [999999, ['calidad']], [$activa->id, ['consulta']]] as [$zonaId, $roles]) {
            $this->put(route('admin.usuarios.update', $cuenta->id), [
                'nombres' => $cuenta->nombres, 'dni' => $cuenta->dni, 'roles' => $roles, 'zona_id' => $zonaId,
            ])->assertSessionHasErrors();
            $this->assertNull($cuenta->fresh()->zona_id);
            $this->assertSame(['consulta'], $cuenta->fresh()->roles);
        }
    }

    public function test_zona_asignada_no_se_desactiva_y_bloquear_desbloquear_no_pierde_la_asignacion(): void
    {
        $zona = Zona::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => ['calidad'], 'zona_id' => $zona->id]);
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => false])->assertSessionHasErrors('activo');
        $this->post(route('admin.usuarios.bloquear', $cuenta->id), ['motivo' => 'Prueba ficticia'])->assertSessionHasNoErrors();
        $this->assertSame($zona->id, $cuenta->fresh()->zona_id);
        $this->post(route('admin.usuarios.desbloquear', $cuenta->id))->assertSessionHasNoErrors();
        $this->assertSame($zona->id, $cuenta->fresh()->zona_id);
        $this->assertTrue($zona->fresh()->activo);
    }

    public function test_la_zona_de_otras_cuentas_no_se_exporta_como_dato_autorizado(): void
    {
        $zona = Zona::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => ['acopiador'], 'zona_id' => $zona->id, 'pin_hash' => '1357']);
        $token = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk()->json('token');
        $usuarios = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json('usuarios');
        $propia = collect($usuarios)->firstWhere('id', $cuenta->id);
        $this->assertTrue($propia['asignacionZonaIncluida']);
        $this->assertSame($zona->id, $propia['zonaId']);
        foreach (collect($usuarios)->where('id', '!=', $cuenta->id) as $otra) {
            $this->assertFalse($otra['asignacionZonaIncluida']);
            $this->assertNull($otra['zonaId']);
        }
    }
}
