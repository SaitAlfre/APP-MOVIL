<?php

namespace Tests\Feature;

use App\Application\Usuarios\CrearUsuarioUseCase;
use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompatibilidadFormulariosCoder1Test extends TestCase
{
    use RefreshDatabase;

    public function test_dominio_rechaza_capacidad_no_finita_y_menor_al_minimo_del_formulario(): void
    {
        foreach ([NAN, INF, -INF, 0.009, 0.0] as $valor) {
            try {
                Proveedor::crear('QA-01', 'Ficha ficticia', '12345678', null, null, 1, 2, $valor);
                $this->fail('Una capacidad no transportable debía rechazarse.');
            } catch (ProveedorInvalidoException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
        $this->assertSame(0.01, Proveedor::crear('QA-01', 'Ficha ficticia', '12345678', null, null, 1, 2, 0.01)->capacidadTachoL);
    }

    public function test_formulario_rechaza_textos_que_exceden_el_contrato_sin_crear_fichas(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $zona = Zona::factory()->create();
        $base = ['codigo' => 'QA-26', 'nombres' => 'Ficha ficticia', 'dni' => '12345678', 'zona_id' => $zona->id, 'tachos' => 2, 'capacidad_tacho_l' => 40];
        foreach (['codigo' => 50, 'nombres' => 150, 'dni' => 20, 'telefono' => 30, 'direccion' => 255, 'dueno' => 150] as $campo => $maximo) {
            $this->post('/admin/proveedores', [...$base, $campo => str_repeat('X', $maximo + 1)])->assertSessionHasErrors($campo);
        }
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_altas_y_restablecimientos_exigen_cuatro_digitos_sin_cambiar_hash_por_error(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '2468']);
        $cuenta = Usuario::factory()->create(['roles' => ['acopiador'], 'pin_hash' => '12345678']);
        $anterior = $cuenta->pin_hash;
        $this->actingAs($admin, 'operador');
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        foreach (['123', '12345', '12345678', '123456789', 'abcd'] as $pin) {
            $datos = ['username' => 'pin_invalido', 'nombres' => 'Cuenta ficticia', 'dni' => '12345678', 'roles' => ['acopiador'], 'pin' => $pin];
            $this->post('/admin/usuarios', $datos)->assertSessionHasErrors('pin');
            $this->withToken($token)->putJson('/api/movil/cambios/usuario', [...$datos, 'activo' => true])->assertUnprocessable();
            $this->post(route('admin.usuarios.restablecer', $cuenta->id), ['pin' => $pin])->assertSessionHasErrors('pin');
            $this->assertSame($anterior, $cuenta->fresh()->pin_hash);
        }
        $this->assertDatabaseMissing('usuarios', ['username' => 'pin_invalido']);
    }

    public function test_acceso_heredado_funciona_hasta_restablecimiento_manual_a_cuatro(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        // Fixture de cuenta anterior; no se crea un PIN largo mediante el formulario vigente.
        $cuenta = Usuario::factory()->create(['roles' => ['acopiador'], 'pin_hash' => '12345678']);
        $hash = $cuenta->pin_hash;
        $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '12345678'])->assertOk();
        $this->assertSame($hash, $cuenta->fresh()->pin_hash);
        $this->actingAs($admin, 'operador')->post(route('admin.usuarios.restablecer', $cuenta->id), ['pin' => '1357', 'pin_confirmation' => '1357'])->assertSessionHasNoErrors();
        $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '12345678'])->assertUnauthorized();
        $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk();
    }

    public function test_caso_de_uso_no_admite_pin_con_salto_final_ni_digitos_no_ascii(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $crear = app(CrearUsuarioUseCase::class);
        foreach (["1357\n", "1357\r\n", ' 1357', '١٣٥٧', '１２３４'] as $pin) {
            try {
                $crear->ejecutar('pin_limite', 'Cuenta ficticia', '12345678', $pin,
                    [Rol::Acopiador], true, $admin->id);
                $this->fail('El dominio debía exigir exactamente cuatro dígitos ASCII.');
            } catch (UsuarioInvalidoException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
        $this->assertDatabaseMissing('usuarios', ['username' => 'pin_limite']);
    }

    public function test_api_restringe_escritura_de_catalogos_a_admin_y_proveedor_a_su_ficha(): void
    {
        $zona = Zona::factory()->create();
        foreach (['admin', 'acopiador', 'calidad', 'proveedor'] as $rol) {
            $cuenta = Usuario::factory()->create(['roles' => [$rol], 'pin_hash' => '1357']);
            $propia = \App\Infrastructure\Persistence\Eloquent\Proveedor::factory()->create(['usuario_id' => $cuenta->id, 'zona_id' => $zona->id]);
            $token = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk()->json('token');
            $datos = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json();
            if ($rol === 'proveedor') {
                $this->assertSame([$propia->id], array_column($datos['proveedores'], 'id'));
            }
            $this->withToken($token)->putJson('/api/movil/cambios/zona', ['nombre' => 'QA PERMISOS '.$rol, 'activo' => true])->assertStatus($rol === 'admin' ? 200 : 403);
            $cuenta->update(['bloqueado_manualmente' => true]);
            $this->withToken($token)->getJson('/api/movil/datos')->assertUnauthorized();
        }
        $this->getJson('/api/movil/datos', ['Authorization' => 'Bearer token-ficticio-invalido'])->assertUnauthorized();
    }
}
