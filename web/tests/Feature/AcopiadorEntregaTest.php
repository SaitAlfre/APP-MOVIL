<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcopiadorEntregaTest extends TestCase
{
    use RefreshDatabase;

    private function jornadaAbierta(): Jornada
    {
        $usuario = Usuario::factory()->create();
        $zona = Zona::factory()->create();
        $jornada = Jornada::factory()->create(['usuario_id' => $usuario->id, 'zona_id' => $zona->id]);
        $this->actingAs($usuario, 'operador');

        return $jornada;
    }

    public function test_registra_una_entrega_valida(): void
    {
        $jornada = $this->jornadaAbierta();
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 2, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);

        $response = $this->post('/acopiador/entregas', [
            'proveedor_id' => $proveedor->id,
            'litros' => 30,
            'tachos' => 1,
        ]);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertDatabaseHas('entregas', [
            'jornada_id' => $jornada->id,
            'proveedor_id' => $proveedor->id,
            'litros' => 30,
            'anulada' => false,
        ]);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'entrega', 'accion' => 'crear']);
    }

    public function test_rechaza_una_entrega_que_supera_la_capacidad_del_proveedor(): void
    {
        $jornada = $this->jornadaAbierta();
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 1, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);

        $response = $this->post('/acopiador/entregas', [
            'proveedor_id' => $proveedor->id,
            'litros' => 50,
            'tachos' => 1,
        ]);

        $response->assertSessionHasErrors('litros');
        $this->assertDatabaseMissing('entregas', ['proveedor_id' => $proveedor->id]);
    }

    public function test_rechaza_una_entrega_de_un_proveedor_no_activo(): void
    {
        $jornada = $this->jornadaAbierta();
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'estado' => 'suspendido']);

        $response = $this->post('/acopiador/entregas', [
            'proveedor_id' => $proveedor->id,
            'litros' => 10,
            'tachos' => 1,
        ]);

        $response->assertSessionHasErrors('litros');
        $this->assertDatabaseMissing('entregas', ['proveedor_id' => $proveedor->id]);
    }

    public function test_detecta_entrega_duplicada_en_la_misma_jornada_y_permite_sumar(): void
    {
        $jornada = $this->jornadaAbierta();
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 5, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);
        $existente = Entrega::factory()->create([
            'jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'litros' => 20, 'tachos' => 1,
        ]);

        $primeraRespuesta = $this->post('/acopiador/entregas', [
            'proveedor_id' => $proveedor->id,
            'litros' => 10,
            'tachos' => 1,
        ]);
        $primeraRespuesta->assertSessionHas('duplicado');

        $response = $this->post('/acopiador/entregas/sumar', [
            'entrega_id' => $existente->id,
            'litros' => 30,
            'tachos' => 2,
        ]);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertDatabaseHas('entregas', ['id' => $existente->id, 'litros' => 30, 'tachos' => 2]);
        $this->assertSame(1, Entrega::query()->where('jornada_id', $jornada->id)->count());
    }

    public function test_permite_registrar_aparte_una_entrega_duplicada(): void
    {
        $jornada = $this->jornadaAbierta();
        $proveedor = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 5, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'litros' => 20, 'tachos' => 1]);

        $response = $this->post('/acopiador/entregas', [
            'proveedor_id' => $proveedor->id,
            'litros' => 10,
            'tachos' => 1,
            'forzar' => 1,
        ]);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertSame(2, Entrega::query()->where('jornada_id', $jornada->id)->count());
    }

    public function test_anula_una_entrega_con_motivo(): void
    {
        $jornada = $this->jornadaAbierta();
        $entrega = Entrega::factory()->create(['jornada_id' => $jornada->id]);

        $response = $this->post("/acopiador/entregas/{$entrega->id}/anular", ['motivo' => 'Registro erróneo']);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertDatabaseHas('entregas', ['id' => $entrega->id, 'anulada' => true]);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'entrega', 'accion' => 'anular', 'motivo' => 'Registro erróneo']);
    }

    public function test_el_lote_registra_todas_las_entregas_con_el_mismo_lote_id(): void
    {
        $jornada = $this->jornadaAbierta();
        $p1 = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 5, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);
        $p2 = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 5, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);

        $response = $this->post('/acopiador/lote', [
            'items' => [
                ['proveedor_id' => $p1->id, 'litros' => 15, 'tachos' => 1],
                ['proveedor_id' => $p2->id, 'litros' => 25, 'tachos' => 1],
            ],
        ]);

        $response->assertRedirect(route('acopiador.home'));
        $this->assertSame(2, Entrega::query()->where('jornada_id', $jornada->id)->count());
        $loteId = Entrega::query()->where('proveedor_id', $p1->id)->value('lote_id');
        $this->assertNotNull($loteId);
        $this->assertSame($loteId, Entrega::query()->where('proveedor_id', $p2->id)->value('lote_id'));
    }

    public function test_el_lote_no_guarda_nada_si_un_item_es_invalido(): void
    {
        $jornada = $this->jornadaAbierta();
        $p1 = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 5, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);
        $p2 = Proveedor::factory()->create(['zona_id' => $jornada->zona_id, 'tachos' => 1, 'capacidad_tacho_l' => 10, 'estado' => 'activo']);

        $response = $this->post('/acopiador/lote', [
            'items' => [
                ['proveedor_id' => $p1->id, 'litros' => 15, 'tachos' => 1],
                ['proveedor_id' => $p2->id, 'litros' => 50, 'tachos' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Entrega::query()->where('jornada_id', $jornada->id)->count());
    }
}
