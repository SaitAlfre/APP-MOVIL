<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Página de roles en dos columnas: lista de roles y permisos por categoría con interruptores. */
class RolesPermisosPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    public function test_el_rol_elegido_muestra_sus_permisos_por_categoria(): void
    {
        $id = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Supervisor de acopio', 'activo' => true,
            'permisos' => json_encode(['recepcion' => ['ver', 'crear'], 'entregas' => ['ver']]), 'campos' => '{}']);

        $this->get(route('admin.roles.index', ['rol' => $id]))->assertOk()
            ->assertSee('Supervisor de acopio')->assertSee('Organización del acopio')->assertSee('Rol del sistema')
            ->assertSee('name="permisos[recepcion][]"', false)
            ->assertViewHas('seleccion', fn (array $seleccion) => $seleccion['tipo'] === 'perfil' && (int) $seleccion['rol']->id === $id)
            ->assertViewHas('permisos', fn (array $permisos) => $permisos['recepcion'] === ['ver', 'crear']);
    }

    public function test_un_rol_del_sistema_se_ve_pero_no_se_edita(): void
    {
        $this->get(route('admin.roles.index', ['sistema' => 'calidad']))->assertOk()
            ->assertSee('Los roles del sistema son fijos')
            ->assertDontSee('name="permisos[calidad][]"', false)
            ->assertViewHas('permisos', fn (array $permisos) => in_array('ver', $permisos['calidad'] ?? [], true));
    }

    public function test_crear_un_rol_lo_deja_elegido_para_activar_permisos(): void
    {
        $this->post(route('admin.roles.store'), ['nombre' => 'Digitador', 'activo' => 1, 'permisos_presentes' => 1])
            ->assertRedirect(route('admin.roles.index'));
        $id = (int) DB::table('perfiles_roles')->where('nombre', 'Digitador')->value('id');

        $this->get(route('admin.roles.index'))->assertOk()
            ->assertViewHas('seleccion', fn (array $seleccion) => $seleccion['tipo'] === 'perfil' && (int) $seleccion['rol']->id === $id);
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Digitador', 'activo' => 1, 'permisos_presentes' => 1, 'permisos' => ['proveedores' => ['editar']]])
            ->assertSessionHasNoErrors();
        $this->assertSame(['editar', 'ver'], json_decode(DB::table('perfiles_roles')->where('id', $id)->value('permisos'), true)['proveedores']);
    }
}
