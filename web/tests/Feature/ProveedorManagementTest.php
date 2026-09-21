<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorManagementTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    public function test_un_admin_puede_registrar_un_proveedor(): void
    {
        $admin = $this->comoAdmin();
        $zona = Zona::factory()->create();

        $response = $this->post('/admin/proveedores', [
            'codigo' => 'PRV-001',
            'nombres' => 'Juan Pérez',
            'dni' => '12345678',
            'telefono' => '987654321',
            'direccion' => 'Av. Siempre Viva 123',
            'zona_id' => $zona->id,
            'tachos' => 2,
            'capacidad_tacho_l' => 40,
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseHas('proveedores', [
            'codigo' => 'PRV-001',
            'dni' => '12345678',
            'zona_id' => $zona->id,
            'creado_por_usuario_id' => $admin->id,
            'estado' => 'activo',
        ]);
    }

    public function test_rechaza_un_codigo_de_proveedor_duplicado(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create();
        Proveedor::factory()->create(['codigo' => 'PRV-001', 'zona_id' => $zona->id]);

        $response = $this->post('/admin/proveedores', [
            'codigo' => 'PRV-001',
            'nombres' => 'Otro Proveedor',
            'dni' => '87654321',
            'zona_id' => $zona->id,
            'tachos' => 1,
            'capacidad_tacho_l' => 40,
        ]);

        $response->assertSessionHasErrors('codigo');
        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_rechaza_tachos_en_cero(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create();

        $response = $this->post('/admin/proveedores', [
            'codigo' => 'PRV-002',
            'nombres' => 'Proveedor Prueba',
            'dni' => '11111111',
            'zona_id' => $zona->id,
            'tachos' => 0,
            'capacidad_tacho_l' => 40,
        ]);

        $response->assertSessionHasErrors([
            'tachos' => 'El campo cantidad de tachos debe ser mayor o igual que 1.',
        ]);
        $this->assertDatabaseMissing('proveedores', ['codigo' => 'PRV-002']);
    }

    public function test_los_campos_obligatorios_muestran_mensajes_en_espanol(): void
    {
        $this->comoAdmin();

        $this->post('/admin/proveedores', [])->assertSessionHasErrors([
            'codigo' => 'El campo código es obligatorio.',
            'nombres' => 'El campo nombre completo es obligatorio.',
            'dni' => 'El campo DNI es obligatorio.',
            'zona_id' => 'El campo zona es obligatorio.',
        ]);
    }

    public function test_un_admin_puede_actualizar_un_proveedor(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'nombres' => 'Nombre Viejo']);

        $response = $this->put("/admin/proveedores/{$proveedor->id}", [
            'codigo' => $proveedor->codigo,
            'nombres' => 'Nombre Nuevo',
            'dni' => $proveedor->dni,
            'zona_id' => $zona->id,
            'tachos' => 3,
            'capacidad_tacho_l' => 50,
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombres' => 'Nombre Nuevo',
            'tachos' => 3,
        ]);
    }

    public function test_un_admin_puede_cambiar_el_estado_de_un_proveedor(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'estado' => 'activo']);

        $response = $this->patch("/admin/proveedores/{$proveedor->id}/estado", [
            'estado' => 'suspendido',
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'estado' => 'suspendido',
        ]);
    }

    public function test_un_admin_puede_ver_el_codigo_qr_de_un_proveedor(): void
    {
        $this->comoAdmin();
        $proveedor = Proveedor::factory()->create();

        $response = $this->get("/admin/proveedores/{$proveedor->id}/qr");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_un_visitante_no_autenticado_no_puede_gestionar_proveedores(): void
    {
        $response = $this->get('/admin/proveedores');

        $response->assertRedirect('/login');
    }

    public function test_un_acopiador_no_puede_gestionar_proveedores(): void
    {
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);

        $response = $this->actingAs($acopiador, 'operador')->get('/admin/proveedores');

        $response->assertForbidden();
    }
}
