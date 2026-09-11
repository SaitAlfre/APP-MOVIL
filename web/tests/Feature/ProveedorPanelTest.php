<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\PosicionSeguimiento;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_proveedor_sin_vincular_ve_un_aviso(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);

        $response = $this->actingAs($usuario, 'operador')->get('/proveedor/panel');

        $response->assertOk();
        $response->assertSee('todavía no está vinculado');
    }

    public function test_muestra_jornada_no_iniciada_cuando_no_hay_jornada_abierta_en_la_zona(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $usuario->id]);

        $response = $this->actingAs($usuario, 'operador')->get('/proveedor/panel');

        $response->assertOk();
        $response->assertSee('aún no ha iniciado su jornada');
    }

    public function test_muestra_seguimiento_no_activado_cuando_la_jornada_esta_abierta_sin_seguimiento(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $usuario->id]);
        Jornada::factory()->create(['zona_id' => $proveedor->zona_id, 'seguimiento_activo' => false]);

        $response = $this->actingAs($usuario, 'operador')->get('/proveedor/panel');

        $response->assertOk();
        $response->assertSee('todavía no activó el seguimiento');
    }

    public function test_muestra_la_ubicacion_disponible_y_viva_con_una_posicion_reciente(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $usuario->id]);
        $jornada = Jornada::factory()->create(['zona_id' => $proveedor->zona_id, 'seguimiento_activo' => true]);
        PosicionSeguimiento::query()->create([
            'jornada_id' => $jornada->id, 'lat' => -12.05, 'lng' => -77.03, 'capturada_en' => now(),
        ]);

        $response = $this->actingAs($usuario, 'operador')->get('/proveedor/panel');

        $response->assertOk();
        $response->assertSee('El acopiador está en camino');
    }

    public function test_muestra_ubicacion_no_viva_con_una_posicion_antigua(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $usuario->id]);
        $jornada = Jornada::factory()->create(['zona_id' => $proveedor->zona_id, 'seguimiento_activo' => true]);
        PosicionSeguimiento::query()->create([
            'jornada_id' => $jornada->id, 'lat' => -12.05, 'lng' => -77.03, 'capturada_en' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($usuario, 'operador')->get('/proveedor/panel');

        $response->assertOk();
        $response->assertSee('no reciente');
    }

    public function test_un_proveedor_puede_ver_su_propio_qr(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        Proveedor::factory()->create(['usuario_id' => $usuario->id]);

        $response = $this->actingAs($usuario, 'operador')->get('/proveedor/qr');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_un_proveedor_no_puede_entrar_al_flujo_de_acopiador_ni_al_panel_admin(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);

        $this->actingAs($usuario, 'operador')->get('/acopiador/onboarding')->assertForbidden();
        $this->actingAs($usuario, 'operador')->get('/admin/proveedores')->assertForbidden();
    }

    public function test_al_iniciar_sesion_un_proveedor_es_redirigido_a_su_panel(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['proveedor'], 'pin_hash' => '1234']);

        $response = $this->post('/login', ['username' => $usuario->username, 'pin' => '1234']);

        $response->assertRedirect(route('proveedor.panel'));
    }
}
