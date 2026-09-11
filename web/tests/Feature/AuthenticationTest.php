<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_activo_puede_iniciar_sesion_con_el_pin_correcto(): void
    {
        $usuario = Usuario::factory()->create(['pin_hash' => '1234']);

        $response = $this->post('/login', ['username' => $usuario->username, 'pin' => '1234']);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertAuthenticatedAs($usuario, 'operador');
    }

    public function test_un_admin_es_redirigido_al_panel_administrativo(): void
    {
        $admin = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => ['admin']]);

        $response = $this->post('/login', ['username' => $admin->username, 'pin' => '1234']);

        $response->assertRedirect(route('admin.proveedores.index'));
    }

    public function test_rechaza_un_pin_incorrecto(): void
    {
        $usuario = Usuario::factory()->create(['pin_hash' => '1234']);

        $response = $this->post('/login', ['username' => $usuario->username, 'pin' => '9999']);

        $response->assertSessionHasErrors('username');
        $this->assertGuest('operador');
        $this->assertSame(1, $usuario->fresh()->intentos_fallidos);
    }

    public function test_bloquea_la_cuenta_tras_cinco_intentos_fallidos(): void
    {
        $usuario = Usuario::factory()->create(['pin_hash' => '1234']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => $usuario->username, 'pin' => '9999']);
        }

        $this->assertSame(5, $usuario->fresh()->intentos_fallidos);
        $this->assertNotNull($usuario->fresh()->bloqueado_hasta);

        $response = $this->post('/login', ['username' => $usuario->username, 'pin' => '1234']);
        $response->assertSessionHasErrors('username');
        $this->assertGuest('operador');
    }

    public function test_rechaza_a_un_usuario_desactivado(): void
    {
        $usuario = Usuario::factory()->inactivo()->create(['pin_hash' => '1234']);

        $response = $this->post('/login', ['username' => $usuario->username, 'pin' => '1234']);

        $response->assertSessionHasErrors('username');
        $this->assertGuest('operador');
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $response = $this->get('/acopiador/onboarding');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/proveedores');
        $response->assertRedirect('/login');
    }

    public function test_un_usuario_puede_cerrar_sesion(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this->actingAs($usuario, 'operador')->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest('operador');
    }

    public function test_un_acopiador_no_puede_entrar_al_panel_administrativo(): void
    {
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);

        $response = $this->actingAs($acopiador, 'operador')->get('/admin/proveedores');

        $response->assertForbidden();
    }

    public function test_un_admin_sin_rol_acopiador_no_puede_usar_el_flujo_de_campo(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);

        $response = $this->actingAs($admin, 'operador')->get('/acopiador/onboarding');

        $response->assertForbidden();
    }
}
