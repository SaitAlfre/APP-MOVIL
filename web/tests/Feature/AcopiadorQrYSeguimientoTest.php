<?php

namespace Tests\Feature;

use App\Domain\Proveedores\ProveedorQr;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcopiadorQrYSeguimientoTest extends TestCase
{
    use RefreshDatabase;

    private function jornadaAbierta(): Jornada
    {
        $usuario = Usuario::factory()->create();
        $jornada = Jornada::factory()->create(['usuario_id' => $usuario->id]);
        $this->actingAs($usuario, 'operador');

        return $jornada;
    }

    public function test_resuelve_un_proveedor_a_partir_de_su_qr(): void
    {
        $this->jornadaAbierta();
        $proveedor = Proveedor::factory()->create();

        $response = $this->postJson('/acopiador/qr/resolver', ['contenido' => ProveedorQr::generar($proveedor->id)]);

        $response->assertOk()->assertJson(['estado' => 'encontrado', 'proveedor' => ['id' => $proveedor->id]]);
    }

    public function test_rechaza_un_qr_que_no_pertenece_a_ecolecta(): void
    {
        $this->jornadaAbierta();

        $response = $this->postJson('/acopiador/qr/resolver', ['contenido' => 'algo-random']);

        $response->assertStatus(404)->assertJson(['estado' => 'qr_invalido']);
    }

    public function test_rechaza_un_qr_de_un_proveedor_inexistente(): void
    {
        $this->jornadaAbierta();

        $response = $this->postJson('/acopiador/qr/resolver', ['contenido' => ProveedorQr::generar(999999)]);

        $response->assertStatus(404)->assertJson(['estado' => 'proveedor_no_encontrado']);
    }

    public function test_activar_seguimiento_marca_la_jornada_como_activa(): void
    {
        $jornada = $this->jornadaAbierta();

        $response = $this->postJson('/acopiador/seguimiento/activar');

        $response->assertOk();
        $this->assertTrue($jornada->fresh()->seguimiento_activo);
    }

    public function test_registrar_posicion_guarda_la_fila_y_activa_el_seguimiento(): void
    {
        $jornada = $this->jornadaAbierta();

        $response = $this->postJson('/acopiador/seguimiento/posicion', ['lat' => -12.05, 'lng' => -77.03, 'precision_m' => 8.5]);

        $response->assertOk();
        $this->assertDatabaseHas('posiciones_seguimiento', ['jornada_id' => $jornada->id]);
        $this->assertTrue($jornada->fresh()->seguimiento_activo);
    }

    public function test_desactivar_seguimiento_no_cierra_la_jornada(): void
    {
        $jornada = $this->jornadaAbierta();
        $jornada->update(['seguimiento_activo' => true]);

        $response = $this->postJson('/acopiador/seguimiento/desactivar');

        $response->assertOk();
        $jornada->refresh();
        $this->assertFalse($jornada->seguimiento_activo);
        $this->assertNull($jornada->cerrada_en);
    }
}
