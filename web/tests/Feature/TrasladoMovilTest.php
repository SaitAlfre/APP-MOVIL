<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrasladoMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_traslado_llega_al_panel_y_solo_admin_puede_aprobarlo(): void
    {
        $cuenta = Usuario::factory()->create(['username' => 'proveedor_traslado', 'pin_hash' => '1357', 'roles' => ['proveedor']]);
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $cuenta->id]);
        $destino = Zona::factory()->create(['activo' => true]);
        $origen = $proveedor->zona_id;
        $token = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk()->json('token');
        $datos = ['uuid' => 'traslado-offline', 'proveedorCodigo' => $proveedor->codigo,
            'zonaDestinoId' => $destino->id, 'zonaDestinoNombre' => $destino->nombre,
            'motivo' => 'Cambio de ruta solicitado sin internet', 'estado' => 'PENDIENTE_ENVIO'];
        $this->withToken($token)->putJson('/api/movil/cambios/traslado', $datos)->assertOk();
        $this->withToken($token)->putJson('/api/movil/cambios/traslado', $datos)->assertOk();
        $this->assertDatabaseCount('solicitudes_traslado', 1);
        $this->assertSame($origen, $proveedor->fresh()->zona_id);
        $this->withToken($token)->putJson('/api/movil/cambios/traslado', [...$datos, 'estado' => 'APROBADA'])->assertForbidden();
        $solicitud = SolicitudTraslado::query()->firstOrFail();
        $this->actingAs($admin, 'operador')->get('/admin/sanciones')->assertOk()->assertSee('Solicitudes de traslado');
        $this->actingAs($admin, 'operador')->patch(route('admin.sanciones.traslados.resolver', $solicitud), ['estado' => 'APROBADA'])->assertRedirect();
        auth('operador')->forgetUser();
        $this->assertSame($destino->id, $proveedor->fresh()->zona_id);
        $this->withToken($token)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonPath('solicitudes.0.estado', 'APROBADA')->assertJsonPath('solicitudes.0.tipo', 'TRASLADO');
    }

    public function test_un_proveedor_no_puede_enviar_ni_leer_traslados_ajenos(): void
    {
        $cuenta = Usuario::factory()->create(['username' => 'otro_traslado', 'pin_hash' => '1357', 'roles' => ['proveedor']]);
        Proveedor::factory()->create(['usuario_id' => $cuenta->id]);
        $ajena = SolicitudTraslado::factory()->create();
        $token = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->assertJsonCount(0, 'solicitudes');
        $this->withToken($token)->putJson('/api/movil/cambios/traslado', [
            'uuid' => $ajena->uuid_movil, 'proveedorCodigo' => $ajena->proveedor->codigo,
            'zonaDestinoId' => $ajena->zona_destino_id, 'zonaDestinoNombre' => $ajena->destino->nombre,
            'motivo' => 'Intento ajeno', 'estado' => 'PENDIENTE',
        ])->assertForbidden();
    }
}
