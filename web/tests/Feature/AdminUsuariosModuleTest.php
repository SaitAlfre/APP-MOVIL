<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsuariosModuleTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    // --- CRUD y listado ---

    public function test_un_admin_puede_crear_un_usuario_con_varios_roles(): void
    {
        $this->comoAdmin();

        $response = $this->post(route('admin.usuarios.store'), [
            'username' => 'recepcion1',
            'nombres' => 'Rosa Recepción',
            'dni' => '11112222',
            'pin' => '4321',
            'roles' => ['recepcion', 'calidad'],
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('usuarios', ['username' => 'recepcion1', 'nombres' => 'Rosa Recepción']);
        $creado = Usuario::query()->where('username', 'recepcion1')->firstOrFail();
        $this->assertSame(['recepcion', 'calidad'], $creado->roles);
        $this->assertNotSame('4321', $creado->pin_hash);
        $this->assertTrue(Hash::check('4321', $creado->pin_hash));
    }

    public function test_no_se_puede_crear_un_usuario_con_username_duplicado(): void
    {
        $this->comoAdmin();
        Usuario::factory()->create(['username' => 'existente']);

        $response = $this->post(route('admin.usuarios.store'), [
            'username' => 'existente', 'nombres' => 'Otro', 'dni' => '99998888', 'pin' => '1111', 'roles' => ['consulta'],
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertSame(1, Usuario::query()->where('username', 'existente')->count());
    }

    public function test_un_admin_puede_editar_datos_y_roles_de_un_usuario(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['consulta']]);

        $response = $this->put(route('admin.usuarios.update', $usuario->id), [
            'nombres' => 'Nombre Actualizado', 'dni' => $usuario->dni, 'roles' => ['produccion'],
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'nombres' => 'Nombre Actualizado']);
        $this->assertSame(['produccion'], $usuario->fresh()->roles);
    }

    public function test_el_listado_filtra_por_busqueda_rol_y_estado(): void
    {
        $this->comoAdmin();
        Usuario::factory()->create(['nombres' => 'Juan Calidad', 'roles' => ['calidad'], 'activo' => true]);
        Usuario::factory()->create(['nombres' => 'Ana Produccion', 'roles' => ['produccion'], 'activo' => false]);

        $this->get(route('admin.usuarios.index', ['buscar' => 'Juan']))->assertSee('Juan Calidad')->assertDontSee('Ana Produccion');
        $this->get(route('admin.usuarios.index', ['rol' => 'produccion']))->assertSee('Ana Produccion')->assertDontSee('Juan Calidad');
        $this->get(route('admin.usuarios.index', ['activo' => '0']))->assertSee('Ana Produccion')->assertDontSee('Juan Calidad');
    }

    // --- Activar / desactivar ---

    public function test_desactivar_un_usuario_le_quita_el_acceso_de_inmediato_aunque_tenga_sesion_abierta(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['consulta']]);

        // El usuario ya tenía una sesión abierta en el navegador.
        $sesionUsuario = $this->actingAs($usuario, 'operador');
        $sesionUsuario->get(route('admin.dashboard.index'))->assertOk();

        // El admin lo desactiva desde otra sesión (simulado reautenticando como admin).
        $admin = $this->comoAdmin();
        $this->patch(route('admin.usuarios.estado', $usuario->id), ['activo' => false])->assertSessionHasNoErrors();

        // La misma sesión del usuario, en su siguiente petición, ya no tiene acceso.
        $this->actingAs($usuario->fresh(), 'operador');
        $this->get(route('admin.dashboard.index'))->assertRedirect(route('login'));
    }

    public function test_no_se_puede_desactivar_al_unico_administrador(): void
    {
        $admin = $this->comoAdmin();

        $response = $this->patch(route('admin.usuarios.estado', $admin->id), ['activo' => false]);

        $response->assertSessionHasErrors('activo');
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_se_puede_desactivar_un_admin_si_queda_otro_admin_activo(): void
    {
        $admin = $this->comoAdmin();
        Usuario::factory()->create(['roles' => ['admin']]);

        $response = $this->patch(route('admin.usuarios.estado', $admin->id), ['activo' => false]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse($admin->fresh()->activo);
    }

    // --- Bloqueo manual ---

    public function test_un_admin_puede_bloquear_y_desbloquear_con_motivo(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => ['calidad']]);

        $this->post(route('admin.usuarios.bloquear', $usuario->id), ['motivo' => 'Sospecha de uso indebido'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'bloqueado_manualmente' => true, 'motivo_bloqueo' => 'Sospecha de uso indebido']);

        // Hay que cerrar la sesión del admin: el middleware `guest` no deja llegar a
        // LoginController si ya hay una sesión "operador" abierta.
        $this->post('/logout');
        $intento = $this->post('/login', ['username' => $usuario->username, 'pin' => '1234']);
        $intento->assertSessionHasErrors('username');
        $this->assertGuest('operador');

        $this->comoAdmin();
        $this->post(route('admin.usuarios.desbloquear', $usuario->id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'bloqueado_manualmente' => false]);
    }

    public function test_bloquear_exige_un_motivo(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['calidad']]);

        $this->post(route('admin.usuarios.bloquear', $usuario->id), ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'bloqueado_manualmente' => false]);
    }

    public function test_no_se_puede_bloquear_al_unico_administrador(): void
    {
        $admin = $this->comoAdmin();

        $response = $this->post(route('admin.usuarios.bloquear', $admin->id), ['motivo' => 'prueba']);

        $response->assertSessionHasErrors('motivo');
        $this->assertFalse($admin->fresh()->bloqueado_manualmente);
    }

    // --- Restablecer credenciales ---

    public function test_restablecer_credenciales_cambia_el_pin_e_invalida_sesiones(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => ['recepcion']]);

        $this->post(route('admin.usuarios.restablecer', $usuario->id), ['pin' => '5678'])->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['username' => $usuario->username, 'pin' => '1234'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => $usuario->username, 'pin' => '5678'])->assertSessionHasNoErrors();
    }

    public function test_restablecer_credenciales_rechaza_un_pin_con_formato_invalido(): void
    {
        $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['recepcion']]);

        $this->post(route('admin.usuarios.restablecer', $usuario->id), ['pin' => 'abc'])->assertSessionHasErrors('pin');
    }

    // --- Auditoría ---

    public function test_los_cambios_de_roles_bloqueo_y_restablecimiento_quedan_auditados(): void
    {
        $admin = $this->comoAdmin();
        $usuario = Usuario::factory()->create(['roles' => ['consulta']]);

        $this->put(route('admin.usuarios.update', $usuario->id), ['nombres' => $usuario->nombres, 'dni' => $usuario->dni, 'roles' => ['calidad']]);
        $this->post(route('admin.usuarios.bloquear', $usuario->id), ['motivo' => 'Auditoría de prueba']);
        $this->post(route('admin.usuarios.desbloquear', $usuario->id));
        $this->post(route('admin.usuarios.restablecer', $usuario->id), ['pin' => '9999']);

        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $usuario->id, 'accion' => 'actualizar', 'usuario_id' => $admin->id]);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $usuario->id, 'accion' => 'anular', 'motivo' => 'Auditoría de prueba']);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $usuario->id, 'accion' => 'autorizar']);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $usuario->id, 'accion' => 'corregir', 'valor_despues' => 'credenciales restablecidas']);
    }

    // --- Matriz de permisos ---

    public function test_un_rol_de_consulta_puede_ver_pero_no_gestionar(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['consulta']]);
        $this->actingAs($usuario, 'operador');

        $this->get(route('admin.proveedores.index'))->assertOk();
        $this->get(route('admin.recepcion.index'))->assertOk();
        $this->get(route('admin.calidad.index'))->assertOk();
        $this->get(route('admin.produccion.index'))->assertOk();
        $this->get(route('admin.liquidaciones.index'))->assertOk();
        $this->get(route('admin.dashboard.index'))->assertOk();

        $this->get(route('admin.proveedores.create'))->assertForbidden();
        $this->post(route('admin.calidad.store'))->assertForbidden();
        $this->get(route('admin.usuarios.index'))->assertForbidden();
    }

    public function test_el_rol_recepcion_gestiona_solo_su_modulo(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['recepcion']]);
        $this->actingAs($usuario, 'operador');

        $this->get(route('admin.recepcion.index'))->assertOk();
        $this->get(route('admin.calidad.index'))->assertOk();

        $this->post(route('admin.calidad.store'))->assertForbidden();
        $this->get(route('admin.zonas-vehiculos.index'))->assertForbidden();
    }

    public function test_los_roles_nuevos_pueden_iniciar_sesion_en_la_web(): void
    {
        foreach (['recepcion', 'calidad', 'produccion', 'liquidaciones', 'consulta'] as $rol) {
            $usuario = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => [$rol]]);

            $this->post('/login', ['username' => $usuario->username, 'pin' => '1234'])
                ->assertRedirect(route('admin.dashboard.index'));
            $this->post('/logout');
        }
    }
}
