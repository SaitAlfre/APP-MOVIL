<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolesConfigurablesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    private function perfil(): int
    {
        return DB::table('perfiles_roles')->insertGetId(['nombre' => 'Auxiliar', 'base' => 'acopiador', 'activo' => true, 'permisos' => json_encode(['usuarios' => ['ver']]), 'campos' => json_encode(['telefono' => ['etiqueta' => 'Teléfono', 'tipo' => 'texto', 'obligatorio' => true]])]);
    }

    public function test_seleccion_de_rol_y_formulario_personalizado(): void
    {
        $this->admin();
        $id = $this->perfil();
        $this->get(route('admin.usuarios.create'))->assertOk()->assertSee('Selecciona primero');
        $this->get(route('admin.usuarios.create', ['perfil_rol_id' => $id]))->assertOk()->assertSee('Teléfono');
        $this->post(route('admin.usuarios.store'), ['perfil_rol_id' => $id, 'username' => 'auxiliar', 'nombres' => 'Auxiliar', 'dni' => '12345678', 'pin' => '4321', 'datos_formulario' => ['telefono' => '999999999']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['username' => 'auxiliar', 'perfil_rol_id' => $id]);
    }

    public function test_campo_obligatorio_se_valida_antes_de_guardar(): void
    {
        $this->admin();
        $id = $this->perfil();
        $this->post(route('admin.usuarios.store'), ['perfil_rol_id' => $id, 'username' => 'auxiliar', 'nombres' => 'Auxiliar', 'dni' => '12345678', 'pin' => '4321'])->assertSessionHasErrors('datos_formulario.telefono');
        $this->assertDatabaseMissing('usuarios', ['username' => 'auxiliar']);
    }

    public function test_permisos_se_aplican_en_el_servidor(): void
    {
        $id = $this->perfil();
        $this->actingAs(Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id]), 'operador');
        $this->get(route('admin.usuarios.index'))->assertOk();
        $this->post(route('admin.usuarios.store'), [])->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_rol_asignado_no_se_elimina(): void
    {
        $this->admin();
        $id = $this->perfil();
        Usuario::factory()->create(['perfil_rol_id' => $id]);
        $this->delete(route('admin.roles.destroy', $id))->assertSessionHasErrors('rol');
        $this->assertDatabaseHas('perfiles_roles', ['id' => $id]);
    }

    public function test_nuevos_usuarios_aparecen_primero(): void
    {
        $this->admin();
        Usuario::factory()->create(['nombres' => 'AAAA anterior']);
        Usuario::factory()->create(['nombres' => 'ZZZZ nuevo']);
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSeeInOrder(['ZZZZ nuevo', 'AAAA anterior']);
    }

    public function test_crud_de_roles(): void
    {
        $this->admin();
        $datos = ['nombre' => 'Nuevo rol', 'base' => 'acopiador', 'activo' => 1, 'permisos' => ['usuarios' => ['ver']], 'campos_texto' => 'telefono|Teléfono|texto|si'];
        $this->post(route('admin.roles.store'), $datos)->assertRedirect(route('admin.roles.index'));
        $id = DB::table('perfiles_roles')->value('id');
        $this->get(route('admin.roles.edit', $id))->assertOk();
        $datos['nombre'] = 'Editado';
        $this->put(route('admin.roles.update', $id), $datos)->assertRedirect(route('admin.roles.index'));
        $this->delete(route('admin.roles.destroy', $id))->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('perfiles_roles', ['id' => $id]);
    }

    public function test_formulario_visual_guarda_campos_y_el_rol_los_conserva(): void
    {
        $this->admin();
        $id = $this->perfil();
        $this->get(route('admin.formularios.index'))->assertOk()->assertSee('Editar formulario');
        $this->get(route('admin.formularios.edit', $id))->assertOk()->assertSee('Agregar campo')->assertDontSee('campos_texto');
        $this->put(route('admin.formularios.update', $id), ['campos' => [['clave' => 'telefono', 'etiqueta' => 'Contacto', 'tipo' => 'texto', 'obligatorio' => 1]]])->assertSessionHasNoErrors()->assertRedirect(route('admin.formularios.index'));
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Auxiliar', 'base' => 'acopiador', 'activo' => 1])->assertSessionHasNoErrors();
        $campos = json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true);
        $this->assertSame('Contacto', $campos['telefono']['etiqueta']);
    }

    public function test_sidebar_muestra_la_gestion_al_inicio_sin_accesos_anteriores(): void
    {
        $this->admin();
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSeeInOrder(['Usuarios y proveedores', 'Roles y permisos', 'Formularios', 'Resumen del día', 'Acopio'])->assertSee('Usuarios y accesos')->assertSee(route('admin.proveedores.index'), false)->assertDontSee('Roles y formularios');
    }

    public function test_formulario_genera_claves_unicas_y_conserva_el_orden(): void
    {
        $this->admin();
        $id = $this->perfil();
        $this->put(route('admin.formularios.update', $id), ['campos' => [
            ['etiqueta' => 'Fecha de ingreso', 'tipo' => 'fecha'],
            ['etiqueta' => 'Teléfono', 'tipo' => 'texto'],
            ['etiqueta' => 'Teléfono', 'tipo' => 'texto'],
        ]])->assertSessionHasNoErrors();
        $campos = json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true);
        $this->assertSame(['fecha_de_ingreso', 'telefono_2', 'telefono_3'], array_keys($campos));
        $this->assertFalse($campos['telefono_2']['obligatorio']);
    }

    public function test_quitar_todos_los_campos_conserva_las_respuestas(): void
    {
        $this->admin();
        $id = $this->perfil();
        $usuario = Usuario::factory()->create(['perfil_rol_id' => $id, 'datos_formulario' => ['telefono' => '999999999']]);
        $this->put(route('admin.formularios.update', $id), ['editor_formulario' => 1])->assertSessionHasNoErrors();
        $this->assertSame([], json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true));
        $this->assertSame('999999999', $usuario->fresh()->datos_formulario['telefono']);
    }

    public function test_tipo_de_campo_con_respuestas_no_se_puede_cambiar(): void
    {
        $this->admin();
        $id = $this->perfil();
        Usuario::factory()->create(['perfil_rol_id' => $id, 'datos_formulario' => ['telefono' => '999999999']]);
        $this->put(route('admin.formularios.update', $id), ['campos' => [['clave' => 'telefono', 'etiqueta' => 'Contacto', 'tipo' => 'numero']]])->assertSessionHasErrors('campos.0.tipo');
        $this->assertSame('texto', json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true)['telefono']['tipo']);
    }

    public function test_formulario_rechaza_nombre_vacio_y_mas_de_cuarenta_campos(): void
    {
        $this->admin();
        $id = $this->perfil();
        $this->put(route('admin.formularios.update', $id), ['campos' => [['etiqueta' => '   ', 'tipo' => 'texto']]])->assertSessionHasErrors('campos.0.etiqueta');
        $this->put(route('admin.formularios.update', $id), ['campos' => array_fill(0, 41, ['etiqueta' => 'Ejemplo', 'tipo' => 'texto'])])->assertSessionHasErrors('campos');
    }

    public function test_solo_administrador_puede_modificar_formularios(): void
    {
        $id = $this->perfil();
        $this->actingAs(Usuario::factory()->create(['roles' => ['calidad']]), 'operador');
        $this->get(route('admin.formularios.index'))->assertForbidden();
        $this->put(route('admin.formularios.update', $id), ['campos' => []])->assertForbidden();
        $this->assertCount(1, json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true));
    }
}
