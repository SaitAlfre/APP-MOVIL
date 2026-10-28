<?php

namespace Tests\Feature;

use App\Application\Movil\RevisionCatalogo;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CamposProveedorCoder1Test extends TestCase
{
    use RefreshDatabase;

    private function datos(Proveedor $p): array
    {
        return ['codigo' => $p->codigo, 'nombres' => $p->nombres, 'dni' => $p->dni,
            'telefono' => $p->telefono, 'direccion' => $p->direccion, 'zona_id' => $p->zona_id,
            'tachos' => $p->tachos, 'capacidad_tacho_l' => $p->capacidad_tacho_l];
    }

    public function test_formulario_anterior_no_borra_responsable_y_el_actual_permite_retirarlo(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $p = Proveedor::factory()->create(['dueno' => 'Responsable ficticio']);
        $this->get(route('admin.proveedores.edit', $p->id))->assertOk()->assertSee('Responsable ficticio');
        $this->put(route('admin.proveedores.update', $p->id), $this->datos($p))->assertSessionHasNoErrors();
        $this->assertSame('Responsable ficticio', $p->fresh()->dueno);
        $this->put(route('admin.proveedores.update', $p->id), [...$this->datos($p), 'dueno' => ''])->assertSessionHasNoErrors();
        $this->assertNull($p->fresh()->dueno);
    }

    public function test_responsable_se_sincroniza_y_cambiar_estado_o_vincular_no_lo_pierde(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '2468']);
        $p = Proveedor::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => ['proveedor'], 'dni' => $p->dni]);
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        $this->withToken($token)->putJson('/api/movil/cambios/proveedor', [
            ...$this->datos($p), 'servidorId' => $p->id, 'zonaId' => $p->zona_id, 'zonaNombre' => $p->zona->nombre,
            'capacidadTachoL' => (float) $p->capacidad_tacho_l, 'estado' => 'ACTIVO', 'usuarioUsername' => $cuenta->username,
            'dueno' => 'Responsable compartido', 'controlVersion' => true, 'revisionBase' => RevisionCatalogo::de($p->refresh()),
        ])->assertOk();
        $this->assertSame('Responsable compartido', $p->fresh()->dueno);
        $this->assertSame($cuenta->id, $p->fresh()->usuario_id);
        $datos = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json('proveedores');
        $ficha = collect($datos)->firstWhere('id', $p->id);
        $this->assertSame('Responsable compartido', $ficha['dueno']);
        $this->assertTrue($ficha['responsableIncluido']);
        $this->actingAs($admin, 'operador')->patch(route('admin.proveedores.estado', $p->id), ['estado' => 'suspendido'])->assertSessionHasNoErrors();
        $this->post(route('admin.proveedores.desvincular', $p->id))->assertSessionHasNoErrors();
        $this->assertSame('Responsable compartido', $p->fresh()->dueno);
        $this->assertNull($p->fresh()->usuario_id);
    }

    public function test_limite_del_responsable_se_valida_sin_truncar_ni_borrar_el_valor_actual(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $p = Proveedor::factory()->create(['dueno' => 'Conservar']);
        $this->put(route('admin.proveedores.update', $p->id), [...$this->datos($p), 'dueno' => str_repeat('x', 151)])
            ->assertSessionHasErrors('dueno');
        $this->assertSame('Conservar', $p->fresh()->dueno);
    }
}
