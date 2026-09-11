<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorVinculacionTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    public function test_un_admin_puede_vincular_un_usuario_proveedor(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);

        $response = $this->post("/admin/proveedores/{$proveedor->id}/vincular", ['usuario_id' => $usuario->id]);

        $response->assertRedirect(route('admin.proveedores.edit', $proveedor->id));
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'usuario_id' => $usuario->id]);
    }

    public function test_rechaza_vincular_un_usuario_sin_rol_proveedor(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();
        $usuario = Usuario::factory()->create(['roles' => ['acopiador']]);

        $response = $this->post("/admin/proveedores/{$proveedor->id}/vincular", ['usuario_id' => $usuario->id]);

        $response->assertSessionHasErrors('usuario_id');
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'usuario_id' => null]);
    }

    public function test_rechaza_vincular_un_usuario_ya_vinculado_a_otro_proveedor(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        Proveedor::factory()->create(['usuario_id' => $usuario->id]);
        $otroProveedor = Proveedor::factory()->create();

        $response = $this->post("/admin/proveedores/{$otroProveedor->id}/vincular", ['usuario_id' => $usuario->id]);

        $response->assertSessionHasErrors('usuario_id');
        $this->assertDatabaseHas('proveedores', ['id' => $otroProveedor->id, 'usuario_id' => null]);
    }

    public function test_un_admin_puede_desvincular_un_usuario(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $usuario->id]);

        $response = $this->post("/admin/proveedores/{$proveedor->id}/desvincular");

        $response->assertRedirect(route('admin.proveedores.edit', $proveedor->id));
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'usuario_id' => null]);
    }
}
