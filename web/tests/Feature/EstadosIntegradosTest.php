<?php

namespace Tests\Feature;

use App\Application\Proveedores\ActualizarProveedorUseCase;
use App\Application\Proveedores\DatosProveedor;
use App\Application\Usuarios\GestionarAccesoUsuarioUseCase;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class EstadosIntegradosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $cuenta;

    private Proveedor $ficha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->cuenta = Usuario::factory()->create(['roles' => ['proveedor'], 'pin_hash' => '2468']);
        $this->ficha = Proveedor::factory()->create(['usuario_id' => $this->cuenta->id, 'dni' => $this->cuenta->dni]);
        $this->actingAs($this->admin, 'operador');
    }

    private function datos(array $cambios = []): array
    {
        return array_replace(['nombres' => 'Proveedor de prueba actualizado', 'dni' => $this->ficha->dni,
            'telefono' => '900000001', 'zona_id' => $this->ficha->zona_id, 'estado' => 'activo'], $cambios);
    }

    private function sesion(Usuario $usuario): array
    {
        return $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468'])->assertOk()->json();
    }

    public function test_guardado_principal_persiste_datos_y_todas_las_transiciones_sin_tocar_otra_ficha(): void
    {
        $otra = Proveedor::factory()->create();
        foreach (['suspendido', 'retirado', 'activo'] as $estado) {
            $this->put(route('admin.proveedores.update', $this->ficha->id), $this->datos(['estado' => $estado, 'motivo_estado' => 'Decisión administrativa de prueba']))
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.proveedores.index'));
            $this->assertDatabaseHas('proveedores', ['id' => $this->ficha->id, 'estado' => $estado, 'telefono' => '900000001', 'nombres' => 'Proveedor de prueba actualizado']);
            $this->get(route('admin.proveedores.index', ['estado' => $estado]))->assertOk()->assertSee($this->ficha->codigo);
            $contenido = $this->get(route('admin.proveedores.edit', $this->ficha->id))->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/value="'.$estado.'"\s+selected/', $contenido);
        }
        $this->assertSame($otra->getRawOriginal('estado'), $otra->fresh()->getRawOriginal('estado'));
        $this->assertSame($this->cuenta->activo, $this->cuenta->fresh()->activo);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'proveedor', 'entidad_id' => $this->ficha->id, 'usuario_id' => $this->admin->id, 'motivo' => 'Decisión administrativa de prueba']);
    }

    public function test_selector_lateral_pertenece_al_formulario_principal_sin_boton_redundante(): void
    {
        $contenido = $this->get(route('admin.proveedores.edit', $this->ficha->id))->assertOk()->assertDontSee('Actualizar estado')->getContent();
        $dom = new \DOMDocument;
        $dom->loadHTML($contenido, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//form[@id="proveedor-formulario"]')->length);
        $this->assertSame(1, $xpath->query('//select[@name="estado" and @form="proveedor-formulario"]')->length);
        $this->assertSame(0, $xpath->query('//form//form')->length);
        $this->assertSame(1, $xpath->query('//textarea[@name="motivo_estado" and @form="proveedor-formulario"]')->length);
    }

    public function test_estado_invalido_no_guarda_datos_ni_audita_exito(): void
    {
        $this->put(route('admin.proveedores.update', $this->ficha->id), $this->datos(['estado' => 'eliminado']))->assertSessionHasErrors('estado');
        $this->assertSame($this->ficha->nombres, $this->ficha->fresh()->nombres);
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
    }

    public function test_fallo_del_estado_revierte_tambien_los_datos_y_su_auditoria(): void
    {
        $auditoriaReal = app(AuditoriaRepositoryInterface::class);
        $this->mock(AuditoriaRepositoryInterface::class, function ($mock) use ($auditoriaReal): void {
            $mock->shouldReceive('registrar')->andReturnUsing(function ($registro) use ($auditoriaReal): void {
                if (str_contains($registro->valorDespues ?? '', '"estado":"suspendido"')) {
                    throw new RuntimeException('Fallo simulado');
                }
                $auditoriaReal->registrar($registro);
            });
        });
        $antes = Auditoria::count();
        try {
            app(ActualizarProveedorUseCase::class)->ejecutar($this->ficha->id, new DatosProveedor(
                codigo: $this->ficha->codigo, nombres: 'No debe persistir', dni: $this->ficha->dni,
                telefono: null, direccion: null, zonaId: $this->ficha->zona_id, tachos: 1, capacidadTachoL: 40,
            ), EstadoProveedor::Suspendido);
            $this->fail('Debe fallar el estado.');
        } catch (RuntimeException $e) {
            $this->assertSame('Fallo simulado', $e->getMessage());
        }
        $this->assertSame($this->ficha->nombres, $this->ficha->fresh()->nombres);
        $this->assertSame($antes, Auditoria::count());
    }

    public function test_patch_heredado_sigue_guardando_estado_y_motivo(): void
    {
        $this->patch(route('admin.proveedores.estado', $this->ficha->id), ['estado' => 'suspendido', 'motivo_estado' => 'Ruta compatible'])->assertSessionHasNoErrors();
        $this->assertSame(EstadoProveedor::Suspendido, $this->ficha->fresh()->estado);
        $this->assertDatabaseHas('auditorias', ['entidad_id' => $this->ficha->id, 'motivo' => 'Ruta compatible']);
    }

    public function test_suspension_revoca_web_access_y_refresh_suspende_ficha_y_restaurar_exige_login_nuevo(): void
    {
        $sesion = $this->sesion($this->cuenta);
        DB::table('sessions')->insert(['id' => 'sesion-ficticia', 'user_id' => $this->cuenta->id, 'payload' => '', 'last_activity' => time()]);
        $this->from('/admin/usuarios?buscar=prueba')->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 1, 'motivo' => 'Suspensión ficticia'])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/usuarios?buscar=prueba');
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(EstadoProveedor::Suspendido, $this->ficha->fresh()->estado);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-ficticia']);
        $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertForbidden();
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $this->cuenta->id, 'motivo' => 'Suspensión ficticia', 'usuario_id' => $this->admin->id]);
        $this->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 0])->assertSessionHasNoErrors();
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
        $nueva = $this->sesion($this->cuenta);
        $this->assertNotSame($sesion['token'], $nueva['token']);
        $this->withToken($nueva['token'])->getJson('/api/movil/datos')->assertOk();
    }

    public function test_motivo_es_obligatorio_y_protege_al_ultimo_administrador(): void
    {
        $perfil = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Perfil basado en Admin sin facultades oficiales', 'base' => 'admin', 'activo' => true, 'permisos' => '{}', 'campos' => '{}']);
        Usuario::factory()->create(['roles' => ['admin'], 'perfil_rol_id' => $perfil]);
        $this->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 1])->assertSessionHasErrors('motivo');
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->post(route('admin.usuarios.acceso', $this->admin->id), ['suspender' => 1, 'motivo' => 'Prueba'])->assertSessionHasErrors('motivo');
        $this->assertFalse($this->admin->fresh()->bloqueado_manualmente);
    }

    public function test_restaurar_legacy_inactivo_conserva_bloqueo_temporal_y_no_procede_con_ficha_retirada(): void
    {
        $hasta = now()->addMinutes(20)->startOfSecond();
        $this->cuenta->update(['activo' => false, 'bloqueado_manualmente' => true, 'bloqueado_hasta' => $hasta, 'intentos_fallidos' => 5]);
        $this->ficha->update(['estado' => 'retirado']);
        $this->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 0])->assertSessionHasErrors('motivo');
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->ficha->update(['estado' => 'activo']);
        $this->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 0])->assertSessionHasNoErrors();
        $actual = $this->cuenta->fresh();
        $this->assertTrue($actual->activo);
        $this->assertFalse($actual->bloqueado_manualmente);
        $this->assertTrue($actual->bloqueado_hasta->equalTo($hasta));
        $this->assertSame(5, $actual->intentos_fallidos);
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
        $this->postJson('/api/movil/sesion', ['username' => $actual->username, 'pin' => '2468'])->assertForbidden();
    }

    public function test_reactivar_ficha_restaura_la_cuenta_suspendida_por_el_enlace_y_listados_muestran_ambas_restricciones(): void
    {
        app(GestionarAccesoUsuarioUseCase::class)->ejecutar($this->cuenta->id, true, 'Prueba', $this->admin->id);
        $this->ficha->update(['estado' => 'retirado']);
        $this->get('/admin/usuarios?estado=suspendido')->assertOk()->assertSee('>Suspendido', false)->assertDontSee('Suspendido administrativamente')->assertSee('Restaurar acceso');
        $this->get(route('admin.usuarios.edit', $this->cuenta->id))->assertOk()->assertSee('Acceso operativo restringido por retiro');
        $this->get('/admin/proveedores')->assertOk()->assertSee('Cuenta suspendida')->assertSee('Acceso operativo restringido por retiro');
        $this->put(route('admin.proveedores.update', $this->ficha->id), $this->datos())->assertSessionHasNoErrors();
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
    }

    public function test_pin_requiere_confirmacion_no_restaura_cuenta_y_no_se_audita_en_claro(): void
    {
        app(GestionarAccesoUsuarioUseCase::class)->ejecutar($this->cuenta->id, true, 'Prueba', $this->admin->id);
        $this->post(route('admin.usuarios.restablecer', $this->cuenta->id), ['pin' => '1357'])->assertSessionHasErrors('pin_confirmation');
        $this->post(route('admin.usuarios.restablecer', $this->cuenta->id), ['pin' => '1357', 'pin_confirmation' => '1357'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('1357', $this->cuenta->fresh()->pin_hash));
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertStringNotContainsString('1357', Auditoria::all()->toJson());
    }

    public function test_ficha_no_activa_conserva_consulta_propia_pero_login_y_refresh_no_dan_acceso_operativo(): void
    {
        foreach (['suspendido', 'retirado'] as $estado) {
            $this->ficha->update(['estado' => $estado]);
            $sesion = $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])
                ->assertOk()->assertJsonPath('usuario.accesoOperativoProveedor', false)->json();
            $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertOk()->assertJsonCount(1, 'proveedores');
            $this->withToken($sesion['token'])->getJson('/api/movil/proveedor/liquidaciones')->assertOk();
            $this->withToken($sesion['token'])->putJson('/api/movil/cambios/traslado', ['uuid' => 'traslado-prueba', 'proveedorCodigo' => $this->ficha->codigo, 'zonaDestinoNombre' => 'Otra', 'motivo' => 'Prueba', 'estado' => 'PENDIENTE'])
                ->assertForbidden()->assertJsonPath('codigo', 'proveedor_no_activo');
            $renovada = $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertOk()->assertJsonPath('usuario.accesoOperativoProveedor', false)->json();
            $this->withToken($renovada['token'])->putJson('/api/movil/cambios/traslado', ['uuid' => 'traslado-prueba', 'proveedorCodigo' => $this->ficha->codigo, 'zonaDestinoNombre' => 'Otra', 'motivo' => 'Prueba', 'estado' => 'PENDIENTE'])->assertForbidden();
        }
    }

    public function test_otros_roles_conservan_su_acceso_y_usuarios_sin_permiso_no_suspenden(): void
    {
        $this->cuenta->update(['roles' => ['proveedor', 'acopiador']]);
        $this->ficha->update(['estado' => 'retirado']);
        $sesion = $this->sesion($this->cuenta);
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertOk();
        $this->actingAs(Usuario::factory()->create(['roles' => ['calidad']]), 'operador')
            ->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 1, 'motivo' => 'No autorizado'])->assertForbidden();
    }

    public function test_ninguna_cuenta_y_lapiz_menu_y_modal_se_conservan(): void
    {
        $sinCuenta = Proveedor::factory()->create();
        $this->get('/admin/proveedores')->assertOk()->assertSee('Sin cuenta móvil');
        $this->get('/admin/proveedores/'.$sinCuenta->id.'/editar')->assertOk()->assertSee('Crear cuenta móvil');
        $this->get('/admin/usuarios')->assertOk()->assertSee('Restablecer PIN')->assertSee('Suspender acceso')->assertDontSee('>Desactivar<', false)->assertDontSee('>Bloquear<', false);
        $this->get(route('admin.usuarios.edit', $this->cuenta->id))->assertOk()->assertSee('Proveedor asociado');
    }
}
