<?php

namespace Tests\Feature;

use App\Application\Movil\ExportarDatosMovilQuery;
use App\Domain\Usuarios\Rol;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\TokenMovil;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolesCompatiblesCoder1Test extends TestCase
{
    use RefreshDatabase;

    private const RETIRADOS = ['asistente', 'produccion', 'despacho', 'recepcion', 'liquidaciones', 'consulta'];

    private function migracion(): Migration
    {
        return require database_path('migrations/2026_10_25_231000_archive_roles_retirados_usuarios.php');
    }

    public function test_api_y_laravel_reconocen_cuatro_roles_y_los_permisos_no_incluyen_retirados(): void
    {
        $oficiales = array_map(fn (Rol $rol) => $rol->value, Rol::cases());
        $this->assertEqualsCanonicalizing(['admin', 'acopiador', 'calidad', 'proveedor'], $oficiales);
        $this->assertEqualsCanonicalizing(ExportarDatosMovilQuery::ROLES_MOVIL, $oficiales);
        foreach (config('permisos') as $modulo) {
            foreach ($modulo as $roles) {
                $this->assertSame([], array_values(array_diff($roles, $oficiales)));
            }
        }
    }

    public function test_formularios_solo_ofrecen_cuatro_roles_sin_etiquetas_obsoletas(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $cuenta = Usuario::factory()->create(['roles' => ['calidad']]);
        foreach (['/admin/usuarios/nuevo', route('admin.usuarios.edit', $cuenta->id)] as $url) {
            $respuesta = $this->get($url)->assertOk();
            $this->assertSame(4, substr_count($respuesta->getContent(), 'name="roles[]"'));
            foreach (self::RETIRADOS as $retirado) {
                $respuesta->assertDontSee('value="'.$retirado.'"', false);
            }
            $respuesta->assertDontSee('pendiente de homologación');
        }
    }

    public function test_web_y_api_rechazan_retirados_incluso_junto_a_un_rol_oficial(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '2468']);
        $this->actingAs($admin, 'operador');
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        foreach (self::RETIRADOS as $rol) {
            $datos = ['username' => 'nuevo_'.$rol, 'nombres' => 'Cuenta ficticia', 'dni' => '99007777', 'pin' => '1357', 'roles' => ['calidad', $rol]];
            $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasErrors('roles.1');
            $this->withToken($token)->putJson('/api/movil/cambios/usuario', [...$datos, 'activo' => true])
                ->assertUnprocessable()->assertJsonValidationErrors('roles.1');
            $this->assertDatabaseMissing('usuarios', ['username' => 'nuevo_'.$rol]);
        }
    }

    public function test_retirados_no_ingresan_no_renuevan_y_no_se_convierten(): void
    {
        foreach (self::RETIRADOS as $rol) {
            $cuenta = Usuario::factory()->create(['roles' => [$rol], 'pin_hash' => '2468']);
            [$registro, $token, $refresh] = TokenMovil::emitir($cuenta, 'fixture');
            $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '2468'])->assertUnauthorized();
            $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
            $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $refresh])->assertUnauthorized();
            $this->assertSame([$rol], $cuenta->fresh()->roles);
            $this->assertFalse($cuenta->tieneRol($rol));
            $this->assertFalse($cuenta->accesoWeb());
        }
    }

    public function test_migracion_conserva_cuenta_pin_zona_ficha_y_roles_originales_sin_conceder_admin(): void
    {
        $zona = Zona::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => self::RETIRADOS, 'zona_id' => $zona->id]);
        $hash = $cuenta->pin_hash;
        $ficha = Proveedor::factory()->create(['usuario_id' => $cuenta->id, 'zona_id' => $zona->id]);
        $this->migracion()->up();
        $this->assertSame([], $cuenta->fresh()->roles);
        $this->assertTrue($cuenta->fresh()->activo);
        $this->assertSame($hash, $cuenta->fresh()->pin_hash);
        $this->assertSame($zona->id, $cuenta->fresh()->zona_id);
        $this->assertSame($cuenta->id, $ficha->fresh()->usuario_id);
        $archivo = DB::table('archivo_roles_usuarios')->where('usuario_id', $cuenta->id)->sole();
        $this->assertSame(self::RETIRADOS, json_decode($archivo->roles_antes, true));
        $this->assertSame(self::RETIRADOS, json_decode($archivo->roles_retirados, true));
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame('ok', DB::select('PRAGMA integrity_check')[0]->integrity_check);
        $this->assertDatabaseCount('usuarios', 1);
        $this->migracion()->down();
        $this->assertSame(self::RETIRADOS, $cuenta->fresh()->roles);
        $this->assertSame($cuenta->id, $ficha->fresh()->usuario_id);
        $this->assertDatabaseCount('archivo_roles_usuarios', 1);
    }

    public function test_mixtos_conservan_solo_oficiales_y_revocan_sesiones_sin_duplicar_archivo(): void
    {
        $cuenta = Usuario::factory()->create(['roles' => ['calidad', 'recepcion']]);
        TokenMovil::emitir($cuenta, 'fixture');
        $this->migracion()->up();
        $this->migracion()->up();
        $this->assertSame(['calidad'], $cuenta->fresh()->roles);
        $this->assertDatabaseMissing('tokens_movil', ['usuario_id' => $cuenta->id]);
        $this->assertDatabaseCount('archivo_roles_usuarios', 1);
    }

    public function test_reversion_no_pisa_asignaciones_posteriores(): void
    {
        $cuenta = Usuario::factory()->create(['roles' => ['produccion']]);
        $this->migracion()->up();
        $cuenta->update(['roles' => ['calidad']]);
        try {
            $this->migracion()->down();
            $this->fail('La reversión debía pedir revisión de la asignación posterior.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('asignaciones posteriores', $error->getMessage());
        }
        $this->assertSame(['calidad'], $cuenta->fresh()->roles);
    }

    public function test_no_crea_mezclas_proveedor_personal_y_conserva_las_heredadas(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $this->post(route('admin.usuarios.store'), [
            'username' => 'mezcla_nueva', 'nombres' => 'Cuenta ficticia', 'dni' => '99007777', 'pin' => '2468', 'roles' => ['proveedor', 'calidad'],
        ])->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('usuarios', ['username' => 'mezcla_nueva']);
        $legada = Usuario::factory()->create(['roles' => ['proveedor', 'calidad']]);
        $this->put(route('admin.usuarios.update', $legada->id), [
            'nombres' => 'Dato conservado', 'dni' => $legada->dni, 'roles' => ['calidad', 'proveedor'],
        ])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing(['proveedor', 'calidad'], $legada->fresh()->roles);
    }

    public function test_modulos_conservados_son_accesibles_al_admin_y_no_otorgan_gestion_a_calidad(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        foreach (['/admin/recepcion', '/admin/produccion', '/admin/inventario', '/admin/ventas', '/admin/liquidaciones'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs(Usuario::factory()->create(['roles' => ['calidad']]), 'operador');
        foreach (['/admin/usuarios', '/admin/inventario', '/admin/ventas'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_cuenta_sin_rol_oficial_requiere_asignacion_y_no_se_anuncia_como_movil(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        Usuario::factory()->create(['roles' => [], 'nombres' => 'Cuenta sin perfil']);
        $this->actingAs($admin, 'operador')->get(route('admin.usuarios.index'))
            ->assertOk()->assertSee('Requiere asignación')->assertSee('Sin perfil asignado');
    }
}
