<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\TokenMovil;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Entregas del celular → tabla `entregas` que consulta el panel, y liquidaciones del panel → proveedor.
 * Reproduce el flujo real de la app: token con usuario/PIN, PUT idempotente por id local.
 */
class SincronizacionMovilTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $acopiador;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $zona = Zona::factory()->create(['nombre' => 'FAON-MARKAPAJO']);
        Vehiculo::factory()->create(['placa' => 'V1A-123']);
        $this->acopiador = Usuario::factory()->create(['username' => 'acop_faon', 'pin_hash' => '2468', 'roles' => ['acopiador']]);
        $this->proveedor = Proveedor::factory()->create(['codigo' => 'PRV-FAON-01', 'zona_id' => $zona->id]);
    }

    private function token(string $username = 'acop_faon', string $pin = '2468'): string
    {
        return $this->postJson('/api/movil/sesion', ['username' => $username, 'pin' => $pin, 'dispositivo' => 'Prueba'])
            ->assertOk()->json('token');
    }

    /** @return array<string, mixed> */
    private function entrega(array $cambios = []): array
    {
        return array_merge([
            'jornadaId' => 'jor-local-1',
            'jornadaAbiertaEn' => 1_790_000_000_000,
            'jornadaCerradaEn' => null,
            'proveedorCodigo' => 'PRV-FAON-01',
            'zonaNombre' => 'FAON-MARKAPAJO',
            'vehiculoPlaca' => 'V1A-123',
            'acopiadorUsername' => 'acop_faon',
            'registradoEn' => 1_790_000_100_000,
            'litros' => 38.5,
            'tachos' => 2,
            'observaciones' => null,
            'anulada' => false,
            'motivo' => null,
            'actualizadoEn' => 1_790_000_100_000,
        ], $cambios);
    }

    private function enviar(string $token, array $datos, string $id = 'ent-local-1')
    {
        return $this->withToken($token)->putJson("/api/movil/entregas/{$id}", $datos);
    }

    public function test_entrega_pendiente_queda_visible_en_el_panel_del_administrador(): void
    {
        $token = $this->token();

        $this->enviar($token, $this->entrega())->assertCreated()->assertJson(['estado' => 'creada']);

        $entrega = Entrega::query()->where('uuid_movil', 'ent-local-1')->firstOrFail();
        $this->assertSame($this->proveedor->id, $entrega->proveedor_id);
        $this->assertSame($this->acopiador->id, $entrega->usuario_id);
        $this->assertEquals(38.5, (float) $entrega->litros);
        $this->assertSame(2, $entrega->tachos);
        $this->assertSame(1_790_000_100_000, $entrega->registrado_en->getTimestampMs());
        $jornada = Jornada::query()->where('uuid_movil', 'jor-local-1')->firstOrFail();
        $this->assertSame($jornada->id, $entrega->jornada_id);
        $this->assertSame('FAON-MARKAPAJO', $jornada->zona->nombre);
        $this->assertSame('V1A-123', $jornada->vehiculo->placa);

        // Lo que ve el administrador en el panel web.
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador')->get('/admin/entregas')->assertOk()->assertSee($this->proveedor->nombres)->assertSee('38.5 L');
    }

    public function test_reintentar_la_misma_version_no_duplica(): void
    {
        $token = $this->token();

        $this->enviar($token, $this->entrega())->assertCreated();
        $this->enviar($token, $this->entrega())->assertOk()->assertJson(['estado' => 'sin_cambios']);
        $this->enviar($token, $this->entrega(['id' => 'ent-local-2']), 'ent-local-2')->assertCreated();

        $this->assertSame(2, Entrega::query()->count());
        $this->assertSame(1, Jornada::query()->count());
        $this->assertSame(2, Auditoria::query()->where('entidad', 'entrega')->where('accion', 'crear')->count());
    }

    public function test_correccion_y_anulacion_actualizan_la_misma_fila_con_auditoria(): void
    {
        $token = $this->token();
        $this->enviar($token, $this->entrega())->assertCreated();

        $this->enviar($token, $this->entrega(['litros' => 40, 'actualizadoEn' => 1_790_000_200_000, 'motivo' => 'Se midió mal']))
            ->assertOk()->assertJson(['estado' => 'actualizada']);
        $this->enviar($token, $this->entrega(['litros' => 40, 'anulada' => true, 'actualizadoEn' => 1_790_000_300_000, 'motivo' => 'Registro duplicado']))
            ->assertOk();

        $entrega = Entrega::query()->sole();
        $this->assertEquals(40.0, (float) $entrega->litros);
        $this->assertTrue($entrega->anulada);
        $auditoria = Auditoria::query()->where('entidad', 'entrega')->where('entidad_id', $entrega->id)->orderBy('id')->get();
        $this->assertSame(['crear', 'corregir', 'anular'], $auditoria->pluck('accion')->all());
        $this->assertSame('litros=38.5;tachos=2', $auditoria[1]->valor_antes);
        $this->assertSame('litros=40;tachos=2', $auditoria[1]->valor_despues);
        $this->assertSame('Se midió mal', $auditoria[1]->motivo);
        $this->assertSame('Registro duplicado', $auditoria[2]->motivo);
    }

    public function test_una_version_atrasada_no_pisa_la_mas_reciente(): void
    {
        $token = $this->token();
        $this->enviar($token, $this->entrega(['litros' => 40, 'actualizadoEn' => 1_790_000_200_000]))->assertCreated();

        $this->enviar($token, $this->entrega())->assertStatus(409)->assertJson(['codigo' => 'version_obsoleta']);

        $this->assertEquals(40.0, (float) Entrega::query()->sole()->litros);
    }

    public function test_una_anulacion_no_se_revierte_desde_el_celular(): void
    {
        $token = $this->token();
        $this->enviar($token, $this->entrega(['anulada' => true]))->assertCreated();

        $this->enviar($token, $this->entrega(['actualizadoEn' => 1_790_000_900_000]))->assertStatus(422)->assertJson(['codigo' => 'reactivacion']);

        $this->assertTrue(Entrega::query()->sole()->anulada);
    }

    public function test_datos_que_no_existen_en_el_servidor_se_rechazan_sin_crear_nada(): void
    {
        $token = $this->token();

        $this->enviar($token, $this->entrega(['proveedorCodigo' => 'PRV-NO-EXISTE']))
            ->assertStatus(422)->assertJson(['codigo' => 'dato_no_registrado'])->assertJsonFragment(['message' => 'El proveedor «PRV-NO-EXISTE» no está registrado en el servidor. Pide al administrador que lo registre igual que en la app.']);
        $this->enviar($token, $this->entrega(['vehiculoPlaca' => 'ZZZ-000']))->assertStatus(422);
        $this->enviar($token, $this->entrega(['zonaNombre' => 'Otra']))->assertStatus(422);

        $this->assertSame(0, Entrega::query()->count());
        $this->assertSame(0, Jornada::query()->count());
    }

    public function test_sin_token_o_con_pin_incorrecto_no_se_acepta(): void
    {
        $this->putJson('/api/movil/entregas/ent-local-1', $this->entrega())->assertStatus(401)->assertJson(['codigo' => 'token_invalido']);
        $this->withToken('inventado')->putJson('/api/movil/entregas/ent-local-1', $this->entrega())->assertStatus(401);
        $this->postJson('/api/movil/sesion', ['username' => 'acop_faon', 'pin' => '0000'])->assertStatus(401);

        $this->assertSame(1, $this->acopiador->fresh()->intentos_fallidos);
        $this->assertSame(0, Entrega::query()->count());
    }

    public function test_el_token_solo_guarda_su_hash_y_deja_de_valer_al_desactivar_la_cuenta(): void
    {
        $token = $this->token();
        $this->assertDatabaseMissing('tokens_movil', ['token_hash' => $token]);
        $this->assertDatabaseHas('tokens_movil', ['token_hash' => hash('sha256', $token), 'usuario_id' => $this->acopiador->id]);

        $this->acopiador->update(['activo' => false]);

        $this->enviar($token, $this->entrega())->assertStatus(401);
    }

    public function test_un_acopiador_no_puede_enviar_entregas_a_nombre_de_otro(): void
    {
        Usuario::factory()->create(['username' => 'acop_moro', 'pin_hash' => '2468', 'roles' => ['acopiador']]);
        $token = $this->token('acop_moro');

        $this->enviar($token, $this->entrega())->assertStatus(403)->assertJson(['codigo' => 'autor_distinto']);
        $this->assertSame(0, Entrega::query()->count());
    }

    public function test_un_proveedor_no_puede_enviar_entregas(): void
    {
        Usuario::factory()->create(['username' => 'prov_faon_01', 'pin_hash' => '1234', 'roles' => ['proveedor']]);

        $this->enviar($this->token('prov_faon_01', '1234'), $this->entrega())->assertStatus(403);
    }

    /** @param  array<string, mixed>  $cambios */
    private function liquidacion(Proveedor $proveedor, array $cambios): Liquidacion
    {
        return Liquidacion::query()->create(array_merge([
            'proveedor_id' => $proveedor->id, 'periodo_inicio' => '2026-09-10', 'periodo_fin' => '2026-09-16',
            'litros_totales' => 100, 'precio_litro' => 1.5, 'monto_total' => 140, 'descuento_sanciones' => 10,
            'estado' => 'pendiente', 'generada_en' => now(),
        ], $cambios));
    }

    public function test_el_proveedor_recibe_sus_liquidaciones_con_su_estado_real_pendiente_o_pagada(): void
    {
        $cuenta = Usuario::factory()->create(['username' => 'prov_faon_01', 'pin_hash' => '1234', 'roles' => ['proveedor']]);
        $this->proveedor->update(['usuario_id' => $cuenta->id]);
        $this->liquidacion($this->proveedor, ['estado' => 'pagada', 'pagada_en' => '2026-09-18 15:00:00']);
        $this->liquidacion($this->proveedor, [
            'periodo_inicio' => '2026-09-17', 'periodo_fin' => '2026-09-23', 'litros_totales' => 80, 'precio_litro' => 1.6,
            'monto_total' => 128, 'descuento_sanciones' => 0,
        ]);

        $respuesta = $this->withToken($this->token('prov_faon_01', '1234'))->getJson('/api/movil/proveedor/liquidaciones')->assertOk();

        $respuesta->assertJsonPath('proveedorCodigo', 'PRV-FAON-01');
        $respuesta->assertJsonCount(2, 'liquidaciones');
        // Más reciente primero: la pendiente NO se presenta como aprobada ni como pagada.
        $respuesta->assertJsonPath('liquidaciones.0.desde', '2026-09-17');
        $respuesta->assertJsonPath('liquidaciones.0.estado', 'PENDIENTE');
        $respuesta->assertJsonPath('liquidaciones.0.fechaPago', null);
        $respuesta->assertJsonPath('liquidaciones.0.total', 128);
        $respuesta->assertJsonPath('liquidaciones.1.estado', 'PAGADA');
        $respuesta->assertJsonPath('liquidaciones.1.fechaPago', '2026-09-18');
        $respuesta->assertJsonPath('liquidaciones.1.precio', 1.5);
        $respuesta->assertJsonPath('liquidaciones.1.bruto', 150);
        $respuesta->assertJsonPath('liquidaciones.1.descuento', 10);
        $respuesta->assertJsonPath('liquidaciones.1.total', 140);
        $this->assertNotContains('APROBADA', $respuesta->json('liquidaciones.*.estado'));
    }

    public function test_un_proveedor_nunca_recibe_liquidaciones_de_otra_ficha(): void
    {
        $cuentaA = Usuario::factory()->create(['username' => 'prov_faon_01', 'pin_hash' => '1234', 'roles' => ['proveedor']]);
        $cuentaB = Usuario::factory()->create(['username' => 'prov_faon_02', 'pin_hash' => '1234', 'roles' => ['proveedor']]);
        $this->proveedor->update(['usuario_id' => $cuentaA->id]);
        $otro = Proveedor::factory()->create(['codigo' => 'PRV-FAON-02', 'usuario_id' => $cuentaB->id]);
        $this->liquidacion($this->proveedor, ['monto_total' => 111]);
        $this->liquidacion($otro, ['monto_total' => 999, 'estado' => 'pagada', 'pagada_en' => now()]);

        $delA = $this->withToken($this->token('prov_faon_01', '1234'))->getJson('/api/movil/proveedor/liquidaciones')->assertOk();
        $delB = $this->withToken($this->token('prov_faon_02', '1234'))->getJson('/api/movil/proveedor/liquidaciones')->assertOk();

        $this->assertSame([111], array_map('intval', $delA->json('liquidaciones.*.total')));
        $this->assertSame([999], array_map('intval', $delB->json('liquidaciones.*.total')));
        $delB->assertJsonPath('proveedorCodigo', 'PRV-FAON-02');
    }

    public function test_una_cuenta_de_proveedor_sin_ficha_no_recibe_liquidaciones(): void
    {
        Usuario::factory()->create(['username' => 'prov_sin_ficha', 'pin_hash' => '1234', 'roles' => ['proveedor']]);
        $this->liquidacion($this->proveedor, []);

        $this->withToken($this->token('prov_sin_ficha', '1234'))->getJson('/api/movil/proveedor/liquidaciones')
            ->assertStatus(404)->assertJson(['codigo' => 'sin_ficha'])->assertJsonMissing(['liquidaciones']);
    }

    public function test_un_acopiador_que_no_existe_en_el_panel_se_rechaza_aunque_envie_un_admin(): void
    {
        Usuario::factory()->create(['username' => 'admin_movil', 'pin_hash' => '1234', 'roles' => ['admin']]);

        $this->enviar($this->token('admin_movil', '1234'), $this->entrega(['acopiadorUsername' => 'acop_inexistente']))
            ->assertStatus(422)->assertJson(['codigo' => 'dato_no_registrado'])
            ->assertJsonFragment(['message' => 'El acopiador «acop_inexistente» no está registrado en el servidor. Pide al administrador que lo registre igual que en la app.']);

        $this->assertSame(0, Entrega::query()->count());
        $this->assertSame(0, Jornada::query()->count());
    }

    public function test_un_acopiador_no_puede_consultar_liquidaciones_de_proveedor(): void
    {
        $this->withToken($this->token())->getJson('/api/movil/proveedor/liquidaciones')->assertStatus(403);
    }

    public function test_cerrar_sesion_revoca_el_token(): void
    {
        $token = $this->token();

        $this->withToken($token)->deleteJson('/api/movil/sesion')->assertOk();

        $this->assertSame(0, TokenMovil::query()->count());
        $this->enviar($token, $this->entrega())->assertStatus(401);
    }

    public function test_una_correccion_enviada_por_admin_se_audita_a_su_nombre_y_la_entrega_sigue_siendo_del_acopiador(): void
    {
        $admin = Usuario::factory()->create(['username' => 'admin_movil', 'pin_hash' => '1234', 'roles' => ['admin']]);
        $this->enviar($this->token(), $this->entrega())->assertCreated();

        $this->enviar($this->token('admin_movil', '1234'), $this->entrega(['litros' => 36, 'actualizadoEn' => 1_790_000_200_000, 'motivo' => 'Revisión de ADMIN']))
            ->assertOk()->assertJson(['estado' => 'actualizada']);

        $entrega = Entrega::query()->sole();
        $this->assertSame($this->acopiador->id, $entrega->usuario_id, 'ADMIN no pasa a ser el autor de la entrega');
        $this->assertEquals(36.0, (float) $entrega->litros);
        $auditoria = Auditoria::query()->where('entidad', 'entrega')->where('entidad_id', $entrega->id)->orderBy('id')->get();
        $this->assertSame(['crear', 'corregir'], $auditoria->pluck('accion')->all());
        $this->assertSame([$this->acopiador->id, $admin->id], $auditoria->pluck('usuario_id')->all());
    }

    public function test_un_token_vencido_se_rechaza_como_sesion_invalida_sin_crear_nada(): void
    {
        $token = $this->token();
        TokenMovil::query()->update(['expira_en' => now()->subMinute()]);

        $this->enviar($token, $this->entrega())->assertStatus(401)->assertJson(['codigo' => 'token_invalido']);

        $this->assertSame(0, Entrega::query()->count());
    }

    public function test_enviar_muchas_entregas_no_agota_el_limite_de_inicio_de_sesion(): void
    {
        $token = $this->token();

        foreach (range(1, 12) as $i) {
            $this->enviar($token, $this->entrega(['id' => "ent-lote-{$i}"]), "ent-lote-{$i}")->assertCreated();
        }

        // Otro celular (u otra cuenta) en la misma red debe poder enlazarse igual.
        $this->postJson('/api/movil/sesion', ['username' => 'acop_faon', 'pin' => '2468', 'dispositivo' => 'Otro'])->assertOk();
    }
}
