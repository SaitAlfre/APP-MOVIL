<?php

namespace Tests\Feature;

use App\Domain\Auditoria\Auditoria as RegistroAuditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/** Suspender acceso de una cuenta Proveedor suspende también su ficha vinculada y restaurar solo revierte esa causa. */
class SuspensionConjuntaProveedorTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $cuenta;

    private Proveedor $ficha;

    private Proveedor $otraFicha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->cuenta = Usuario::factory()->create(['nombres' => 'Cuenta DEDDDD', 'roles' => ['proveedor'], 'pin_hash' => '2468']);
        $this->ficha = Proveedor::factory()->create(['nombres' => 'Ficha DEDDDD', 'usuario_id' => $this->cuenta->id, 'dni' => $this->cuenta->dni]);
        $this->otraFicha = Proveedor::factory()->create(['nombres' => 'Ficha ajena', 'dni' => $this->cuenta->dni === '11111111' ? '22222222' : '11111111']);
        $this->actingAs($this->admin, 'operador');
    }

    private function suspender(Usuario $cuenta, string $motivo = 'Motivo ficticio'): TestResponse
    {
        return $this->post(route('admin.usuarios.acceso', $cuenta->id), ['suspender' => 1, 'motivo' => $motivo]);
    }

    private function restaurar(Usuario $cuenta): TestResponse
    {
        return $this->post(route('admin.usuarios.acceso', $cuenta->id), ['suspender' => 0]);
    }

    public function test_suspender_acceso_suspende_cuenta_y_ficha_vinculada_y_se_ve_en_ambos_listados(): void
    {
        $this->suspender($this->cuenta)->assertSessionHasNoErrors()->assertSessionHas('estado', fn (string $m) => str_contains($m, 'ficha de proveedor también quedó Suspendida'));
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(EstadoProveedor::Suspendido, $this->ficha->fresh()->estado);
        $this->assertSame(ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA, $this->ficha->fresh()->suspension_origen);
        $this->assertSame(EstadoProveedor::Activo, $this->otraFicha->fresh()->estado);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'proveedor', 'entidad_id' => $this->ficha->id, 'usuario_id' => $this->admin->id,
            'motivo' => "Suspensión del acceso de la cuenta vinculada «{$this->cuenta->username}»: Motivo ficticio"]);
        $this->assertSame(1, Auditoria::query()->where('entidad', 'proveedor')->where('entidad_id', $this->ficha->id)->count());

        $this->get(route('admin.usuarios.index', ['estado' => 'suspendido']))->assertOk()->assertSee('Cuenta DEDDDD')->assertSee('Restaurar acceso');
        $this->get(route('admin.proveedores.index', ['estado' => 'suspendido']))->assertOk()->assertSee('Ficha DEDDDD')->assertDontSee('Ficha ajena');
        $this->get(route('admin.proveedores.index', ['estado' => 'activo']))->assertOk()->assertSee('Ficha ajena')->assertDontSee('Ficha DEDDDD');
        $this->get(route('admin.proveedores.edit', $this->ficha->id))->assertOk()->assertSee('Suspendido junto con el acceso de su cuenta');
    }

    public function test_restaurar_reactiva_la_ficha_suspendida_por_la_misma_accion_y_exige_login_nuevo(): void
    {
        $sesion = $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertOk()->json();
        $this->suspender($this->cuenta)->assertSessionHasNoErrors();
        $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertForbidden();
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();

        $this->restaurar($this->cuenta)->assertSessionHasNoErrors()->assertSessionHas('estado', fn (string $m) => str_contains($m, 'volvió a estar Activa'))->assertSessionMissing('aviso');
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
        $this->assertNull($this->ficha->fresh()->suspension_origen);
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->postJson('/api/movil/sesion', ['username' => $this->cuenta->username, 'pin' => '2468'])->assertOk()->assertJsonPath('usuario.accesoOperativoProveedor', true);
        $this->assertSame(2, Auditoria::query()->where('entidad', 'proveedor')->where('entidad_id', $this->ficha->id)->count());
    }

    public function test_proveedor_sin_ficha_solo_suspende_la_cuenta_y_registra_la_ausencia(): void
    {
        $sinFicha = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->suspender($sinFicha)->assertSessionHasNoErrors()->assertSessionHas('estado', fn (string $m) => str_contains($m, 'no tiene una ficha de proveedor vinculada'));
        $this->assertTrue($sinFicha->fresh()->bloqueado_manualmente);
        $this->assertSame(2, Proveedor::query()->count());
        $this->assertSame(0, Proveedor::query()->where('estado', 'suspendido')->count());
        $this->assertSame('sin_ficha', json_decode(Auditoria::query()->where('entidad', 'usuario')->where('entidad_id', $sinFicha->id)->value('valor_despues'), true)['ficha_productiva']);
    }

    public function test_cuentas_sin_rol_proveedor_no_cambian_ninguna_ficha(): void
    {
        foreach (['acopiador', 'calidad', 'admin'] as $rol) {
            $cuenta = Usuario::factory()->create(['roles' => [$rol]]);
            $ficha = Proveedor::factory()->create(['usuario_id' => $cuenta->id]);
            $this->suspender($cuenta)->assertSessionHasNoErrors();
            $this->assertTrue($cuenta->fresh()->bloqueado_manualmente);
            $this->assertSame(EstadoProveedor::Activo, $ficha->fresh()->estado);
        }
        $this->assertSame(0, Proveedor::query()->where('estado', '!=', 'activo')->count());
    }

    public function test_ficha_retirada_conserva_retirado_y_su_cuenta_no_se_restaura_sin_reactivarla(): void
    {
        $this->ficha->update(['estado' => 'retirado']);
        $this->suspender($this->cuenta)->assertSessionHasNoErrors()->assertSessionHas('aviso', fn (string $m) => str_contains($m, 'Retirada'));
        $this->assertSame(EstadoProveedor::Retirado, $this->ficha->fresh()->estado);
        $this->restaurar($this->cuenta)->assertSessionHasErrors('motivo');
        $this->assertSame(EstadoProveedor::Retirado, $this->ficha->fresh()->estado);
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
    }

    public function test_suspension_independiente_de_la_ficha_no_se_sobrescribe_ni_se_levanta_al_restaurar(): void
    {
        $this->put(route('admin.proveedores.update', $this->ficha->id), ['nombres' => 'Ficha DEDDDD', 'dni' => $this->ficha->dni, 'zona_id' => $this->ficha->zona_id,
            'estado' => 'suspendido', 'motivo_estado' => 'Sanción ficticia de calidad'])->assertSessionHasNoErrors();
        $this->suspender($this->cuenta)->assertSessionHasNoErrors()->assertSessionHas('aviso', fn (string $m) => str_contains($m, 'ya estaba Suspendida por otra causa'));
        $this->assertNull($this->ficha->fresh()->suspension_origen);
        $this->restaurar($this->cuenta)->assertSessionHasNoErrors()->assertSessionHas('aviso', fn (string $m) => str_contains($m, 'sigue Suspendida'));
        $this->assertSame(EstadoProveedor::Suspendido, $this->ficha->fresh()->estado);
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'proveedor', 'entidad_id' => $this->ficha->id, 'motivo' => 'Sanción ficticia de calidad']);
    }

    public function test_suspension_historica_sin_origen_no_se_reactiva_automaticamente(): void
    {
        $this->ficha->update(['estado' => 'suspendido']);
        $this->cuenta->update(['bloqueado_manualmente' => true]);
        $this->restaurar($this->cuenta)->assertSessionHasNoErrors()->assertSessionHas('aviso');
        $this->assertSame(EstadoProveedor::Suspendido, $this->ficha->fresh()->estado);
    }

    public function test_cambiar_el_estado_en_proveedores_vuelve_independiente_la_decision_y_editar_datos_la_conserva(): void
    {
        $this->suspender($this->cuenta)->assertSessionHasNoErrors();
        $datos = ['nombres' => 'Ficha DEDDDD editada', 'dni' => $this->ficha->dni, 'zona_id' => $this->ficha->zona_id];
        $this->put(route('admin.proveedores.update', $this->ficha->id), [...$datos, 'estado' => 'suspendido'])->assertSessionHasNoErrors();
        $this->assertSame(ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA, $this->ficha->fresh()->suspension_origen);
        $this->put(route('admin.proveedores.update', $this->ficha->id), [...$datos, 'estado' => 'retirado'])->assertSessionHasNoErrors();
        $this->assertNull($this->ficha->fresh()->suspension_origen);
        $this->restaurar($this->cuenta)->assertSessionHasErrors('motivo');
        $this->assertSame(EstadoProveedor::Retirado, $this->ficha->fresh()->estado);
        $this->assertTrue($this->cuenta->fresh()->bloqueado_manualmente);
    }

    public function test_un_error_durante_la_operacion_no_deja_estados_parciales(): void
    {
        $real = app(AuditoriaRepositoryInterface::class);
        $this->app->instance(AuditoriaRepositoryInterface::class, new class($real) implements AuditoriaRepositoryInterface
        {
            public function __construct(private AuditoriaRepositoryInterface $real) {}

            public function registrar(RegistroAuditoria $auditoria): void
            {
                if ($auditoria->entidad === 'usuario') {
                    throw new RuntimeException('Fallo simulado de auditoría.');
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
        $this->suspender($this->cuenta)->assertSessionHasErrors('motivo');
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame(EstadoProveedor::Activo, $this->ficha->fresh()->estado);
        $this->assertNull($this->ficha->fresh()->suspension_origen);
        $this->assertSame(0, Auditoria::query()->count());
    }

    public function test_esquema_sin_migrar_no_deja_cambios_parciales_y_registra_la_causa_sin_datos_sensibles(): void
    {
        Schema::table('proveedores', fn (Blueprint $tabla) => $tabla->dropColumn('suspension_origen'));
        Log::spy();
        $this->suspender($this->cuenta, 'Motivo confidencial ficticio')->assertSessionHasErrors('motivo');
        $this->assertFalse($this->cuenta->fresh()->bloqueado_manualmente);
        $this->assertSame('activo', DB::table('proveedores')->where('id', $this->ficha->id)->value('estado'));
        $this->assertSame(0, Auditoria::query()->count());
        Log::shouldHaveReceived('error')->withArgs(fn (string $mensaje, array $contexto) => $mensaje === 'Error de persistencia'
            && str_contains((string) $contexto['detalle'], 'suspension_origen')
            && ! str_contains(json_encode($contexto), 'Motivo confidencial'))->once();
    }

    public function test_el_alta_de_una_ficha_no_escribe_el_origen_de_suspension(): void
    {
        Schema::table('proveedores', fn (Blueprint $tabla) => $tabla->dropColumn('suspension_origen'));
        $this->post(route('admin.proveedores.store'), ['dni' => '45678912', 'nombres' => 'Ficha nueva ficticia', 'zona_id' => $this->ficha->zona_id])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseHas('proveedores', ['dni' => '45678912', 'estado' => 'activo', 'usuario_id' => null]);
    }

    public function test_menus_y_modales_explican_el_efecto_sobre_la_ficha(): void
    {
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Suspender acceso')->assertSee('Su ficha de proveedor también quedará Suspendida');
        $this->suspender($this->cuenta)->assertSessionHasNoErrors();
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Restaurar acceso')->assertSee('se reactivará, porque se suspendió junto con esta cuenta');
    }
}
