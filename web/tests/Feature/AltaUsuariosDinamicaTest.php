<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AltaUsuariosDinamicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        Http::preventStrayRequests();
    }

    private function datos(): array
    {
        return ['username' => 'nuevo.usuario', 'dni' => '00000001', 'nombres' => 'Ana Prueba', 'roles' => ['acopiador'], 'pin' => '4321', 'pin_confirmation' => '4321'];
    }

    public function test_dni_completa_nombre_con_api_y_no_crea_cuentas(): void
    {
        config(['services.decolecta.token' => 'token-ficticio']);
        Http::fake(['api.decolecta.com/*' => Http::response(['document_number' => '00000001', 'first_name' => 'ANA', 'first_last_name' => 'PRUEBA', 'second_last_name' => 'DEMO'])]);
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => '00000001'])->assertOk()->assertJsonPath('nombre', 'ANA PRUEBA DEMO')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('usuarios', 1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer token-ficticio'));
    }

    public function test_dni_duplicado_no_consume_api_y_no_crea_otra_cuenta(): void
    {
        $existente = Usuario::factory()->create(['dni' => '00000001']);
        Http::fake();
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => '00000001'])->assertOk()->assertJsonPath('estado', 'registrado')->assertJsonPath('url', route('admin.usuarios.edit', $existente->id));
        $this->post(route('admin.usuarios.store'), $this->datos())->assertSessionHasErrors('dni');
        $this->assertDatabaseMissing('usuarios', ['username' => 'nuevo.usuario']);
        Http::assertNothingSent();
    }

    public function test_sin_api_se_permite_registro_manual(): void
    {
        config(['services.decolecta.token' => '']);
        Http::fake();
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => '00000001'])->assertOk()->assertJsonPath('estado', 'no_disponible');
        $this->post(route('admin.usuarios.store'), $this->datos())->assertSessionHasNoErrors()->assertRedirect(route('admin.usuarios.index'));
        Http::assertNothingSent();
    }

    public function test_documento_invalido_y_operador_sin_permiso_no_consultan_api(): void
    {
        Http::fake();
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => 'abc'])->assertUnprocessable();
        $this->actingAs(Usuario::factory()->create(['roles' => ['calidad']]), 'operador');
        $this->postJson(route('admin.usuarios.consultar-dni'), ['dni' => '00000001'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_pin_no_coincide_y_no_se_conserva_en_datos_anteriores(): void
    {
        $datos = $this->datos();
        $datos['pin_confirmation'] = '1234';
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasErrors('pin_confirmation')->assertSessionMissing('_old_input.pin')->assertSessionMissing('_old_input.pin_confirmation');
        $this->assertDatabaseMissing('usuarios', ['username' => 'nuevo.usuario']);
    }

    public function test_normaliza_usuario_y_valida_username_duplicado(): void
    {
        $datos = $this->datos();
        $datos['username'] = '  Nuevo.Usuario  ';
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['username' => 'nuevo.usuario']);
        $datos['dni'] = '00000002';
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasErrors('username');
    }

    public function test_editar_no_permite_apropiarse_de_otro_dni(): void
    {
        $a = Usuario::factory()->create(['dni' => '00000001', 'roles' => ['acopiador']]);
        $b = Usuario::factory()->create(['dni' => '00000002']);
        $this->put(route('admin.usuarios.update', $a->id), ['nombres' => $a->nombres, 'dni' => $b->dni, 'roles' => ['acopiador']])->assertSessionHasErrors('dni');
        $this->assertSame('00000001', $a->fresh()->dni);
    }

    public function test_campos_personalizados_permiten_limpiar_un_valor_opcional(): void
    {
        $id = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Auxiliar', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{}', 'campos' => json_encode(['telefono' => ['etiqueta' => 'Teléfono', 'tipo' => 'texto', 'obligatorio' => false]])]);
        $usuario = Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id, 'datos_formulario' => ['telefono' => '999999999']]);
        $this->put(route('admin.usuarios.update', $usuario->id), ['nombres' => $usuario->nombres, 'dni' => $usuario->dni, 'perfil_rol_id' => $id, 'datos_formulario' => ['telefono' => '']])->assertSessionHasNoErrors();
        $this->assertNull($usuario->fresh()->datos_formulario['telefono']);
    }

    public function test_formulario_no_vuelve_a_pedir_el_rol(): void
    {
        $this->get(route('admin.usuarios.create', ['base' => 'acopiador']))->assertOk()->assertSee('Consultar DNI')->assertSee('Repite el PIN')->assertSee('Cambiar rol')->assertDontSee('¿Qué trabajo realizará esta persona?');
    }

    public function test_datos_con_tipo_incorrecto_se_rechazan_sin_error_del_servidor(): void
    {
        $datos = $this->datos();
        $datos['nombres'] = ['incorrecto'];
        $datos['username'] = ['incorrecto'];
        $datos['dni'] = ['incorrecto'];
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasErrors(['nombres', 'username', 'dni']);
    }

    public function test_rol_proveedor_crea_una_cuenta_y_no_una_ficha_productiva(): void
    {
        $this->get(route('admin.usuarios.create', ['base' => 'proveedor']))->assertOk()->assertSee('Cambiar rol')->assertSee('PIN inicial')->assertViewIs('admin.usuarios.form');
        $datos = $this->datos();
        $datos['roles'] = ['proveedor'];
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['username' => $datos['username']]);
        $this->assertDatabaseCount('proveedores', 0);
        $this->get(route('admin.proveedores.create'))->assertOk()->assertSee('Datos del proveedor')->assertDontSee('PIN inicial');
    }

    public function test_formulario_personalizado_reutiliza_dni_y_nombre_sin_duplicarlos(): void
    {
        $id = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Secretaría', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{}', 'campos' => json_encode([
            'documento_personal' => ['etiqueta' => 'Dni', 'tipo' => 'numero', 'obligatorio' => true],
            'nombre_personal' => ['etiqueta' => 'nombre', 'tipo' => 'texto', 'obligatorio' => true],
        ])]);
        $response = $this->get(route('admin.usuarios.create', ['perfil_rol_id' => $id]))->assertOk()->assertDontSee('Identidad y datos de trabajo');
        $this->assertSame(1, substr_count($response->getContent(), 'name="dni"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="nombres"'));
        $datos = $this->datos();
        $datos['perfil_rol_id'] = $id;
        $this->post(route('admin.usuarios.store'), $datos)->assertSessionHasNoErrors();
        $usuario = Usuario::where('username', $datos['username'])->firstOrFail();
        $this->assertSame('00000001', $usuario->datos_formulario['documento_personal']);
        $this->assertSame('Ana Prueba', $usuario->datos_formulario['nombre_personal']);
        $this->get(route('admin.usuarios.edit', $usuario->id))->assertOk()->assertSee('Ana Prueba');
    }

    public function test_campos_personalizados_sin_identidad_conservan_sus_campos_y_agregan_identidad_basica(): void
    {
        $id = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Auxiliar', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{}', 'campos' => json_encode([
            'telefono' => ['etiqueta' => 'Teléfono', 'tipo' => 'texto', 'obligatorio' => false],
        ])]);
        $response = $this->get(route('admin.usuarios.create', ['perfil_rol_id' => $id]))->assertOk()->assertSee('Teléfono');
        $this->assertSame(1, substr_count($response->getContent(), 'name="dni"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="nombres"'));
    }
}
