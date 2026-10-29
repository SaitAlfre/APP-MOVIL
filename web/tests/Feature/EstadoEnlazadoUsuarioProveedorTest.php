<?php

namespace Tests\Feature;

use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Domain\Auditoria\Auditoria as RegistroAuditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/** El estado de la ficha y el de su cuenta de proveedor están enlazados en ambas direcciones. */
class EstadoEnlazadoUsuarioProveedorTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $cuenta;

    private Proveedor $ficha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->cuenta = Usuario::factory()->create(['nombres' => 'Cuenta enlazada', 'roles' => ['proveedor'], 'pin_hash' => '2468']);
        $this->ficha = Proveedor::factory()->create(['nombres' => 'Ficha enlazada', 'usuario_id' => $this->cuenta->id, 'dni' => $this->cuenta->dni]);
        $this->actingAs($this->admin, 'operador');
    }

    private function cambiarFicha(string $estado, ?string $motivo = null): TestResponse
    {
        return $this->put(route('admin.proveedores.update', $this->ficha->id), ['nombres' => $this->ficha->nombres, 'dni' => $this->ficha->dni,
            'zona_id' => $this->ficha->zona_id, 'estado' => $estado, 'motivo_estado' => $motivo]);
    }

    private function token(): array
    {
        return $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertOk()->json();
    }

    public function test_suspender_la_ficha_suspende_la_cuenta_y_reactivarla_la_restaura(): void
    {
        $sesion = $this->token();
        $otraCuenta = Usuario::factory()->create(['roles' => ['proveedor']]);
        $otraFicha = Proveedor::factory()->create(['usuario_id' => $otraCuenta->id, 'dni' => $otraCuenta->dni]);

        $this->cambiarFicha('suspendido', 'Leche adulterada (ficticio)')->assertSessionHasNoErrors()
            ->assertSessionHas('estado', fn (string $m) => str_contains($m, 'cuenta móvil vinculada también quedó Suspendida'));
        $cuenta = $this->cuenta->fresh();
        $this->assertTrue($cuenta->bloqueado_manualmente);
        $this->assertSame(UsuarioRepositoryInterface::ORIGEN_FICHA_PROVEEDOR, $cuenta->suspension_origen);
        $this->assertStringContainsString('Leche adulterada (ficticio)', $cuenta->motivo_bloqueo);
        $this->assertSame($this->admin->id, $cuenta->bloqueado_por);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $this->cuenta->id, 'usuario_id' => $this->admin->id]);
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
        $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertForbidden();

        $this->get(route('admin.usuarios.index', ['estado' => 'suspendido']))->assertOk()->assertSee('Cuenta enlazada');
        $this->get(route('admin.proveedores.index', ['estado' => 'suspendido']))->assertOk()->assertSee('Ficha enlazada')->assertSee('Cuenta suspendida');
        $this->assertFalse((bool) $otraCuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(EstadoProveedor::Activo, $otraFicha->fresh()->estado);

        $this->cambiarFicha('activo')->assertSessionHasNoErrors()->assertSessionHas('estado', fn (string $m) => str_contains($m, 'volvió a estar Activa'));
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertNull($this->cuenta->fresh()->suspension_origen);
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->token();
    }

    public function test_retirar_la_ficha_suspende_la_cuenta_y_solo_se_restaura_al_reactivarla(): void
    {
        $this->patch(route('admin.proveedores.estado', $this->ficha->id), ['estado' => 'retirado', 'motivo_estado' => 'Dejó de entregar'])->assertSessionHasNoErrors();
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 0])->assertSessionHasErrors('motivo');
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('La ficha de proveedor está Retirada');

        $this->cambiarFicha('suspendido')->assertSessionHasNoErrors();
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->cambiarFicha('activo')->assertSessionHasNoErrors();
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
    }

    public function test_restaurar_la_cuenta_reactiva_la_ficha_que_la_suspendio(): void
    {
        $this->cambiarFicha('suspendido')->assertSessionHasNoErrors();
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('se reactivará, porque se suspendió junto con esta cuenta');
        $this->post(route('admin.usuarios.acceso', $this->cuenta->id), ['suspender' => 0])->assertSessionHasNoErrors()
            ->assertSessionHas('estado', fn (string $m) => str_contains($m, 'volvió a estar Activa'));
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
    }

    public function test_suspension_independiente_de_la_cuenta_no_se_levanta_al_reactivar_la_ficha(): void
    {
        $this->cuenta->update(['bloqueado_manualmente' => true, 'motivo_bloqueo' => 'Suspensión anterior']);
        $this->cambiarFicha('suspendido')->assertSessionHasNoErrors();
        $this->cambiarFicha('activo')->assertSessionHasNoErrors();
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame('Suspensión anterior', $this->cuenta->fresh()->motivo_bloqueo);
    }

    public function test_cuenta_con_otros_roles_conserva_su_acceso(): void
    {
        $this->cuenta->update(['roles' => ['proveedor', 'acopiador']]);
        $this->get(route('admin.proveedores.edit', $this->ficha->id))->assertOk()->assertSee('tiene además otros roles');
        $this->cambiarFicha('retirado')->assertSessionHasNoErrors();
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertOk();
    }

    public function test_vincular_una_cuenta_a_una_ficha_suspendida_la_suspende(): void
    {
        $ficha = Proveedor::factory()->create(['estado' => 'suspendido']);
        $cuenta = Usuario::factory()->create(['roles' => ['proveedor'], 'dni' => $ficha->dni]);
        $this->post("/admin/proveedores/{$ficha->id}/vincular", ['usuario_id' => $cuenta->id])->assertSessionHasNoErrors();
        $this->assertSame($cuenta->id, $ficha->fresh()->usuario_id);
        $this->assertTrue($cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(UsuarioRepositoryInterface::ORIGEN_FICHA_PROVEEDOR, $cuenta->fresh()->suspension_origen);
    }

    public function test_la_app_de_administracion_tambien_enlaza_el_estado(): void
    {
        $token = $this->postJson('/api/movil/sesion', ['username' => $this->admin->username, 'pin' => '1234'])->assertOk()->json('token');
        $this->withToken($token)->putJson('/api/movil/cambios/proveedor', ['servidorId' => $this->ficha->id, 'codigo' => $this->ficha->codigo, 'nombres' => $this->ficha->nombres,
            'dni' => $this->ficha->dni, 'zonaId' => $this->ficha->zona_id, 'zonaNombre' => $this->ficha->zona->nombre, 'tachos' => $this->ficha->tachos, 'capacidadTachoL' => $this->ficha->capacidad_tacho_l,
            'estado' => 'SUSPENDIDO', 'usuarioUsername' => $this->cuenta->username])->assertOk();
        $this->assertSame(EstadoProveedor::Suspendido, $this->ficha->fresh()->estado);
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
    }

    public function test_si_falla_la_cuenta_no_cambia_la_ficha(): void
    {
        $real = app(AuditoriaRepositoryInterface::class);
        $this->app->instance(AuditoriaRepositoryInterface::class, new class($real) implements AuditoriaRepositoryInterface
        {
            public function __construct(private AuditoriaRepositoryInterface $real) {}

            public function registrar(RegistroAuditoria $auditoria): void
            {
                if ($auditoria->entidad === 'usuario') {
                    throw new RuntimeException('Fallo simulado.');
                }
                $this->real->registrar($auditoria);
            }

            public function paginar(int $porPagina = 25, array $filtros = []): LengthAwarePaginator
            {
                return $this->real->paginar($porPagina, $filtros);
            }

            public function buscarPorId(int $id): ?RegistroAuditoria
            {
                return $this->real->buscarPorId($id);
            }
        });
        try {
            app(CambiarEstadoProveedorUseCase::class)->ejecutar($this->ficha->id, EstadoProveedor::Suspendido, 'Prueba', $this->admin->id);
            $this->fail('Debía fallar.');
        } catch (RuntimeException $e) {
            $this->assertSame('Fallo simulado.', $e->getMessage());
        }
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(0, Auditoria::query()->where('entidad', 'usuario')->count());
    }
}
