<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJornadaEntregaTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    public function test_un_admin_puede_abrir_una_jornada_en_nombre_de_un_acopiador(): void
    {
        $this->comoAdmin();
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();

        $response = $this->post('/admin/acopiadores/jornadas', [
            'usuario_id' => $acopiador->id,
            'zona_id' => $zona->id,
            'vehiculo_id' => $vehiculo->id,
        ]);

        $jornada = Jornada::query()->where('usuario_id', $acopiador->id)->firstOrFail();
        $response->assertRedirect(route('admin.acopiadores.jornadas.show', $jornada->id));
        $this->assertDatabaseHas('jornadas', ['usuario_id' => $acopiador->id, 'zona_id' => $zona->id]);
    }

    public function test_no_se_puede_abrir_una_jornada_en_una_zona_ya_ocupada_por_otro_acopiador(): void
    {
        $this->comoAdmin();
        $zona = Zona::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        $acopiador1 = Usuario::factory()->create(['roles' => ['acopiador']]);
        $acopiador2 = Usuario::factory()->create(['roles' => ['acopiador']]);

        Jornada::factory()->create(['usuario_id' => $acopiador1->id, 'zona_id' => $zona->id, 'fecha' => now()->toDateString(), 'cerrada_en' => null]);

        $response = $this->post('/admin/acopiadores/jornadas', [
            'usuario_id' => $acopiador2->id,
            'zona_id' => $zona->id,
            'vehiculo_id' => $vehiculo->id,
        ]);

        $response->assertSessionHasErrors('zona_id');
        $this->assertDatabaseCount('jornadas', 1);
    }

    public function test_un_admin_puede_registrar_una_entrega_en_nombre_del_acopiador_de_la_jornada(): void
    {
        $this->comoAdmin();
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);
        $zona = Zona::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'tachos' => 5, 'capacidad_tacho_l' => 40]);
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'cerrada_en' => null]);

        $response = $this->post("/admin/acopiadores/jornadas/{$jornada->id}/entregas", [
            'proveedor_id' => $proveedor->id,
            'litros' => 20,
            'tachos' => 1,
        ]);

        $response->assertRedirect(route('admin.acopiadores.jornadas.show', $jornada->id));
        $this->assertDatabaseHas('entregas', [
            'jornada_id' => $jornada->id,
            'proveedor_id' => $proveedor->id,
            'usuario_id' => $acopiador->id,
            'litros' => 20,
        ]);
    }

    public function test_no_se_puede_registrar_una_entrega_que_supera_la_capacidad_del_proveedor(): void
    {
        $this->comoAdmin();
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);
        $zona = Zona::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'tachos' => 1, 'capacidad_tacho_l' => 40]);
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'cerrada_en' => null]);

        $response = $this->post("/admin/acopiadores/jornadas/{$jornada->id}/entregas", [
            'proveedor_id' => $proveedor->id,
            'litros' => 50,
            'tachos' => 1,
        ]);

        $response->assertSessionHasErrors('litros');
        $this->assertDatabaseCount('entregas', 0);
    }

    public function test_registrar_una_entrega_duplicada_del_mismo_proveedor_muestra_opcion_de_sumar(): void
    {
        $this->comoAdmin();
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);
        $zona = Zona::factory()->create();
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'tachos' => 5, 'capacidad_tacho_l' => 40]);
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'cerrada_en' => null]);

        $this->post("/admin/acopiadores/jornadas/{$jornada->id}/entregas", ['proveedor_id' => $proveedor->id, 'litros' => 10, 'tachos' => 1]);

        $response = $this->post("/admin/acopiadores/jornadas/{$jornada->id}/entregas", ['proveedor_id' => $proveedor->id, 'litros' => 5, 'tachos' => 1]);

        $response->assertSessionHas('duplicado');
        $this->assertDatabaseCount('entregas', 1);
    }

    public function test_un_admin_puede_anular_una_entrega_con_motivo(): void
    {
        $this->comoAdmin();
        $entrega = Entrega::factory()->create(['anulada' => false]);

        $response = $this->post("/admin/acopiadores/entregas/{$entrega->id}/anular", ['motivo' => 'Registro duplicado por error']);

        $response->assertRedirect(route('admin.acopiadores.jornadas.show', $entrega->jornada_id));
        $this->assertDatabaseHas('entregas', ['id' => $entrega->id, 'anulada' => true]);
    }

    public function test_un_admin_puede_cerrar_una_jornada(): void
    {
        $this->comoAdmin();
        $jornada = Jornada::factory()->create(['cerrada_en' => null]);

        $response = $this->patch("/admin/acopiadores/jornadas/{$jornada->id}/cerrar");

        $response->assertRedirect(route('admin.acopiadores.index'));
        $this->assertNotNull($jornada->fresh()->cerrada_en);
    }

    public function test_un_acopiador_no_puede_acceder_a_la_gestion_de_jornadas_del_admin(): void
    {
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);
        $this->actingAs($acopiador, 'operador');

        $this->get('/admin/acopiadores/jornadas/nueva')->assertForbidden();
    }
}
