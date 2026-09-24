<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel → celular: lo que se cambia en la web llega en `GET /api/movil/datos`, y cada cuenta recibe
 * solo lo que su rol puede ver.
 */
class DatosMovilTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $username, string $pin = '1357'): string
    {
        return $this->postJson('/api/movil/sesion', ['username' => $username, 'pin' => $pin, 'dispositivo' => 'Prueba'])
            ->assertOk()->json('token');
    }

    public function test_el_inicio_de_sesion_devuelve_la_cuenta_para_crearla_en_el_celular(): void
    {
        $usuario = Usuario::factory()->create(['username' => 'nuevo_web', 'dni' => '99001234', 'pin_hash' => '1357', 'roles' => ['acopiador', 'produccion']]);

        $this->postJson('/api/movil/sesion', ['username' => 'nuevo_web', 'pin' => '1357'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $usuario->id)
            ->assertJsonPath('usuario.dni', '99001234')
            ->assertJsonPath('usuario.roles', ['acopiador']);
    }

    public function test_el_administrador_recibe_los_cambios_del_panel(): void
    {
        $admin = Usuario::factory()->create(['username' => 'admin_web', 'pin_hash' => '1357', 'roles' => ['admin']]);
        $zona = Zona::factory()->create(['nombre' => 'FAON-MARKAPAJO']);
        $proveedor = Proveedor::factory()->create(['codigo' => 'PRV-01', 'zona_id' => $zona->id]);
        $entrega = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'zona_id' => $zona->id, 'litros' => 40]);
        Comunicado::query()->create(['codigo' => 'C-1', 'titulo' => 'Aviso', 'contenido' => 'Hola', 'audiencia' => 'todos',
            'estado' => 'publicado', 'publicado_en' => now(), 'autor_id' => $admin->id]);
        $token = $this->token('admin_web');

        $zona->update(['nombre' => 'FAON NUEVO']);
        $entrega->update(['litros' => 35.5, 'anulada' => true]);

        $datos = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->assertJsonPath('completo', true);

        $this->assertContains('FAON NUEVO', collect($datos->json('zonas'))->pluck('nombre'));
        $recibida = collect($datos->json('entregas'))->firstWhere('id', $entrega->id);
        $this->assertEquals(35.5, $recibida['litros']);
        $this->assertTrue($recibida['anulada']);
        $this->assertSame('ACTIVO', collect($datos->json('proveedores'))->firstWhere('codigo', 'PRV-01')['estado']);
        $this->assertSame('Aviso', $datos->json('comunicados.0.titulo'));
    }

    public function test_el_proveedor_solo_recibe_su_ficha_y_sus_entregas(): void
    {
        $cuenta = Usuario::factory()->create(['username' => 'prov_web', 'pin_hash' => '1357', 'roles' => ['proveedor']]);
        $propio = Proveedor::factory()->create(['usuario_id' => $cuenta->id]);
        $ajeno = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $propio->id]);
        Entrega::factory()->create(['proveedor_id' => $ajeno->id]);

        $datos = $this->withToken($this->token('prov_web'))->getJson('/api/movil/datos')->assertOk()->assertJsonPath('completo', false);

        $this->assertSame([$propio->id], collect($datos->json('proveedores'))->pluck('id')->all());
        $this->assertSame([$propio->id], collect($datos->json('entregas'))->pluck('proveedorId')->unique()->values()->all());
        $otros = collect($datos->json('usuarios'))->where('id', '!=', $cuenta->id);
        $this->assertTrue($otros->every(fn ($u) => $u['dni'] === null), 'no expone el DNI de otras personas');
    }

    public function test_sin_token_no_hay_datos(): void
    {
        $this->getJson('/api/movil/datos')->assertUnauthorized();
    }

    public function test_el_analisis_de_calidad_del_panel_llega_completo_al_celular(): void
    {
        Usuario::factory()->create(['username' => 'cal_web', 'pin_hash' => '1357', 'roles' => ['calidad']]);
        $proveedor = Proveedor::factory()->create();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $this->post('/admin/calidad', ['proveedor_id' => $proveedor->id, 'fecha' => now('America/Lima')->toDateString(), 'hora' => '07:00',
            'unidad_congelacion' => '°C', 'valores' => ['grasa' => '2.1', 'congelacion' => '-0.53']])->assertSessionHasNoErrors();
        auth('operador')->forgetUser();

        $analisis = $this->withToken($this->token('cal_web'))->getJson('/api/movil/datos')->assertOk()->json('analisis.0');

        $this->assertSame(2.1, $analisis['grasa']);
        $this->assertSame(-0.53, $analisis['puntoCongelacion']);
        $this->assertSame('OBSERVADO', $analisis['estado']);
        $this->assertSame(['grasa'], $analisis['visita']['parametrosAlertados']);
        $this->assertSame('3.0 a 6.0 %', $analisis['visita']['referencias']['grasa']);
    }
}
