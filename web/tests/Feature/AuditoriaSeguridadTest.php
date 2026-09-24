<?php

namespace Tests\Feature;

use App\Application\Usuarios\ActualizarUsuarioUseCase;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuditoriaSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        $usuario = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '1234']);
        $this->actingAs($usuario, 'operador');

        return $usuario;
    }

    private function registro(int $usuarioId, array $datos = []): Auditoria
    {
        return Auditoria::query()->create($datos + [
            'entidad' => 'recepcion_acopio', 'entidad_id' => 1, 'usuario_id' => $usuarioId,
            'accion' => 'corregir', 'valor_antes' => '{"litros":100}', 'valor_despues' => '{"litros":95}',
            'motivo' => 'Derrame', 'ocurrido_en' => now(),
        ]);
    }

    public function test_filtra_por_responsable_entidad_accion_y_periodo_con_paginacion(): void
    {
        $admin = $this->admin();
        $otro = Usuario::factory()->create();
        for ($i = 0; $i < 27; $i++) {
            $this->registro($admin->id, ['motivo' => 'Incluido '.$i]);
        }
        $this->registro($otro->id, ['motivo' => 'Otro responsable']);
        $this->registro($admin->id, ['entidad' => 'sesion', 'motivo' => 'Otra entidad']);
        $this->registro($admin->id, ['accion' => 'crear', 'motivo' => 'Otra acción']);
        $this->registro($admin->id, ['ocurrido_en' => now()->subDays(3), 'motivo' => 'Otra fecha']);
        $filtros = ['usuario_id' => $admin->id, 'entidad' => 'recepcion_acopio', 'accion' => 'corregir', 'desde' => now()->toDateString(), 'hasta' => now()->toDateString()];
        $this->get(route('admin.auditoria.index', $filtros))->assertOk()
            ->assertViewHas('paginador', fn ($p) => $p->total() === 27 && str_contains($p->url(2), 'entidad=recepcion_acopio'))
            ->assertDontSee('Otro responsable')->assertDontSee('Otra entidad')->assertDontSee('Otra acción')->assertDontSee('Otra fecha');
        $this->get(route('admin.auditoria.index', $filtros + ['page' => 2]))->assertViewHas('paginador', fn ($p) => $p->count() === 2);
    }

    public function test_detalle_escapa_html_y_oculta_secretos_incluso_en_registros_antiguos(): void
    {
        $admin = $this->admin();
        $registro = $this->registro($admin->id);
        DB::table('auditorias')->where('id', $registro->id)->update([
            'valor_despues' => json_encode(['litros' => 95, 'pin_hash' => 'hash-no-visible', 'detalle' => ['token' => 'token-no-visible'], 'observacion' => '<script>alert(1)</script>']),
        ]);
        $this->get(route('admin.auditoria.show', $registro->id))->assertOk()->assertSee('95')
            ->assertSee('[OCULTO]')->assertDontSee('hash-no-visible')->assertDontSee('token-no-visible')
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_auditoria_admite_cambios_largos_y_sanea_datos_antes_de_guardarlos(): void
    {
        $admin = $this->admin();
        $registro = $this->registro($admin->id, ['valor_despues' => json_encode(['detalle' => str_repeat('x', 2000), 'pin' => 'secreto-prueba']), 'motivo' => 'token=secreto-motivo']);
        $this->assertGreaterThan(2000, strlen($registro->fresh()->valor_despues));
        $this->assertStringNotContainsString('secreto-prueba', $registro->fresh()->valor_despues);
        $this->assertStringNotContainsString('secreto-motivo', $registro->fresh()->motivo);
    }

    public function test_detalle_exige_permiso_y_los_filtros_se_validan(): void
    {
        $admin = $this->admin();
        $registro = $this->registro($admin->id);
        $this->get(route('admin.auditoria.show', 999999))->assertNotFound();
        $this->get(route('admin.auditoria.index', ['desde' => '2026-09-20', 'hasta' => '2026-09-19']))->assertSessionHasErrors('hasta');
        $this->actingAs(Usuario::factory()->create(['roles' => ['recepcion']]), 'operador');
        $this->get(route('admin.auditoria.show', $registro->id))->assertForbidden();
        $this->get(route('admin.auditoria.index'))->assertForbidden();
    }

    public function test_pin_no_se_conserva_en_errores_de_validacion_ni_duplicados(): void
    {
        $admin = $this->admin();
        $datos = ['username' => $admin->username, 'nombres' => 'Prueba', 'dni' => '12345678', 'pin' => '5678', 'roles' => ['consulta']];
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasErrors('username')->assertSessionMissing('_old_input.pin');
        $this->post(route('admin.usuarios.store'), array_replace($datos, ['roles' => []]))->assertSessionHasErrors('roles')->assertSessionMissing('_old_input.pin');
    }

    public function test_login_y_logout_auditados_sin_credenciales_y_limite_para_cuentas_inexistentes(): void
    {
        $usuario = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '1234']);
        $this->post('/login', ['username' => $usuario->username, 'pin' => '9876'])->assertSessionHasErrors();
        $this->post('/login', ['username' => $usuario->username, 'pin' => '1234'])->assertRedirect();
        $this->post('/logout')->assertRedirect();
        foreach (['acceso_fallido', 'iniciar_sesion', 'cerrar_sesion'] as $accion) {
            $this->assertDatabaseHas('auditorias', ['entidad' => 'sesion', 'accion' => $accion, 'usuario_id' => $usuario->id]);
        }
        $this->assertStringNotContainsString('9876', Auditoria::query()->get()->toJson());
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['username' => 'inexistente-limitado', 'pin' => '9876'])->assertRedirect();
        }
        $this->post('/login', ['username' => 'inexistente-limitado', 'pin' => '9876'])->assertStatus(429);
    }

    public function test_restablecer_revoca_sesion_anterior_aunque_no_use_tabla_sessions(): void
    {
        $admin = $this->admin();
        $usuario = Usuario::factory()->create(['roles' => ['consulta']]);
        $this->post(route('admin.usuarios.restablecer', $usuario->id), ['pin' => '5678'])->assertSessionHasNoErrors();
        $this->assertEquals(1, $usuario->fresh()->version_sesion);
        $this->actingAs($usuario->fresh(), 'operador')->withSession(['version_sesion' => [$usuario->id => 0]])
            ->get(route('admin.dashboard.index'))->assertRedirect(route('login'));
        $this->post('/login', ['username' => $usuario->username, 'pin' => '5678'])->assertSessionHasNoErrors();
        $this->get(route('admin.dashboard.index'))->assertOk();
    }

    public function test_login_rechaza_username_no_textual_sin_error_interno(): void
    {
        $this->post('/login', ['username' => ['invalido'], 'pin' => '1234'])
            ->assertSessionHasErrors('username')->assertSessionMissing('_old_input.pin');
    }

    public function test_fecha_de_auditoria_es_la_del_registro_y_no_la_llegada_declarada(): void
    {
        $this->admin();
        $entrega = Entrega::factory()->create(['litros' => 100, 'anulada' => false]);
        $this->post(route('admin.recepcion.llegada.store', $entrega->jornada_id), [
            'llegada_en' => now()->subDay()->format('Y-m-d\TH:i'),
            'litros_medidos' => 95, 'motivo_diferencia' => 'Derrame',
        ])->assertSessionHasNoErrors();
        $registro = Auditoria::query()->where('entidad', 'recepcion_acopio')->firstOrFail();
        $this->assertEquals(now()->toDateString(), $registro->ocurrido_en->format('Y-m-d'));
    }

    public function test_solo_admin_puede_quitar_admin_incluso_invocando_el_caso_de_uso(): void
    {
        $admin = $this->admin();
        Usuario::factory()->create(['roles' => ['admin']]);
        $this->expectException(UsuarioInvalidoException::class);
        app(ActualizarUsuarioUseCase::class)->ejecutar($admin->id, $admin->nombres, $admin->dni, [Rol::Consulta], false, $admin->id);
    }

    /** @return array<string, mixed> */
    private function analisisCalidad(int $proveedorId): array
    {
        return ['proveedor_id' => $proveedorId, 'fecha' => now('America/Lima')->toDateString(), 'hora' => '08:00',
            'unidad_congelacion' => '°C', 'valores' => ['grasa' => '3.5']];
    }

    public function test_calidad_se_audita_y_no_se_guarda_si_falla_la_auditoria(): void
    {
        $admin = $this->admin();
        $proveedor = Proveedor::factory()->create();
        $this->post(route('admin.calidad.store'), $this->analisisCalidad($proveedor->id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('auditorias', ['entidad' => 'analisis_calidad', 'usuario_id' => $admin->id, 'accion' => 'crear']);

        $otro = Proveedor::factory()->create();
        $this->mock(AuditoriaRepositoryInterface::class)->shouldReceive('registrar')->andThrow(new \RuntimeException('Auditoría no disponible'));
        $this->post(route('admin.calidad.store'), $this->analisisCalidad($otro->id))->assertSessionHasErrors('valores');
        $this->assertDatabaseMissing('analisis_calidad', ['proveedor_id' => $otro->id]);
    }

    public function test_errores_de_base_de_datos_no_exponen_consultas_ni_bindings(): void
    {
        $this->admin();
        $proveedor = Proveedor::factory()->create();
        Log::spy();
        $error = new QueryException('sqlite', 'INSERT secreto_sql', ['pin-secreto'], new \PDOException('Error con credenciales'));
        $this->mock(AuditoriaRepositoryInterface::class)->shouldReceive('registrar')->andThrow($error);
        $this->post(route('admin.calidad.store'), $this->analisisCalidad($proveedor->id))->assertSessionHasErrors('valores');
        $this->assertStringNotContainsString('secreto_sql', session('errors')->first('valores'));
        Log::shouldHaveReceived('error')->with('Error de persistencia', \Mockery::on(fn ($context) => ! str_contains(json_encode($context), 'secreto')))->once();
        $this->assertDatabaseMissing('analisis_calidad', ['proveedor_id' => $proveedor->id]);
    }

    public function test_liquidacion_y_pago_registran_valor_anterior_y_nuevo(): void
    {
        $admin = $this->admin();
        $entrega = Entrega::factory()->create(['litros' => 100]);
        $this->post(route('admin.liquidaciones.store'), ['proveedor_id' => $entrega->proveedor_id, 'periodo_inicio' => now()->toDateString(), 'periodo_fin' => now()->toDateString(), 'precio_litro' => 2])->assertSessionHasNoErrors();
        $id = DB::table('liquidaciones')->value('id');
        $this->patch(route('admin.liquidaciones.pagar', $id))->assertSessionHasNoErrors();
        $cambio = Auditoria::query()->where('entidad', 'liquidacion')->where('accion', 'actualizar')->firstOrFail();
        $this->assertSame('pendiente', json_decode($cambio->valor_antes, true)['estado']);
        $this->assertSame('pagada', json_decode($cambio->valor_despues, true)['estado']);
        $this->assertSame($admin->id, $cambio->usuario_id);
    }

    public function test_iniciar_lote_registra_al_actor_y_no_al_creador(): void
    {
        $admin = $this->admin();
        $creador = Usuario::factory()->create(['roles' => ['produccion']]);
        $producto = Producto::factory()->create();
        $lote = LoteProduccion::query()->create(['codigo' => 'LOTE-AUDIT', 'producto_id' => $producto->id, 'fecha' => now()->toDateString(), 'litros_por_unidad_snapshot' => 10, 'litros_asignados' => 100, 'unidades_estimadas' => 10, 'estado' => 'borrador', 'responsable_id' => $creador->id, 'origen_acopio' => []]);
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('auditorias', ['entidad' => 'lote_produccion', 'entidad_id' => $lote->id, 'usuario_id' => $admin->id, 'valor_despues' => 'estado=en_proceso']);
    }

    public function test_rutas_nuevo_no_se_interpretan_como_identificadores(): void
    {
        $this->admin();
        $this->get(route('admin.produccion.productos.create'))->assertOk();
        $this->get(route('admin.acopiadores.jornadas.create'))->assertOk();
    }
}
