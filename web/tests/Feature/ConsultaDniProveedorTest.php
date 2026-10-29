<?php

namespace Tests\Feature;

use App\Application\Proveedores\ConsultarDniUseCase;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConsultaDniProveedorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        config(['services.decolecta.token' => 'token-ficticio-solo-test']);
        Http::preventStrayRequests();
    }

    private function respuesta(string $dni = '00000001'): array
    {
        return ['document_number' => $dni, 'first_name' => 'ANA', 'first_last_name' => 'PRUEBA', 'second_last_name' => 'DEMO'];
    }

    public function test_consulta_normaliza_el_nombre_sin_guardar_un_proveedor(): void
    {
        Http::fake(['api.decolecta.com/*' => Http::response($this->respuesta())]);
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'encontrado')->assertJsonPath('nombre', 'ANA PRUEBA DEMO')
            ->assertHeader('Cache-Control', 'no-store, private');
        Http::assertSent(fn ($peticion) => $peticion->hasHeader('Authorization', 'Bearer token-ficticio-solo-test') && $peticion['numero'] === '00000001');
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_sin_token_se_puede_continuar_manualmente_sin_hacer_peticiones(): void
    {
        config(['services.decolecta.token' => '']);
        Http::fake();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'no_configurado')->assertJsonPath('mensaje', ConsultarDniUseCase::MENSAJE_SIN_CONFIGURAR);
        Http::assertNothingSent();
        $this->get(route('admin.proveedores.create'))->assertOk()
            ->assertSee(ConsultarDniUseCase::MENSAJE_SIN_CONFIGURAR)->assertDontSee('data-consultar-dni disabled', false)
            ->assertDontSee('Consulta no disponible. Puedes completar los nombres manualmente.');
    }

    public function test_un_documento_existente_no_consume_la_api_y_ofrece_recuperar_sus_datos(): void
    {
        $proveedor = Proveedor::factory()->create(['dni' => '00000001', 'nombres' => 'Ficha interna', 'telefono' => '900111222']);
        Http::fake();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'registrado')
            ->assertJsonPath('registro.situacion', 'ficha_sin_cuenta')
            ->assertJsonPath('registro.ficha.url', route('admin.proveedores.show', $proveedor->id))
            ->assertJsonPath('registro.datos', ['nombres' => 'Ficha interna', 'telefono' => '900111222', 'zona_id' => $proveedor->zona_id]);
        Http::assertNothingSent();
    }

    public function test_una_cuenta_sin_ficha_se_detecta_antes_de_consultar_reniec(): void
    {
        Usuario::factory()->create(['dni' => '00000001', 'nombres' => 'Cuenta interna', 'roles' => ['proveedor'], 'pin_hash' => '2468']);
        Http::fake();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'registrado')->assertJsonPath('registro.situacion', 'cuenta_sin_ficha')
            ->assertJsonPath('registro.accion', 'asociar_cuenta')->assertJsonPath('registro.datos.nombres', 'Cuenta interna')
            ->assertDontSee('2468')->assertDontSee('pin_hash');
        Http::assertNothingSent();
    }

    public function test_una_coincidencia_bloqueante_no_ofrece_recuperar_datos(): void
    {
        Usuario::factory()->create(['dni' => '00000001', 'roles' => ['acopiador']]);
        Http::fake();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('registro.situacion', 'cuenta_de_otro_rol')
            ->assertJsonPath('registro.bloquea', true)->assertJsonPath('registro.datos', null);
        Http::assertNothingSent();
    }

    public function test_al_editar_el_propio_dni_no_cuenta_como_duplicado(): void
    {
        $proveedor = Proveedor::factory()->create(['dni' => '00000001']);
        Http::fake(['api.decolecta.com/*' => Http::response($this->respuesta())]);
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001', 'excluir' => $proveedor->id])
            ->assertOk()->assertJsonPath('estado', 'encontrado');
        $otro = Proveedor::factory()->create(['dni' => '00000002']);
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001', 'excluir' => $otro->id])
            ->assertOk()->assertJsonPath('estado', 'registrado');
    }

    public function test_credencial_rechazada_y_limite_se_distinguen_sin_exponer_el_token(): void
    {
        Log::spy();
        Http::fake(['api.decolecta.com/*' => Http::sequence()->push(['error' => 'Apikey Required / Limit Exceeded'], 401)
            ->push(['error' => 'Apikey Required / Limit Exceeded'], 403)->push(['error' => 'Apikey Required / Limit Exceeded'], 429)]);
        foreach ([401 => 'no_autorizado', 403 => 'no_autorizado', 429 => 'limite'] as $esperado) {
            $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
                ->assertOk()->assertJsonPath('estado', $esperado)
                ->assertDontSee('Apikey Required')->assertDontSee('token-ficticio-solo-test');
        }
        Log::shouldHaveReceived('warning')->withArgs(fn (string $mensaje, array $contexto) => $contexto === ['estado' => 401])->once();
        Log::shouldNotHaveReceived('warning', [\Mockery::any(), \Mockery::on(fn ($contexto) => str_contains(json_encode($contexto), 'token-ficticio'))]);
    }

    public function test_usuarios_detecta_una_ficha_existente_y_respeta_permisos(): void
    {
        Proveedor::factory()->create(['dni' => '00000001', 'nombres' => 'Ficha interna', 'codigo' => 'PRV-INT-1']);
        Http::fake(['api.decolecta.com/*' => Http::response($this->respuesta())]);
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'ficha_existente')->assertJsonPath('nombre', 'Ficha interna');
        Http::assertNothingSent();
        $perfil = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Solo usuarios', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{"usuarios":["ver","crear","editar"]}', 'campos' => '{}']);
        $this->actingAs(Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $perfil]), 'operador');
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'encontrado')->assertDontSee('Ficha interna');
    }

    public function test_documentos_invalidos_no_llegan_al_servicio_externo(): void
    {
        Http::fake();
        foreach (['123', '123456789', 'abcdefgh'] as $dni) {
            $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => $dni])
                ->assertUnprocessable()->assertJsonValidationErrors('dni');
        }
        Http::assertNothingSent();
    }

    public function test_el_proveedor_no_puede_consultar_identidades(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['proveedor']]), 'operador');
        Http::fake();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_los_errores_remotos_no_exponen_respuestas_ni_secretos(): void
    {
        Http::fake(['api.decolecta.com/*' => Http::sequence()->push(['error' => 'detalle-privado'], 401)->push(['error' => 'detalle-privado'], 403)
            ->push(['error' => 'detalle-privado'], 429)->push(['error' => 'detalle-privado'], 500)]);
        foreach ([401, 403, 429, 500] as $estado) {
            $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
                ->assertOk()->assertJsonPath('estado', ['401' => 'no_autorizado', '403' => 'no_autorizado', '429' => 'limite', '500' => 'no_disponible'][(string) $estado])
                ->assertDontSee('detalle-privado')->assertDontSee('token-ficticio-solo-test');
        }
    }

    public function test_un_dni_sin_resultado_se_puede_completar_manualmente(): void
    {
        Http::fake(['api.decolecta.com/*' => Http::response([], 404)]);
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'sin_resultado');
    }

    public function test_rechaza_identidades_distintas_y_respuestas_incompletas(): void
    {
        foreach ([$this->respuesta('00000002'), ['document_number' => '00000001'], ['document_number' => '00000001', 'first_name' => [], 'first_last_name' => 'PRUEBA']] as $datos) {
            Http::fake(['api.decolecta.com/*' => Http::response($datos)]);
            $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
                ->assertOk()->assertJsonPath('estado', 'no_disponible')->assertJsonMissingPath('nombre');
        }
    }

    public function test_un_timeout_no_interrumpe_el_formulario(): void
    {
        Http::fake(fn () => throw new ConnectionException('detalle-privado'));
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'no_disponible')->assertDontSee('detalle-privado');
    }

    public function test_el_token_no_se_incluye_en_el_formulario(): void
    {
        $this->get(route('admin.proveedores.create'))->assertOk()
            ->assertSee('data-dni-disponible="true"', false)->assertDontSee('token-ficticio-solo-test');
    }
}
