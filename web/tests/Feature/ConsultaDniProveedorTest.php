<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
            ->assertOk()->assertJsonPath('estado', 'no_disponible');
        Http::assertNothingSent();
    }

    public function test_un_documento_existente_no_consume_la_api(): void
    {
        $proveedor = Proveedor::factory()->create(['dni' => '00000001']);
        Http::fake();
        $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
            ->assertOk()->assertJsonPath('estado', 'registrado')
            ->assertJsonPath('url', route('admin.proveedores.show', $proveedor->id));
        Http::assertNothingSent();
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
        foreach ([401, 403, 429, 500] as $estado) {
            Http::fake(['api.decolecta.com/*' => Http::response(['error' => 'detalle-privado'], $estado)]);
            $this->postJson(route('admin.proveedores.consultar-dni'), ['dni' => '00000001'])
                ->assertOk()->assertJsonPath('estado', 'no_disponible')
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
