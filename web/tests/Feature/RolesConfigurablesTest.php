<?php

namespace Tests\Feature;

use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
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

    public function test_permisos_dependientes_incluyen_consulta_y_no_ofrecen_acciones_inexistentes(): void
    {
        $this->admin();
        $this->get(route('admin.roles.create'))->assertOk()->assertDontSee('value="eliminar"', false);
        $this->post(route('admin.roles.store'), ['nombre' => '  Editor de proveedores  ', 'base' => 'acopiador', 'activo' => 1, 'permisos' => ['proveedores' => ['editar']]])->assertSessionHasNoErrors();
        $perfil = DB::table('perfiles_roles')->where('nombre', 'Editor de proveedores')->first();
        $this->assertSame(['editar', 'ver'], json_decode($perfil->permisos, true)['proveedores']);
    }

    public function test_quitar_permisos_no_los_restaura_al_volver_de_un_error(): void
    {
        $this->admin();
        $id = $this->perfil();
        $url = route('admin.roles.edit', $id);
        $this->from($url)->put(route('admin.roles.update', $id), ['nombre' => '', 'base' => 'acopiador', 'activo' => 1, 'permisos_presentes' => 1])->assertSessionHasErrors('nombre');
        $this->get($url)->assertOk()->assertDontSee('value="ver" class="h-4 w-4 accent-eh-primary" checked', false);
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Auxiliar', 'base' => 'acopiador', 'activo' => 1, 'permisos_presentes' => 1])->assertSessionHasNoErrors();
        $this->assertSame([], json_decode(DB::table('perfiles_roles')->where('id', $id)->value('permisos'), true));
    }

    public function test_rol_asignado_valida_base_sin_pagina_de_error_y_conserva_datos(): void
    {
        $this->admin();
        $id = $this->perfil();
        Usuario::factory()->create(['perfil_rol_id' => $id]);
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Auxiliar', 'base' => 'proveedor', 'activo' => 1])->assertSessionHasErrors('base');
        $this->assertDatabaseHas('perfiles_roles', ['id' => $id, 'base' => 'acopiador']);
    }

    public function test_listados_de_roles_y_formularios_filtran_y_paginan(): void
    {
        $this->admin();
        for ($i = 1; $i <= 12; $i++) {
            DB::table('perfiles_roles')->insert(['nombre' => 'Rol '.$i, 'base' => 'acopiador', 'activo' => $i !== 12, 'permisos' => '{}', 'campos' => '{}']);
        }
        foreach (['admin.roles.index', 'admin.formularios.index'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertViewHas('perfiles', fn ($perfiles) => $perfiles->total() === 12 && $perfiles->count() === 10);
            $this->get(route($ruta, ['estado' => 'inactivo', 'buscar' => 'Rol 12']))->assertOk()->assertViewHas('perfiles', fn ($perfiles) => $perfiles->total() === 1);
        }
    }

    public function test_solo_lectura_no_abre_el_editor_de_usuarios(): void
    {
        $id = $this->perfil();
        $usuario = Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id]);
        $this->actingAs($usuario, 'operador');
        $this->get(route('admin.usuarios.edit', $usuario))->assertForbidden();
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Solo lectura')->assertDontSee('Cambiar PIN');
    }

    public function test_editor_de_proveedores_puede_consultar_duplicados_y_vincular_sin_crear(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['proveedores' => ['ver', 'editar']])]);
        $operador = Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id]);
        $cuenta = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create();
        $this->actingAs($operador, 'operador');
        $this->getJson(route('admin.proveedores.duplicados', ['dni' => $proveedor->dni]))->assertOk();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => $proveedor->dni])->assertOk()->assertJsonPath('estado', 'registrado');
        $this->post(route('admin.proveedores.vincular', $proveedor), ['usuario_id' => $cuenta->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'usuario_id' => $cuenta->id]);
        $this->post(route('admin.proveedores.desvincular', $proveedor))->assertSessionHasNoErrors();
        $this->get(route('admin.proveedores.create'))->assertForbidden();
        $this->post(route('admin.proveedores.store'), [])->assertForbidden();
    }

    public function test_permiso_crear_no_ofrece_ni_permite_editar_proveedores(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['proveedores' => ['ver', 'crear']])]);
        $this->actingAs(Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id]), 'operador');
        $proveedor = Proveedor::factory()->create();
        $this->get(route('admin.proveedores.show', $proveedor))->assertOk()->assertDontSee(route('admin.proveedores.edit', $proveedor), false);
        $this->get(route('admin.proveedores.edit', $proveedor))->assertForbidden();
        $this->post(route('admin.proveedores.desvincular', $proveedor))->assertForbidden();
        $this->get(route('admin.proveedores.create'))->assertOk();
    }

    public function test_edicion_cambia_de_rol_y_carga_su_formulario_sin_modificar_antes_de_guardar(): void
    {
        $this->admin();
        $id = $this->perfil();
        $usuario = Usuario::factory()->create(['roles' => ['acopiador']]);
        $this->get(route('admin.usuarios.edit', ['usuario' => $usuario->id, 'rol' => 'perfil:'.$id]))->assertOk()->assertSee('Teléfono');
        $this->assertNull($usuario->fresh()->perfil_rol_id);
        $this->put(route('admin.usuarios.update', $usuario), ['perfil_rol_id' => $id, 'nombres' => $usuario->nombres, 'dni' => $usuario->dni, 'datos_formulario' => ['telefono' => '999888777']])->assertSessionHasNoErrors();
        $this->assertSame($id, $usuario->fresh()->perfil_rol_id);
        $this->get(route('admin.usuarios.edit', ['usuario' => $usuario->id, 'rol' => 'proveedor']))->assertOk()->assertViewHas('perfil', null)->assertViewHas('base', 'proveedor');
    }

    public function test_inicio_de_rol_personalizado_apunta_a_un_modulo_permitido(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['productos' => ['ver']])]);
        $usuario = Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id, 'pin_hash' => '1234']);
        $this->post(route('login.store'), ['username' => $usuario->username, 'pin' => '1234'])->assertRedirect(route('admin.produccion.productos.index'));
        $this->get(route('login'))->assertRedirect(route('admin.produccion.productos.index'));
        $this->get(route('admin.produccion.productos.index'))->assertOk()->assertSee(route('admin.produccion.productos.index'), false)->assertDontSee('href="'.route('admin.mapa-rutas.index').'"', false);
    }

    public function test_editor_de_cuentas_no_puede_tomar_control_de_un_administrador(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['usuarios' => ['ver', 'crear', 'editar']])]);
        $operador = Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id]);
        $administrador = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '1234']);
        $hashAnterior = $administrador->pin_hash;
        $this->actingAs($operador, 'operador');
        $this->get(route('admin.usuarios.create', ['base' => 'admin']))->assertForbidden();
        $this->get(route('admin.usuarios.edit', $administrador))->assertForbidden();
        $this->put(route('admin.usuarios.update', $administrador), ['nombres' => 'Alterado'])->assertForbidden();
        $this->post(route('admin.usuarios.restablecer', $administrador), ['pin' => '9999'])->assertForbidden();
        $this->post(route('admin.usuarios.bloquear', $administrador), ['motivo' => 'Prueba'])->assertForbidden();
        $this->patch(route('admin.usuarios.estado', $administrador), ['activo' => 0])->assertForbidden();
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Cuenta protegida')->assertDontSee('data-modal-open="pin-'.$administrador->id.'"', false);
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => $administrador->dni])->assertOk()->assertJsonPath('url', route('admin.usuarios.index', ['buscar' => $administrador->dni]));
        $this->assertSame($hashAnterior, $administrador->fresh()->pin_hash);
        $this->assertTrue($administrador->fresh()->activo);
    }

    public function test_editor_delegado_conserva_perfil_y_puede_actualizar_identidad(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['usuarios' => ['ver', 'editar']])]);
        $operador = Usuario::factory()->create(['perfil_rol_id' => $id]);
        $cuenta = Usuario::factory()->create(['perfil_rol_id' => $id, 'datos_formulario' => ['telefono' => '999888777']]);
        $this->actingAs($operador, 'operador');
        $this->get(route('admin.usuarios.edit', $cuenta))->assertOk()->assertViewHas('puedeAsignarRol', false)->assertViewHas('opcionesRol', []);
        $this->put(route('admin.usuarios.update', $cuenta), ['perfil_rol_id' => $id, 'nombres' => 'Nombre corregido', 'dni' => $cuenta->dni, 'datos_formulario' => ['telefono' => '999888777']])->assertSessionHasNoErrors();
        $this->assertSame('Nombre corregido', $cuenta->fresh()->nombres);
        $this->assertSame($id, $cuenta->fresh()->perfil_rol_id);
        $this->assertSame(['acopiador'], $cuenta->fresh()->roles);
        $versionSesion = (int) $operador->version_sesion;
        $this->put(route('admin.usuarios.update', $operador), ['perfil_rol_id' => $id, 'nombres' => 'Mi nombre corregido', 'dni' => $operador->dni, 'datos_formulario' => ['telefono' => '999888776']])->assertSessionHasNoErrors();
        $this->get(route('admin.usuarios.index'))->assertOk();
        $this->assertAuthenticatedAs($operador, 'operador');
        $this->assertSame($versionSesion, (int) $operador->fresh()->version_sesion);
        $this->get(route('admin.usuarios.edit', ['usuario' => $cuenta->id, 'rol' => 'calidad']))->assertForbidden();
        $this->put(route('admin.usuarios.update', $cuenta), ['roles' => ['calidad'], 'nombres' => 'Ataque', 'dni' => $cuenta->dni])->assertSessionHasErrors('perfil_rol_id');
        $this->assertSame('Nombre corregido', $cuenta->fresh()->nombres);
        $this->assertSame($id, $cuenta->fresh()->perfil_rol_id);
    }

    public function test_editor_delegado_no_cambia_a_un_perfil_mas_poderoso(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['usuarios' => ['ver', 'crear', 'editar']])]);
        $privilegiado = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Pagos', 'base' => 'acopiador', 'activo' => true, 'permisos' => json_encode(['liquidaciones' => ['ver', 'crear', 'editar']]), 'campos' => '{}']);
        $operador = Usuario::factory()->create(['perfil_rol_id' => $id]);
        $this->actingAs($operador, 'operador');
        $this->put(route('admin.usuarios.update', $operador), ['perfil_rol_id' => $privilegiado, 'nombres' => $operador->nombres, 'dni' => $operador->dni])->assertSessionHasErrors('perfil_rol_id');
        $this->assertSame($id, $operador->fresh()->perfil_rol_id);
        $this->assertFalse($operador->fresh()->puede('liquidaciones'));
    }

    public function test_editor_delegado_no_toma_control_de_otras_cuentas_mediante_pin_o_estado(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['usuarios' => ['ver', 'editar']])]);
        $otroPerfil = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Pagos', 'base' => 'acopiador', 'activo' => true, 'permisos' => json_encode(['liquidaciones' => ['ver', 'editar']]), 'campos' => '{}']);
        $operador = Usuario::factory()->create(['perfil_rol_id' => $id]);
        $cuentas = [Usuario::factory()->create(['perfil_rol_id' => $otroPerfil]), Usuario::factory()->create(['roles' => ['calidad']])];
        $this->actingAs($operador, 'operador');
        foreach ($cuentas as $cuenta) {
            $hash = $cuenta->pin_hash;
            $this->get(route('admin.usuarios.edit', $cuenta))->assertForbidden();
            $this->put(route('admin.usuarios.update', $cuenta), [])->assertForbidden();
            $this->post(route('admin.usuarios.restablecer', $cuenta), ['pin' => '9999'])->assertForbidden();
            $this->post(route('admin.usuarios.bloquear', $cuenta), ['motivo' => 'Prueba'])->assertForbidden();
            $this->post(route('admin.usuarios.desbloquear', $cuenta))->assertForbidden();
            $this->patch(route('admin.usuarios.estado', $cuenta), ['activo' => 0])->assertForbidden();
            $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => $cuenta->dni])->assertOk()->assertJsonPath('url', route('admin.usuarios.index', ['buscar' => $cuenta->dni]));
            $this->assertSame($hash, $cuenta->fresh()->pin_hash);
            $this->assertTrue($cuenta->fresh()->activo);
        }
    }

    public function test_alta_delegada_solo_admite_su_propio_perfil_personalizado(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['usuarios' => ['ver', 'crear']])]);
        $otroPerfil = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Otro perfil', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{}', 'campos' => '{}']);
        $this->actingAs(Usuario::factory()->create(['perfil_rol_id' => $id]), 'operador');
        $this->get(route('admin.usuarios.create'))->assertOk()->assertViewHas('roles', [])->assertViewHas('perfiles', fn ($perfiles) => $perfiles->pluck('id')->all() === [$id])->assertViewHas('puedeAsignarRol', false);
        $this->get(route('admin.usuarios.create', ['base' => 'calidad']))->assertForbidden();
        $this->get(route('admin.usuarios.create', ['perfil_rol_id' => $otroPerfil]))->assertForbidden();
        $datos = ['username' => 'cuenta.delegada', 'nombres' => 'Cuenta del equipo', 'dni' => '00000001', 'pin' => '4321', 'datos_formulario' => ['telefono' => '999888777']];
        $this->post(route('admin.usuarios.store'), $datos + ['roles' => ['calidad']])->assertSessionHasErrors('perfil_rol_id');
        $this->post(route('admin.usuarios.store'), $datos + ['perfil_rol_id' => $otroPerfil])->assertSessionHasErrors('perfil_rol_id');
        $this->assertDatabaseMissing('usuarios', ['username' => 'cuenta.delegada']);
        $this->post(route('admin.usuarios.store'), $datos + ['perfil_rol_id' => $id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['username' => 'cuenta.delegada', 'perfil_rol_id' => $id]);
    }

    public function test_ficha_de_proveedor_aisla_datos_de_calidad_pagos_y_auditoria(): void
    {
        $id = $this->perfil();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['proveedores' => ['ver']])]);
        $this->actingAs(Usuario::factory()->create(['perfil_rol_id' => $id]), 'operador');
        $proveedor = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 17.5]);
        $this->mock(ControlCalidadRepositoryInterface::class, fn ($mock) => $mock->shouldNotReceive('buscarPorEntregaId'));
        $this->mock(LiquidacionRepositoryInterface::class, fn ($mock) => $mock->shouldNotReceive('ultimasDelProveedor'));
        $this->mock(AuditoriaRepositoryInterface::class, fn ($mock) => $mock->shouldNotReceive('paginar'));
        DB::enableQueryLog();
        $this->get(route('admin.proveedores.show', $proveedor))->assertOk()->assertSee('Litros semana actual')->assertSee('17.5')->assertDontSee('Precio vigente hoy')->assertDontSee('Total estimado de la semana')->assertDontSee('Calidad:')->assertViewHas('controles', [])->assertViewHas('auditoria', null);
        $consultas = implode(' ', array_column(DB::getQueryLog(), 'query'));
        DB::disableQueryLog();
        $this->assertStringNotContainsString('tarifas_leche', $consultas);
        foreach (['calidad', 'liquidaciones', 'auditoria'] as $tab) {
            $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => $tab]))->assertForbidden();
        }
        $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'entregas']))->assertOk()->assertSee('17.5')->assertDontSee('Pendiente');
    }

    public function test_ficha_de_proveedor_habilita_cada_seccion_solo_con_su_permiso(): void
    {
        $id = $this->perfil();
        $operador = Usuario::factory()->create(['perfil_rol_id' => $id]);
        $proveedor = Proveedor::factory()->create();
        $this->actingAs($operador, 'operador');
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['proveedores' => ['ver'], 'calidad' => ['ver']])]);
        $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'calidad']))->assertOk()->assertSee('Sin controles de calidad')->assertDontSee('Precio vigente hoy');
        $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'liquidaciones']))->assertForbidden();
        DB::table('perfiles_roles')->where('id', $id)->update(['permisos' => json_encode(['proveedores' => ['ver'], 'liquidaciones' => ['ver'], 'auditoria' => ['ver']])]);
        $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'liquidaciones']))->assertOk()->assertSee('Precio vigente hoy')->assertSee('Sin liquidaciones');
        $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'auditoria']))->assertOk()->assertSee('Sin movimientos auditados');
        $this->get(route('admin.proveedores.show', ['proveedor' => $proveedor->id, 'tab' => 'calidad']))->assertForbidden();
    }
}
