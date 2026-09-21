<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Listado de entregas: filtros, totales y registro por lote.
 */
class AdminEntregasPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    /**
     * Nombres de los proveedores presentes en la tabla de resultados.
     *
     * El listado no basta con `assertDontSee`: el modal de registro por lote incluye
     * un selector con todos los proveedores activos, así que se comprueba la consulta.
     *
     * @return list<string>
     */
    private function proveedoresListados(TestResponse $respuesta): array
    {
        return collect($respuesta->viewData('entregas')->items())
            ->map(fn ($entrega) => $entrega->proveedor?->nombres)
            ->filter()
            ->values()
            ->all();
    }

    public function test_busca_por_proveedor_y_por_acopiador(): void
    {
        $acopiador = Usuario::factory()->create(['nombres' => 'Evaristo Mamani', 'roles' => ['acopiador']]);
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id]);
        $proveedor = Proveedor::factory()->create(['nombres' => 'Celestino Apaza']);
        $otroProveedor = Proveedor::factory()->create(['nombres' => 'Felipa Mamani']);

        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'jornada_id' => $jornada->id, 'litros' => 10]);
        Entrega::factory()->create(['proveedor_id' => $otroProveedor->id, 'litros' => 20]);

        $porProveedor = $this->get('/admin/entregas?buscar=Celestino');
        $porProveedor->assertOk();
        $porProveedor->assertSee('Celestino Apaza');
        $this->assertSame(['Celestino Apaza'], $this->proveedoresListados($porProveedor));

        $porAcopiador = $this->get('/admin/entregas?buscar=Evaristo');
        $this->assertSame(['Celestino Apaza'], $this->proveedoresListados($porAcopiador));
    }

    public function test_filtra_por_fecha_zona_y_estado(): void
    {
        $norte = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $sur = Zona::factory()->create(['nombre' => 'Zona Sur']);
        $ana = Proveedor::factory()->create(['nombres' => 'Ana Norte', 'zona_id' => $norte->id]);
        $beto = Proveedor::factory()->create(['nombres' => 'Beto Sur', 'zona_id' => $sur->id]);
        $carla = Proveedor::factory()->create(['nombres' => 'Carla Anulada', 'zona_id' => $norte->id]);

        Entrega::factory()->create(['proveedor_id' => $ana->id, 'zona_id' => $norte->id, 'registrado_en' => now()]);
        Entrega::factory()->create(['proveedor_id' => $beto->id, 'zona_id' => $sur->id, 'registrado_en' => now()->subDays(3)]);
        Entrega::factory()->create(['proveedor_id' => $carla->id, 'zona_id' => $norte->id, 'registrado_en' => now(), 'anulada' => true]);

        $porZona = $this->get('/admin/entregas?zona_id='.$norte->id);
        $porZona->assertOk();
        $this->assertEqualsCanonicalizing(['Ana Norte', 'Carla Anulada'], $this->proveedoresListados($porZona));

        $porFecha = $this->get('/admin/entregas?fecha='.now()->subDays(3)->toDateString());
        $this->assertSame(['Beto Sur'], $this->proveedoresListados($porFecha));

        $porEstado = $this->get('/admin/entregas?estado=anulada');
        $this->assertSame(['Carla Anulada'], $this->proveedoresListados($porEstado));

        $registradas = $this->get('/admin/entregas?estado=registrada');
        $this->assertEqualsCanonicalizing(['Ana Norte', 'Beto Sur'], $this->proveedoresListados($registradas));
    }

    public function test_el_total_del_filtro_excluye_las_entregas_anuladas(): void
    {
        Entrega::factory()->create(['litros' => 30, 'registrado_en' => now()]);
        Entrega::factory()->create(['litros' => 20, 'registrado_en' => now()]);
        Entrega::factory()->create(['litros' => 90, 'registrado_en' => now(), 'anulada' => true]);

        $respuesta = $this->get('/admin/entregas');

        $respuesta->assertOk();
        $respuesta->assertViewHas('litrosFiltrados', 50.0);
        $respuesta->assertViewHas('litrosHoy', 50.0);
        $respuesta->assertViewHas('entregasHoy', 2);
    }

    public function test_los_totales_del_encabezado_respetan_el_filtro_de_fecha(): void
    {
        Entrega::factory()->create(['litros' => 25, 'registrado_en' => now()]);
        Entrega::factory()->create(['litros' => 75, 'registrado_en' => now()->subDays(2)]);

        $respuesta = $this->get('/admin/entregas?fecha='.now()->subDays(2)->toDateString());

        // El total del filtro cambia…
        $respuesta->assertViewHas('litrosFiltrados', 75.0);
        // …pero "litros de hoy" sigue siendo el del día en curso.
        $respuesta->assertViewHas('litrosHoy', 25.0);
    }

    public function test_un_filtro_invalido_se_rechaza(): void
    {
        $this->get('/admin/entregas?zona_id=999999')->assertSessionHasErrors('zona_id');
        $this->get('/admin/entregas?estado=inventado')->assertSessionHasErrors('estado');
        $this->get('/admin/entregas?fecha=no-es-fecha')->assertSessionHasErrors('fecha');
    }

    public function test_sin_jornadas_abiertas_el_registro_por_lote_avisa_en_vez_de_ofrecer_el_formulario(): void
    {
        Jornada::factory()->cerrada()->create();

        $respuesta = $this->get('/admin/entregas');

        $respuesta->assertOk();
        $respuesta->assertSee('No hay jornadas abiertas', false);
        $respuesta->assertDontSee('Registrar lote');
    }

    public function test_el_registro_por_lote_rechaza_proveedores_repetidos_en_la_misma_carga(): void
    {
        $jornada = Jornada::factory()->create();
        $proveedor = Proveedor::factory()->create(['tachos' => 5, 'capacidad_tacho_l' => 40]);

        $respuesta = $this->post('/admin/entregas/lote', [
            'jornada_id' => $jornada->id,
            'entregas' => [
                ['proveedor_id' => $proveedor->id, 'litros' => 10, 'tachos' => 1],
                ['proveedor_id' => $proveedor->id, 'litros' => 12, 'tachos' => 1],
            ],
        ]);

        $respuesta->assertSessionHasErrors('entregas.1.proveedor_id');
        $this->assertDatabaseCount('entregas', 0);
    }

    public function test_un_rol_de_consulta_ve_el_listado_pero_no_puede_registrar_lotes(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['consulta']]), 'operador');

        $this->get('/admin/entregas')->assertOk()->assertDontSee('Registro por lote');
        $this->post('/admin/entregas/lote', ['jornada_id' => 1, 'entregas' => []])->assertForbidden();
    }
}
