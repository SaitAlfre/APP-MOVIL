<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRecepcionModuleTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    private function crearJornadaConEntrega(float $litros, string $fecha, ?Vehiculo $vehiculo = null): Jornada
    {
        $jornada = Jornada::factory()->create(['vehiculo_id' => ($vehiculo ?? Vehiculo::factory()->create())->id, 'fecha' => $fecha]);

        Entrega::factory()->create([
            'jornada_id' => $jornada->id,
            'vehiculo_id' => $jornada->vehiculo_id,
            'litros' => $litros,
            'registrado_en' => $fecha.' 08:00:00',
            'anulada' => false,
        ]);

        return $jornada;
    }

    public function test_registrar_llegada_conserva_lo_recolectado_y_lo_medido_y_calcula_la_diferencia(): void
    {
        $admin = $this->comoAdmin();
        $jornada = $this->crearJornadaConEntrega(100, now()->toDateString());

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'),
            'litros_medidos' => 95,
            'motivo_diferencia' => 'Pérdida durante el traslado',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.recepcion.index'));

        $this->assertDatabaseHas('recepciones_acopio', [
            'jornada_id' => $jornada->id,
            'litros_recolectados' => 100,
            'litros_medidos' => 95,
            'usuario_id' => $admin->id,
        ]);
        $this->assertEquals(100.0, (float) Entrega::query()->where('jornada_id', $jornada->id)->first()->litros);
    }

    public function test_registrar_llegada_no_requiere_que_calidad_haya_evaluado(): void
    {
        $this->comoAdmin();
        $jornada = $this->crearJornadaConEntrega(80, now()->toDateString());

        $this->assertDatabaseCount('controles_calidad', 0);

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'),
            'litros_medidos' => 80,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('recepciones_acopio', ['jornada_id' => $jornada->id, 'litros_medidos' => 80]);
    }

    public function test_dos_viajes_del_mismo_camion_el_mismo_dia_generan_recepciones_independientes(): void
    {
        $this->comoAdmin();
        $vehiculo = Vehiculo::factory()->create();
        $hoy = now()->toDateString();

        $viaje1 = $this->crearJornadaConEntrega(60, $hoy, $vehiculo);
        $viaje2 = $this->crearJornadaConEntrega(40, $hoy, $vehiculo);

        $this->post(route('admin.recepcion.llegada.store', $viaje1->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 55, 'motivo_diferencia' => 'Derrame viaje 1',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.recepcion.llegada.store', $viaje2->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 40,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recepciones_acopio', 2);
        $this->assertDatabaseHas('recepciones_acopio', ['jornada_id' => $viaje1->id, 'litros_recolectados' => 60, 'litros_medidos' => 55]);
        $this->assertDatabaseHas('recepciones_acopio', ['jornada_id' => $viaje2->id, 'litros_recolectados' => 40, 'litros_medidos' => 40]);
    }

    public function test_registrar_llegada_de_nuevo_para_la_misma_jornada_corrige_en_vez_de_duplicar(): void
    {
        $this->comoAdmin();
        $jornada = $this->crearJornadaConEntrega(100, now()->toDateString());

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 95, 'motivo_diferencia' => 'Primera medición',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 90, 'motivo_diferencia' => 'Corrección tras revisar la balanza',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recepciones_acopio', 1);
        $this->assertDatabaseHas('recepciones_acopio', ['jornada_id' => $jornada->id, 'litros_medidos' => 90]);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'recepcion_acopio', 'accion' => 'corregir']);
    }

    public function test_rechaza_litros_medidos_negativos_y_exige_motivo_cuando_hay_diferencia(): void
    {
        $this->comoAdmin();
        $jornada = $this->crearJornadaConEntrega(100, now()->toDateString());

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => -1,
        ])->assertSessionHasErrors('litros_medidos');

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 90,
        ])->assertSessionHasErrors('litros_medidos');

        $this->assertDatabaseCount('recepciones_acopio', 0);
    }

    public function test_bloquea_registrar_o_corregir_la_recepcion_si_el_dia_ya_fue_procesado(): void
    {
        $this->comoAdmin();
        $hoy = now()->toDateString();
        $jornada = $this->crearJornadaConEntrega(100, $hoy);
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        LoteProduccion::query()->create([
            'codigo' => 'L-TEST-BLOQUEO',
            'producto_id' => Producto::factory()->create()->id,
            'fecha' => $hoy,
            'litros_por_unidad_snapshot' => 10,
            'litros_asignados' => 100,
            'unidades_estimadas' => 10,
            'estado' => 'borrador',
            'origen_acopio' => [],
            'responsable_id' => $admin->id,
        ]);

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 95, 'motivo_diferencia' => 'Tarde',
        ])->assertSessionHasErrors('litros_medidos');

        $this->assertDatabaseCount('recepciones_acopio', 0);
    }

    public function test_el_listado_filtra_por_estado_pendiente_y_registrada(): void
    {
        $this->comoAdmin();
        $hoy = now()->toDateString();
        $pendiente = $this->crearJornadaConEntrega(50, $hoy);
        $registrada = $this->crearJornadaConEntrega(70, $hoy);
        $this->post(route('admin.recepcion.llegada.store', $registrada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 70,
        ]);

        $this->get(route('admin.recepcion.index', ['estado' => 'pendiente']))
            ->assertSee($pendiente->vehiculo->placa)
            ->assertDontSee($registrada->vehiculo->placa);

        $this->get(route('admin.recepcion.index', ['estado' => 'registrada']))
            ->assertSee($registrada->vehiculo->placa);
    }

    public function test_un_proveedor_no_puede_acceder_a_recepcion(): void
    {
        $proveedor = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->actingAs($proveedor, 'operador');
        $jornada = $this->crearJornadaConEntrega(50, now()->toDateString());

        $this->get(route('admin.recepcion.index'))->assertForbidden();
        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 50,
        ])->assertForbidden();
    }
}
