<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoAdminRecuperacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_con_sesion_revocada_puede_reingresar_y_cargar_panel(): void
    {
        $admin = Usuario::factory()->create(['username' => 'admin', 'pin_hash' => '1234', 'roles' => ['admin']]);
        $admin->forceFill(['version_sesion' => 3])->save();
        $this->post('/login', ['username' => 'admin', 'pin' => '1234'])->assertRedirect(route('admin.dashboard.index'));
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/precios-leche')->assertOk();
        $this->assertAuthenticatedAs($admin, 'operador');
    }

    public function test_exceso_de_intentos_muestra_espera_y_formulario_sin_exponer_pin(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['username' => 'cuenta-prueba-limite', 'pin' => '987654']);
        }
        $this->post('/login', ['username' => 'cuenta-prueba-limite', 'pin' => '987654'])
            ->assertStatus(429)->assertHeader('Retry-After')->assertSee('Demasiados intentos de acceso.')
            ->assertSee('vuelve a ingresar')->assertSee('name="username"', false)->assertDontSee('987654');
    }
}
