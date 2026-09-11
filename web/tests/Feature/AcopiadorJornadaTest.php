<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcopiadorJornadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_abrir_jornada_crea_una_nueva_y_redirige_al_inicio(): void
    {
        $usuario = Usuario::factory()->create();
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();

        $response = $this->actingAs($usuario, 'operador')
            ->post('/acopiador/onboarding', ['zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertDatabaseHas('jornadas', [
            'usuario_id' => $usuario->id,
            'zona_id' => $zona->id,
            'vehiculo_id' => $vehiculo->id,
        ]);
    }

    public function test_bloquea_abrir_jornada_en_una_zona_ocupada_por_otro_acopiador(): void
    {
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        $ocupante = Usuario::factory()->create();
        Jornada::factory()->create(['usuario_id' => $ocupante->id, 'zona_id' => $zona->id]);

        $otro = Usuario::factory()->create();

        $response = $this->actingAs($otro, 'operador')
            ->post('/acopiador/onboarding', ['zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);

        $response->assertSessionHasErrors('zona_id');
        $this->assertDatabaseMissing('jornadas', ['usuario_id' => $otro->id]);
    }

    public function test_retoma_la_jornada_abierta_de_hoy_en_vez_de_crear_otra(): void
    {
        $usuario = Usuario::factory()->create();
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        $jornada = Jornada::factory()->create(['usuario_id' => $usuario->id, 'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);

        $this->actingAs($usuario, 'operador')
            ->post('/acopiador/onboarding', ['zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);

        $this->assertSame(1, Jornada::query()->where('usuario_id', $usuario->id)->count());
        $this->assertSame($jornada->id, Jornada::query()->where('usuario_id', $usuario->id)->first()->id);
    }

    public function test_cerrar_jornada_la_marca_cerrada_y_detiene_el_seguimiento(): void
    {
        $usuario = Usuario::factory()->create();
        $jornada = Jornada::factory()->create(['usuario_id' => $usuario->id, 'seguimiento_activo' => true]);

        $response = $this->actingAs($usuario, 'operador')->post('/acopiador/jornada/cerrar');

        $response->assertRedirect(route('acopiador.onboarding'));
        $jornada->refresh();
        $this->assertNotNull($jornada->cerrada_en);
        $this->assertFalse($jornada->seguimiento_activo);
    }

    public function test_sin_jornada_abierta_el_inicio_redirige_a_la_seleccion_de_zona(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this->actingAs($usuario, 'operador')->get('/acopiador/inicio');

        $response->assertRedirect(route('acopiador.onboarding'));
    }
}
