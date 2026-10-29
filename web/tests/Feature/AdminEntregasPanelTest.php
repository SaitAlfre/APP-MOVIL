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
 * Historial de recojos: filtros y totales. Solo consulta; los recojos se registran en su jornada.
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
     * Se comprueba la consulta y no solo el HTML, para no depender de otros textos de la página.
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

        $porFecha = $this->get('/admin/entregas?fecha='.now('America/Lima')->subDays(3)->toDateString());
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

        $respuesta = $this->get('/admin/entregas?fecha='.now('America/Lima')->subDays(2)->toDateString());

        // El total del filtro cambia…
        $respuesta->assertViewHas('litrosFiltrados', 75.0);
        // …pero "litros de hoy" sigue siendo el del día en curso.
        $respuesta->assertViewHas('litrosHoy', 25.0);
    }

    public function test_filtra_por_periodo_rapido_y_por_rango_de_fechas(): void
    {
        $lima = now('America/Lima');
        Entrega::factory()->create(['litros' => 10, 'registrado_en' => $lima->copy()->setTime(9, 0)->utc()]);
        Entrega::factory()->create(['litros' => 20, 'registrado_en' => $lima->copy()->subDay()->setTime(9, 0)->utc()]);
        Entrega::factory()->create(['litros' => 40, 'registrado_en' => $lima->copy()->subDays(20)->setTime(9, 0)->utc()]);

        $this->get('/admin/entregas?periodo=hoy')->assertOk()->assertViewHas('litrosFiltrados', 10.0);
        $this->get('/admin/entregas?periodo=ayer')->assertOk()->assertViewHas('litrosFiltrados', 20.0);
        $this->get('/admin/entregas?desde='.$lima->copy()->subDays(25)->toDateString().'&hasta='.$lima->copy()->subDays(2)->toDateString())
            ->assertOk()->assertViewHas('litrosFiltrados', 40.0);
        $this->get('/admin/entregas?periodo=mes')->assertSessionHasErrors('periodo');
    }

    public function test_los_encabezados_filtran_por_acopiador_y_por_origen(): void
    {
        $acopiador = Usuario::factory()->create(['nombres' => 'Rubén Quispe', 'roles' => ['acopiador']]);
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id]);
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'usuario_id' => $acopiador->id, 'litros' => 15, 'uuid_movil' => 'celular-1']);
        Entrega::factory()->create(['litros' => 30, 'uuid_movil' => null]);

        $this->get('/admin/entregas?acopiador_id='.$acopiador->id)->assertOk()
            ->assertViewHas('litrosFiltrados', 15.0)
            ->assertSee('th-filtro-activo', false)
            ->assertSee('· Rubén Quispe', false);
        $this->get('/admin/entregas?origen=web')->assertOk()->assertViewHas('litrosFiltrados', 30.0);
        $this->get('/admin/entregas?origen=celular&zona_id='.Zona::factory()->create()->id)->assertOk()
            ->assertSee('Sin recojos para esta búsqueda')
            ->assertSee('data-th-filtro', false);
        $this->get('/admin/entregas?origen=fax')->assertSessionHasErrors('origen');
    }

    public function test_un_filtro_invalido_se_rechaza(): void
    {
        $this->get('/admin/entregas?zona_id=999999')->assertSessionHasErrors('zona_id');
        $this->get('/admin/entregas?estado=inventado')->assertSessionHasErrors('estado');
        $this->get('/admin/entregas?fecha=no-es-fecha')->assertSessionHasErrors('fecha');
    }

    public function test_el_historial_es_solo_consulta_y_ya_no_registra_por_lote(): void
    {
        $jornada = Jornada::factory()->create();
        Entrega::factory()->create(['jornada_id' => $jornada->id]);

        $this->get('/admin/entregas')->assertOk()
            ->assertSee('Historial de recojos')
            ->assertSee(route('admin.acopiadores.jornadas.show', $jornada->id), false)
            ->assertDontSee('registro-lote', false);
        $this->post('/admin/entregas/lote', ['jornada_id' => $jornada->id, 'entregas' => []])->assertNotFound();
    }

    public function test_calidad_ve_el_historial_sin_enlaces_a_jornadas(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['calidad']]), 'operador');
        $entrega = Entrega::factory()->create();

        $this->get('/admin/entregas')->assertOk()
            ->assertDontSee(route('admin.acopiadores.jornadas.show', $entrega->jornada_id), false);
    }
}
