<?php

namespace Tests\Feature;

use App\Application\Auditoria\DatosSegurosAuditoria;
use App\Application\Usuarios\RestablecerCredencialesUsuarioUseCase;
use App\Infrastructure\Persistence\Eloquent\TokenMovil;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefreshTokenMovilTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function sesion(?Usuario $usuario = null): array
    {
        $usuario ??= Usuario::factory()->create(['pin_hash' => '2468', 'roles' => ['acopiador']]);

        return $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468'])
            ->assertOk()->json();
    }

    public function test_login_emite_par_de_tokens_con_hashes_y_respuesta_no_cacheable(): void
    {
        $sesion = $this->sesion();
        $this->assertSame($sesion['token'], $sesion['access_token']);
        $this->assertSame('Bearer', $sesion['token_type']);
        $this->assertEqualsWithDelta(900, $sesion['expires_in'], 1);
        $this->assertEqualsWithDelta(now()->addDays(30)->getTimestampMs(), $sesion['refreshExpiraEn'], 2000);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{64}$/', $sesion['refresh_token']);
        $this->assertNotSame($sesion['token'], $sesion['refresh_token']);
        $this->assertDatabaseHas('refresh_tokens_movil', ['token_hash' => hash('sha256', $sesion['refresh_token'])]);
        $this->assertDatabaseMissing('refresh_tokens_movil', ['token_hash' => $sesion['refresh_token']]);
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Pragma', 'no-cache');
    }

    public function test_refresh_rota_ambos_tokens_y_no_extiende_la_sesion(): void
    {
        $sesion = $this->sesion();
        $nuevo = $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertOk()->json();
        $this->assertNotSame($sesion['token'], $nuevo['token']);
        $this->assertNotSame($sesion['refresh_token'], $nuevo['refresh_token']);
        $this->assertSame($sesion['refreshExpiraEn'], $nuevo['refreshExpiraEn']);
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->withToken($nuevo['token'])->getJson('/api/movil/datos')->assertOk();
        $this->assertDatabaseCount('tokens_movil', 1);
        $this->assertDatabaseCount('refresh_tokens_movil', 2);
    }

    public function test_renueva_con_access_token_vencido_sin_enviar_pin(): void
    {
        $sesion = $this->sesion();
        $this->travel(16)->minutes();
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $nuevo = $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertOk()->json();
        $this->withToken($nuevo['token'])->getJson('/api/movil/datos')->assertOk();
    }

    public function test_reutilizar_refresh_usado_revoca_su_sesion_y_preserva_otro_dispositivo(): void
    {
        $usuario = Usuario::factory()->create(['pin_hash' => '2468']);
        $sesion = $this->sesion($usuario);
        $otro = $this->sesion($usuario);
        $nuevo = $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertOk()->json();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])
            ->assertUnauthorized()->assertJsonPath('codigo', 'refresh_invalido');
        $this->withToken($nuevo['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $nuevo['refresh_token']])->assertUnauthorized();
        $this->withToken($otro['token'])->getJson('/api/movil/datos')->assertOk();
        $this->assertDatabaseCount('tokens_movil', 1);
    }

    public function test_refresh_vencido_no_renueva(): void
    {
        $sesion = $this->sesion();
        $this->travel(31)->days();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
        $this->assertDatabaseCount('tokens_movil', 0);
        $this->assertDatabaseCount('refresh_tokens_movil', 0);
    }

    public function test_rotacion_no_emite_access_mas_alla_del_vencimiento_refresh(): void
    {
        $this->freezeTime();
        $sesion = $this->sesion();
        $this->travel(30 * 24 * 60 - 5)->minutes();
        $nuevo = $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertOk()->json();
        $this->assertSame($sesion['refreshExpiraEn'], $nuevo['expiraEn']);
        $this->assertSame(300, $nuevo['expires_in']);
    }

    public function test_refresh_inventado_o_access_como_refresh_no_revoca_sesion_legitima(): void
    {
        $sesion = $this->sesion();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => 'inventado'])->assertUnauthorized();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['token']])->assertUnauthorized();
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertOk();
    }

    public function test_refresh_no_se_puede_usar_como_bearer_para_datos(): void
    {
        $sesion = $this->sesion();
        $this->withToken($sesion['refresh_token'])->getJson('/api/movil/datos')->assertUnauthorized();
    }

    public function test_logout_con_access_revoca_tambien_refresh_e_historial(): void
    {
        $sesion = $this->sesion();
        $nuevo = $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertOk()->json();
        $this->withToken($nuevo['token'])->deleteJson('/api/movil/sesion')->assertOk();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $nuevo['refresh_token']])->assertUnauthorized();
        $this->assertDatabaseCount('refresh_tokens_movil', 0);
    }

    public function test_logout_con_refresh_funciona_aunque_access_haya_vencido(): void
    {
        $sesion = $this->sesion();
        $this->travel(16)->minutes();
        $this->deleteJson('/api/movil/sesion', ['refresh_token' => $sesion['refresh_token']])->assertOk();
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
    }

    public function test_cambiar_pin_revoca_access_y_refresh(): void
    {
        $usuario = Usuario::factory()->create(['pin_hash' => '2468']);
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $sesion = $this->sesion($usuario);
        app(RestablecerCredencialesUsuarioUseCase::class)->ejecutar($usuario->id, '5678', $admin->id);
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
        $this->assertDatabaseCount('refresh_tokens_movil', 0);
    }

    public function test_cuenta_inactiva_o_bloqueada_no_puede_renovar(): void
    {
        foreach ([['activo' => false], ['bloqueado_manualmente' => true], ['bloqueado_hasta' => now()->addMinutes(15)]] as $estado) {
            $usuario = Usuario::factory()->create(['pin_hash' => '2468']);
            $sesion = $this->sesion($usuario);
            $usuario->update($estado);
            $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => $sesion['refresh_token']])->assertUnauthorized();
        }
    }

    public function test_valida_refresh_en_body_sin_aceptar_bearer_como_reemplazo(): void
    {
        $sesion = $this->sesion();
        $this->postJson('/api/movil/sesion/refresh', [])->assertUnprocessable()->assertJsonValidationErrors('refresh_token');
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => ['invalido']])->assertUnprocessable();
        $this->withToken($sesion['refresh_token'])->postJson('/api/movil/sesion/refresh', [])->assertUnprocessable();
    }

    public function test_refresh_tiene_limite_independiente_del_login(): void
    {
        foreach (range(1, 60) as $intento) {
            $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => 'inventado'])->assertUnauthorized();
        }
        $this->postJson('/api/movil/sesion/refresh', ['refresh_token' => 'inventado'])->assertStatus(429)->assertHeader('Retry-After');
        $this->sesion();
    }

    public function test_auditoria_oculta_refresh_y_access_en_texto_y_json(): void
    {
        $this->assertSame('refresh_token=[OCULTO];access_token=[OCULTO]', DatosSegurosAuditoria::limpiar('refresh_token=secreto-uno;access_token=secreto-dos'));
        $json = DatosSegurosAuditoria::limpiar(json_encode(['detalle' => ['refresh_token' => 'secreto-uno', 'access_token' => 'secreto-dos']]));
        $this->assertStringNotContainsString('secreto-', $json);
    }

    public function test_tokens_emitidos_antes_de_migrar_siguen_validos_hasta_su_vencimiento(): void
    {
        $usuario = Usuario::factory()->create();
        TokenMovil::query()->create([
            'usuario_id' => $usuario->id, 'token_hash' => TokenMovil::hash('token-anterior'),
            'expira_en' => now()->addDays(10),
        ]);
        $this->withToken('token-anterior')->getJson('/api/movil/datos')->assertOk();
        $this->assertSame(0, DB::table('refresh_tokens_movil')->count());
    }
}
