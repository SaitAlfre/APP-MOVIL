<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bloque "panel" del centro operativo: fotografía del día y colas de trabajo.
 * Se distingue de los indicadores del periodo filtrado, que ya cubre AdminDashboardTest.
 */
class AdminDashboardPanelTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_litros_hoy_compara_contra_ayer_y_no_contra_todo_el_periodo(): void
    {
        $proveedor = Proveedor::factory()->create();
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 120, 'registrado_en' => now()]);
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 100, 'registrado_en' => now()->subDay()]);
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 500, 'registrado_en' => now()->subDays(5)]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertViewHas('panel', function (array $panel) {
            return abs($panel['litros_hoy'] - 120.0) < 0.01
                && abs($panel['litros_hoy_variacion'] - 20.0) < 0.01;
        });
    }

    public function test_sin_entregas_ayer_no_se_calcula_variacion(): void
    {
        Entrega::factory()->create(['litros' => 50, 'registrado_en' => now()]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertViewHas('panel', fn (array $panel) => $panel['litros_hoy_variacion'] === null);
    }

    public function test_proveedores_atendidos_cuenta_distintos_y_los_compara_con_los_activos(): void
    {
        $uno = Proveedor::factory()->create(['estado' => 'activo']);
        $dos = Proveedor::factory()->create(['estado' => 'activo']);
        Proveedor::factory()->create(['estado' => 'retirado']);

        Entrega::factory()->create(['proveedor_id' => $uno->id, 'registrado_en' => now()]);
        Entrega::factory()->create(['proveedor_id' => $uno->id, 'registrado_en' => now()]);
        Entrega::factory()->create(['proveedor_id' => $dos->id, 'registrado_en' => now()]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertViewHas('panel', function (array $panel) {
            return $panel['proveedores_atendidos'] === 2
                && $panel['proveedores_activos'] === 2
                && $panel['entregas_registradas'] === 3;
        });
    }

    public function test_las_jornadas_abiertas_se_listan_con_su_acopiador_zona_y_litros(): void
    {
        $acopiador = Usuario::factory()->create(['nombres' => 'Evaristo Mamani', 'roles' => ['acopiador']]);
        $zona = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $vehiculo = Vehiculo::factory()->create();
        $abierta = Jornada::factory()->create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);
        Jornada::factory()->cerrada()->create(['zona_id' => Zona::factory()->create()->id]);

        Entrega::factory()->create(['jornada_id' => $abierta->id, 'litros' => 62.5, 'registrado_en' => now()]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertViewHas('panel', function (array $panel) {
            return count($panel['jornadas_abiertas']) === 1
                && $panel['jornadas_abiertas'][0]['acopiador']?->nombres === 'Evaristo Mamani'
                && $panel['jornadas_abiertas'][0]['zona']?->nombre === 'Zona Norte'
                && abs($panel['jornadas_abiertas'][0]['litros'] - 62.5) < 0.01;
        });
        $respuesta->assertSee('Jornadas abiertas');
        $respuesta->assertSee('Evaristo Mamani');
    }

    public function test_la_distribucion_de_calidad_se_limita_al_periodo_filtrado(): void
    {
        $dentro = Entrega::factory()->create(['registrado_en' => now()]);
        $fuera = Entrega::factory()->create(['registrado_en' => now()->subMonths(6)]);

        ControlCalidad::query()->create([
            'entrega_id' => $dentro->id, 'usuario_id' => $this->admin->id, 'resultado' => 'aprobado',
            'temperatura_c' => 3, 'acidez' => 16, 'evaluado_en' => now(),
        ]);
        ControlCalidad::query()->create([
            'entrega_id' => $fuera->id, 'usuario_id' => $this->admin->id, 'resultado' => 'rechazado',
            'temperatura_c' => 9, 'acidez' => 25, 'evaluado_en' => now()->subMonths(6),
        ]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertViewHas('panel', function (array $panel) {
            return $panel['calidad'] === ['aprobado' => 1, 'observado' => 0, 'rechazado' => 0]
                && $panel['calidad_alertas'] === 0;
        });
    }

    public function test_las_entregas_sin_calidad_alimentan_la_cola_de_controles_pendientes(): void
    {
        $proveedor = Proveedor::factory()->create(['nombres' => 'Felipa Mamani']);
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 35, 'registrado_en' => now()]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertViewHas('panel', function (array $panel) use ($proveedor) {
            return count($panel['entregas_sin_calidad']) === 1
                && $panel['entregas_sin_calidad'][0]['proveedor']?->id === $proveedor->id;
        });
        $respuesta->assertSee('Controles de calidad pendientes');
        $respuesta->assertSee('Felipa Mamani');
    }

    public function test_las_ultimas_entregas_se_limitan_a_cinco_filas(): void
    {
        Entrega::factory()->count(8)->create(['registrado_en' => now()]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertViewHas('panel', fn (array $panel) => count($panel['ultimas_entregas']) === 5);
        $respuesta->assertSee('Últimas entregas');
    }

    public function test_los_accesos_rapidos_dependen_de_los_permisos_del_rol(): void
    {
        $respuestaAdmin = $this->get('/admin/dashboard');
        $respuestaAdmin->assertSee('Generar liquidación');
        $respuestaAdmin->assertSee('Control calidad');

        $calidad = Usuario::factory()->create(['roles' => ['calidad']]);
        $this->actingAs($calidad, 'operador');

        $respuestaCalidad = $this->get('/admin/dashboard');
        $respuestaCalidad->assertOk();
        $respuestaCalidad->assertSee('Control calidad');
        $respuestaCalidad->assertDontSee('Generar liquidación');
    }

    public function test_el_filtro_de_zona_no_afecta_los_indicadores_globales_de_produccion(): void
    {
        $norte = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $sur = Zona::factory()->create(['nombre' => 'Zona Sur']);
        Entrega::factory()->create(['zona_id' => $norte->id, 'litros' => 40, 'registrado_en' => now()]);
        Entrega::factory()->create(['zona_id' => $sur->id, 'litros' => 60, 'registrado_en' => now()]);

        $respuesta = $this->get('/admin/dashboard?zona_id='.$norte->id);

        $respuesta->assertOk();
        // Los litros recolectados sí se filtran por zona…
        $respuesta->assertViewHas('indicadores', fn (array $indicadores) => abs($indicadores['litros_recolectados'] - 40.0) < 0.01);
        // …pero los lotes de producción son globales y no dependen de la zona.
        $respuesta->assertViewHas('panel', fn (array $panel) => $panel['lotes_abiertos'] === 0);
    }
}
