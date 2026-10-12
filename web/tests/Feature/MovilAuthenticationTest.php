<?php

namespace Tests\Feature;

use App\Application\Usuarios\BloquearUsuarioUseCase;
use App\Application\Usuarios\CambiarEstadoUsuarioUseCase;
use App\Application\Usuarios\DesbloquearUsuarioUseCase;
use App\Application\Usuarios\RestablecerCredencialesUsuarioUseCase;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\TokenMovil;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MovilAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function cuenta(): Usuario
    {
        return Usuario::factory()->create(['pin_hash' => '2468', 'roles' => ['acopiador']]);
    }

    private function token(Usuario $usuario): string
    {
        return $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468', 'dispositivo' => 'Postman'])
            ->assertOk()->json('token');
    }

    public function test_login_emite_token_aleatorio_guarda_hash_y_no_expone_credenciales(): void
    {
        $usuario = $this->cuenta();
        $respuesta = $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468']);
        $respuesta->assertOk()->assertJsonPath('usuario.username', $usuario->username)
            ->assertJsonMissingPath('usuario.pin')->assertJsonMissingPath('usuario.pin_hash');
        $token = $respuesta->json('token');
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{64}$/', $token);
        $this->assertEqualsWithDelta(now()->addMinutes(15)->getTimestampMs(), $respuesta->json('expiraEn'), 2000);
        $this->assertTrue(Hash::check('2468', $usuario->fresh()->pin_hash));
        $this->assertDatabaseHas('tokens_movil', ['token_hash' => hash('sha256', $token), 'usuario_id' => $usuario->id]);
        $this->assertStringNotContainsString($token, Auditoria::query()->get()->toJson());
        $this->assertStringNotContainsString($usuario->pin_hash, Auditoria::query()->get()->toJson());
    }

    public function test_token_valido_permite_datos_y_token_alterado_no(): void
    {
        $token = $this->token($this->cuenta());
        $this->withToken($token)->getJson('/api/movil/datos')->assertOk();
        $alterado = ($token[0] === 'A' ? 'B' : 'A').substr($token, 1);
        $this->withToken($alterado)->getJson('/api/movil/datos')->assertUnauthorized()->assertJsonPath('codigo', 'token_invalido');
    }

    public function test_sin_token_o_token_en_query_no_autentica(): void
    {
        $token = $this->token($this->cuenta());
        $this->getJson('/api/movil/datos')->assertUnauthorized();
        $this->getJson('/api/movil/datos?token='.$token)->assertUnauthorized();
    }

    public function test_rechaza_token_en_el_instante_de_expiracion(): void
    {
        $this->freezeTime();
        $token = $this->token($this->cuenta());
        TokenMovil::query()->update(['expira_en' => now()]);
        $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
    }

    public function test_logout_revoca_solo_el_token_presentado(): void
    {
        $usuario = $this->cuenta();
        $primero = $this->token($usuario);
        $segundo = $this->token($usuario);
        $this->assertNotSame($primero, $segundo);
        $this->withToken($primero)->deleteJson('/api/movil/sesion')->assertOk();
        $this->withToken($primero)->getJson('/api/movil/datos')->assertUnauthorized();
        $this->withToken($segundo)->getJson('/api/movil/datos')->assertOk();
    }

    public function test_pin_incorrecto_y_cuenta_inexistente_no_emiten_token(): void
    {
        $usuario = $this->cuenta();
        $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '0000'])
            ->assertUnauthorized()->assertJsonPath('codigo', 'credenciales');
        $this->postJson('/api/movil/sesion', ['username' => "' OR 1=1 --", 'pin' => '2468'])
            ->assertUnauthorized()->assertJsonPath('codigo', 'credenciales');
        $this->assertDatabaseCount('tokens_movil', 0);
    }

    public function test_valida_tipos_y_campos_sin_error_interno(): void
    {
        $this->postJson('/api/movil/sesion', [])->assertUnprocessable()->assertJsonValidationErrors(['username', 'pin']);
        $this->postJson('/api/movil/sesion', ['username' => ['admin'], 'pin' => '2468'])->assertUnprocessable();
        $this->postJson('/api/movil/sesion', ['username' => 'admin', 'pin' => 2468])->assertUnprocessable();
        $this->assertDatabaseCount('tokens_movil', 0);
    }

    public function test_cinco_pines_incorrectos_bloquean_login_y_token_existente_temporalmente(): void
    {
        $usuario = $this->cuenta();
        $token = $this->token($usuario);
        foreach (range(1, 5) as $intento) {
            $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '0000'])->assertUnauthorized();
        }
        $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468'])
            ->assertForbidden()->assertJsonPath('codigo', 'cuenta_bloqueada');
        $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
        $this->assertNotNull($usuario->fresh()->bloqueado_hasta);
    }

    public function test_limita_intentos_incluso_para_usuarios_inexistentes(): void
    {
        foreach (range(1, 10) as $intento) {
            $this->postJson('/api/movil/sesion', ['username' => 'postman-inexistente', 'pin' => '0000'])->assertUnauthorized();
        }
        $this->postJson('/api/movil/sesion', ['username' => 'postman-inexistente', 'pin' => '0000'])
            ->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_token_no_otorga_permisos_de_otro_rol(): void
    {
        $this->withToken($this->token($this->cuenta()))->getJson('/api/movil/proveedor/liquidaciones')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }

    public function test_restablecer_pin_revoca_todos_los_tokens_de_esa_cuenta(): void
    {
        $usuario = $this->cuenta();
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $primero = $this->token($usuario);
        $segundo = $this->token($usuario);
        $otroToken = $this->token($this->cuenta());

        app(RestablecerCredencialesUsuarioUseCase::class)->ejecutar($usuario->id, '5678', $admin->id);

        $this->withToken($primero)->getJson('/api/movil/datos')->assertUnauthorized();
        $this->withToken($segundo)->getJson('/api/movil/datos')->assertUnauthorized();
        $this->withToken($otroToken)->getJson('/api/movil/datos')->assertOk();
        $this->assertDatabaseMissing('tokens_movil', ['usuario_id' => $usuario->id]);
        $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '5678'])->assertOk();
    }

    public function test_reactivar_cuenta_no_resucita_tokens_anteriores(): void
    {
        $usuario = $this->cuenta();
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $token = $this->token($usuario);
        app(CambiarEstadoUsuarioUseCase::class)->ejecutar($usuario->id, false, $admin->id);
        $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
        app(CambiarEstadoUsuarioUseCase::class)->ejecutar($usuario->id, true, $admin->id);
        $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
    }

    public function test_desbloquear_cuenta_no_resucita_tokens_anteriores(): void
    {
        $usuario = $this->cuenta();
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $token = $this->token($usuario);
        app(BloquearUsuarioUseCase::class)->ejecutar($usuario->id, 'Prueba de seguridad', $admin->id);
        $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
        app(DesbloquearUsuarioUseCase::class)->ejecutar($usuario->id, $admin->id);
        $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
    }
}
