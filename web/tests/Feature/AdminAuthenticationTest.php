<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_activo_puede_iniciar_sesion_con_credenciales_correctas(): void
    {
        $admin = AdminUser::factory()->create(['password' => 'password-correcto']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password-correcto',
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_rechaza_credenciales_incorrectas(): void
    {
        $admin = AdminUser::factory()->create(['password' => 'password-correcto']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password-incorrecto',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_rechaza_a_un_admin_desactivado(): void
    {
        $admin = AdminUser::factory()->inactivo()->create(['password' => 'password-correcto']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password-correcto',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $response = $this->get('/admin/proveedores');

        $response->assertRedirect('/admin/login');
    }

    public function test_un_admin_puede_cerrar_sesion(): void
    {
        $admin = AdminUser::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/logout');

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }
}
