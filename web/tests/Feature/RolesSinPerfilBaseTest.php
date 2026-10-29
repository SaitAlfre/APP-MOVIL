<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/** Un rol personalizado solo define permisos del panel web; las funciones móviles se asignan explícitamente a cada cuenta. */
class RolesSinPerfilBaseTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    private function crearRol(array $permisos, string $nombre = 'Supervisor de acopio', bool $activo = true): int
    {
        $this->post(route('admin.roles.store'), ['nombre' => $nombre, 'activo' => $activo ? 1 : 0, 'permisos_presentes' => 1, 'permisos' => $permisos])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.roles.index'));

        return (int) DB::table('perfiles_roles')->where('nombre', $nombre)->value('id');
    }

    private function crearCuenta(int $rol, array $extra = []): Usuario
    {
        $this->actingAs($this->admin, 'operador')->post(route('admin.usuarios.store'), ['perfil_rol_id' => $rol, 'username' => 'cuenta.qa', 'nombres' => 'Cuenta QA',
            'dni' => '45678912', 'pin' => '4826', 'pin_confirmation' => '4826', ...$extra])->assertSessionHasNoErrors();

        return Usuario::query()->where('username', 'cuenta.qa')->firstOrFail();
    }

    /** Asignar un rol invalida las sesiones de la cuenta: se entra con la versión de sesión vigente. */
    private function entrarComo(Usuario $cuenta): static
    {
        $cuenta = $cuenta->fresh();

        return $this->actingAs($cuenta, 'operador')->withSession(['version_sesion.'.$cuenta->id => $cuenta->version_sesion]);
    }

    public function test_crear_rol_solo_con_nombre_estado_y_permisos_guarda_sin_base(): void
    {
        $id = $this->crearRol(['proveedores' => ['crear'], 'reportes' => ['ver'], 'usuarios' => ['editar', 'ver']]);
        $rol = DB::table('perfiles_roles')->find($id);
        $this->assertNull($rol->base);
        $this->assertTrue((bool) $rol->activo);
        $this->assertEqualsCanonicalizing(['crear', 'ver'], json_decode($rol->permisos, true)['proveedores']);
        $this->assertSame(['ver'], json_decode($rol->permisos, true)['reportes']);

        $html = $this->get(route('admin.roles.edit', $id))->assertOk()->assertDontSee('Perfil base')->getContent();
        foreach (['proveedores-crear', 'proveedores-ver', 'reportes-ver', 'usuarios-editar', 'usuarios-ver'] as $casilla) {
            $this->assertMatchesRegularExpression('/id="permiso-'.$casilla.'"[^>]*checked/s', $html);
        }
        $this->assertDoesNotMatchRegularExpression('/id="permiso-produccion-ver"[^>]*checked/s', $html);
        $this->get(route('admin.roles.index'))->assertOk()->assertSee('Solo panel web')->assertDontSee('Perfil base');
    }

    public function test_validaciones_del_servidor_no_conceden_permisos_inexistentes(): void
    {
        $this->post(route('admin.roles.store'), ['activo' => 1, 'permisos' => []])->assertSessionHasErrors('nombre');
        $this->post(route('admin.roles.store'), ['nombre' => 'X', 'activo' => 1, 'permisos' => ['modulo_falso' => ['ver']]])->assertSessionHasErrors('permisos');
        $this->post(route('admin.roles.store'), ['nombre' => 'X', 'activo' => 1, 'permisos' => ['reportes' => ['borrar']]])->assertSessionHasErrors('permisos.reportes.0');
        $id = $this->crearRol(['reportes' => ['eliminar', 'editar'], 'auditoria' => ['ver']], 'Sin eliminar');
        $permisos = json_decode(DB::table('perfiles_roles')->where('id', $id)->value('permisos'), true);
        $this->assertSame([], $permisos['reportes']);
        $this->assertSame(['ver'], $permisos['auditoria']);
        $this->assertDatabaseCount('perfiles_roles', 1);
    }

    public function test_editar_rol_reemplaza_permisos_y_quitar_todos_los_deja_sin_acceso(): void
    {
        $id = $this->crearRol(['reportes' => ['ver'], 'proveedores' => ['ver', 'crear']]);
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Supervisor de acopio', 'activo' => 0, 'permisos_presentes' => 1, 'permisos' => ['auditoria' => ['ver']]])->assertSessionHasNoErrors();
        $rol = DB::table('perfiles_roles')->find($id);
        $this->assertSame(['auditoria' => ['ver']], json_decode($rol->permisos, true));
        $this->assertFalse((bool) $rol->activo);
        $this->assertNull($rol->base);
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Supervisor de acopio', 'activo' => 1, 'permisos_presentes' => 1])->assertSessionHasNoErrors();
        $this->assertSame([], json_decode(DB::table('perfiles_roles')->where('id', $id)->value('permisos'), true));
    }

    public function test_cuenta_con_rol_nuevo_no_recibe_funciones_moviles_y_solo_accede_a_sus_modulos(): void
    {
        $id = $this->crearRol(['reportes' => ['ver']]);
        $this->get(route('admin.usuarios.create', ['perfil_rol_id' => $id]))->assertOk()
            ->assertSee('Funciones en la app móvil')->assertSee('Ninguna (solo panel web)')->assertDontSee('value="admin"', false)->assertDontSee('value="proveedor"', false);
        $cuenta = $this->crearCuenta($id);
        $this->assertSame([], $cuenta->roles);
        $this->assertSame($id, $cuenta->perfil_rol_id);

        $this->entrarComo($cuenta);
        $this->get(route('admin.reportes.index'))->assertOk();
        $this->get(route('admin.usuarios.index'))->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->postJson('/api/movil/sesion', ['username' => 'cuenta.qa', 'pin' => '4826'])->assertForbidden()->assertJsonPath('codigo', 'perfil_no_disponible');
    }

    public function test_cuenta_sin_funcion_movil_inicia_sesion_en_el_panel_y_ve_solo_sus_modulos(): void
    {
        $id = $this->crearRol(['reportes' => ['ver']]);
        $this->crearCuenta($id);
        auth('operador')->logout();
        $this->post(route('login'), ['username' => 'cuenta.qa', 'pin' => '4826'])->assertSessionHasNoErrors()->assertRedirect(route('admin.reportes.index'));
        $this->get(route('admin.reportes.index'))->assertOk();
        $this->get(route('admin.proveedores.index'))->assertForbidden();
    }

    public function test_el_nombre_del_rol_no_autoriza_nada(): void
    {
        $id = $this->crearRol([], 'Administrador');
        $cuenta = $this->crearCuenta($id);
        $this->assertFalse($cuenta->tieneRol('admin'));
        $this->assertFalse($cuenta->accesoWeb());
        $this->entrarComo($cuenta)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->postJson('/api/movil/sesion', ['username' => 'cuenta.qa', 'pin' => '4826'])->assertForbidden();
    }

    public function test_funcion_movil_explicita_habilita_solo_esa_funcion(): void
    {
        $id = $this->crearRol(['reportes' => ['ver']]);
        $zona = Zona::factory()->create();
        $cuenta = $this->crearCuenta($id, ['funcion_movil' => 'acopiador', 'zona_id' => $zona->id]);
        $this->assertSame(['acopiador'], $cuenta->roles);
        $this->postJson('/api/movil/sesion', ['username' => 'cuenta.qa', 'pin' => '4826'])->assertOk()->assertJsonPath('usuario.roles', ['acopiador']);

        foreach (['admin', 'proveedor', 'otro'] as $invalida) {
            $this->post(route('admin.usuarios.update', $cuenta->id), ['_method' => 'PUT', 'perfil_rol_id' => $id, 'nombres' => 'Cuenta QA', 'dni' => '45678912', 'funcion_movil' => $invalida])
                ->assertSessionHasErrors('funcion_movil');
        }
        $this->assertSame(['acopiador'], $cuenta->fresh()->roles);
        $this->put(route('admin.usuarios.update', $cuenta->id), ['perfil_rol_id' => $id, 'nombres' => 'Cuenta QA', 'dni' => '45678912', 'funcion_movil' => '', 'zona_id' => ''])->assertSessionHasNoErrors();
        $this->assertSame([], $cuenta->fresh()->roles);
    }

    public function test_un_operador_sin_ser_administrador_no_asigna_funciones_moviles(): void
    {
        $id = $this->crearRol(['usuarios' => ['ver', 'crear', 'editar']]);
        $operador = Usuario::factory()->create(['roles' => [], 'perfil_rol_id' => $id, 'pin_hash' => '1111']);
        $this->actingAs($operador, 'operador')->post(route('admin.usuarios.store'), ['perfil_rol_id' => $id, 'username' => 'creada.por.operador', 'nombres' => 'Creada',
            'dni' => '45678913', 'pin' => '4826', 'pin_confirmation' => '4826', 'funcion_movil' => 'acopiador'])->assertSessionHasNoErrors();
        $this->assertSame([], Usuario::query()->where('username', 'creada.por.operador')->value('roles'));
    }

    public function test_roles_anteriores_con_base_conservan_las_funciones_de_sus_cuentas(): void
    {
        $anterior = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Auxiliar heredado', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{"reportes":["ver"]}', 'campos' => '{}']);
        $existente = Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $anterior, 'pin_hash' => '2468']);
        $this->get(route('admin.usuarios.create', ['perfil_rol_id' => $anterior]))->assertOk()->assertSee('conserva las funciones móviles de Acopiador')->assertDontSee('Funciones en la app móvil');
        $nueva = $this->crearCuenta($anterior);
        $this->assertSame(['acopiador'], $nueva->roles);
        $this->put(route('admin.roles.update', $anterior), ['nombre' => 'Auxiliar heredado', 'activo' => 1, 'permisos_presentes' => 1, 'permisos' => ['reportes' => ['ver'], 'auditoria' => ['ver']]])->assertSessionHasNoErrors();
        $this->assertSame('acopiador', DB::table('perfiles_roles')->where('id', $anterior)->value('base'));
        $this->assertSame(['acopiador'], $existente->fresh()->roles);
        $this->postJson('/api/movil/sesion', ['username' => $existente->username, 'pin' => '2468'])->assertOk();
    }

    public function test_rol_inactivo_no_da_acceso_al_panel(): void
    {
        $id = $this->crearRol(['reportes' => ['ver']], 'Temporal', false);
        $cuenta = Usuario::factory()->create(['roles' => [], 'perfil_rol_id' => $id]);
        $this->assertFalse($cuenta->accesoWeb());
        $this->actingAs($cuenta, 'operador')->get(route('admin.reportes.index'))->assertForbidden();
    }

    public function test_la_migracion_es_reversible_sin_inventar_una_base(): void
    {
        $migracion = require database_path('migrations/2026_10_28_224001_make_base_nullable_in_perfiles_roles_table.php');
        $this->crearRol(['reportes' => ['ver']]);
        try {
            $migracion->down();
            $this->fail('La reversión debía detenerse con roles sin base.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sin perfil base', $e->getMessage());
        }
        $this->assertSame(1, DB::table('perfiles_roles')->whereNull('base')->count());
        DB::table('perfiles_roles')->delete();
        $migracion->down();
        $migracion->up();
        $this->assertDatabaseCount('perfiles_roles', 0);
    }
}
