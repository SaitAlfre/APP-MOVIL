<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listado filtrable de proveedores y su ficha por pestañas.
 */
class AdminProveedoresPanelTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_el_listado_filtra_por_nombre_codigo_o_dni(): void
    {
        Proveedor::factory()->create(['nombres' => 'Celestino Apaza', 'codigo' => 'PRV-101', 'dni' => '11111111']);
        Proveedor::factory()->create(['nombres' => 'Felipa Mamani', 'codigo' => 'PRV-202', 'dni' => '22222222']);

        $porNombre = $this->get('/admin/proveedores?q=Celestino');
        $porNombre->assertOk();
        $porNombre->assertSee('Celestino Apaza');
        $porNombre->assertDontSee('Felipa Mamani');

        $porCodigo = $this->get('/admin/proveedores?q=PRV-202');
        $porCodigo->assertSee('Felipa Mamani');
        $porCodigo->assertDontSee('Celestino Apaza');

        $porDni = $this->get('/admin/proveedores?q=11111111');
        $porDni->assertSee('Celestino Apaza');
        $porDni->assertDontSee('Felipa Mamani');
    }

    public function test_el_listado_filtra_por_zona_y_por_estado(): void
    {
        $norte = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $sur = Zona::factory()->create(['nombre' => 'Zona Sur']);
        Proveedor::factory()->create(['nombres' => 'Ana Norte', 'zona_id' => $norte->id, 'estado' => 'activo']);
        Proveedor::factory()->create(['nombres' => 'Beto Sur', 'zona_id' => $sur->id, 'estado' => 'activo']);
        Proveedor::factory()->create(['nombres' => 'Carla Retirada', 'zona_id' => $norte->id, 'estado' => 'retirado']);

        $porZona = $this->get('/admin/proveedores?zona_id='.$norte->id);
        $porZona->assertOk();
        $porZona->assertSee('Ana Norte');
        $porZona->assertDontSee('Beto Sur');

        $porEstado = $this->get('/admin/proveedores?estado=retirado');
        $porEstado->assertSee('Carla Retirada');
        $porEstado->assertDontSee('Ana Norte');

        $combinado = $this->get('/admin/proveedores?zona_id='.$norte->id.'&estado=activo');
        $combinado->assertSee('Ana Norte');
        $combinado->assertDontSee('Carla Retirada');
        $combinado->assertDontSee('Beto Sur');
    }

    public function test_una_busqueda_sin_coincidencias_muestra_el_estado_vacio_y_no_el_inicial(): void
    {
        Proveedor::factory()->create(['nombres' => 'Celestino Apaza']);

        $respuesta = $this->get('/admin/proveedores?q=zzzzz');

        $respuesta->assertOk();
        $respuesta->assertSee('No se encontraron proveedores');
        $respuesta->assertDontSee('Aún no hay proveedores registrados');
    }

    public function test_un_filtro_de_zona_inexistente_se_rechaza_en_vez_de_ignorarse(): void
    {
        $this->get('/admin/proveedores?zona_id=999999')->assertSessionHasErrors('zona_id');
        $this->get('/admin/proveedores?estado=inventado')->assertSessionHasErrors('estado');
    }

    public function test_el_listado_muestra_los_litros_de_la_semana_y_la_ultima_entrega(): void
    {
        $proveedor = Proveedor::factory()->create(['nombres' => 'Celestino Apaza']);
        $jornada = Jornada::factory()->create();

        // Dentro de la semana en curso.
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'jornada_id' => $jornada->id,
            'litros' => 30,
            'registrado_en' => now()->startOfWeek(),
        ]);
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'jornada_id' => $jornada->id,
            'litros' => 12.5,
            'registrado_en' => now(),
        ]);
        // Semana anterior: no debe sumar.
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'jornada_id' => $jornada->id,
            'litros' => 99,
            'registrado_en' => now()->startOfWeek()->subDays(3),
        ]);

        $respuesta = $this->get('/admin/proveedores');

        $respuesta->assertOk();
        $respuesta->assertViewHas('resumenSemana', function (array $resumen) use ($proveedor) {
            return abs($resumen[$proveedor->id]['litros'] - 42.5) < 0.01;
        });
        $respuesta->assertSee('42.5 L');
    }

    public function test_la_ficha_muestra_los_datos_generales_y_la_ultima_entrega(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $proveedor = Proveedor::factory()->create([
            'nombres' => 'Celestino Apaza',
            'codigo' => 'PRV-101',
            'dni' => '11111111',
            'zona_id' => $zona->id,
        ]);
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'litros' => 48.5,
            'registrado_en' => now(),
        ]);

        $respuesta = $this->get("/admin/proveedores/{$proveedor->id}");

        $respuesta->assertOk();
        $respuesta->assertSee('Celestino Apaza');
        $respuesta->assertSee('PRV-101');
        $respuesta->assertSee('Zona Norte');
        $respuesta->assertSee('48.5');
        $respuesta->assertSee('Litros semana actual');
    }

    public function test_la_ficha_separa_entregas_calidad_liquidaciones_y_auditoria_en_pestanas(): void
    {
        $proveedor = Proveedor::factory()->create(['nombres' => 'Celestino Apaza']);
        $entrega = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 40, 'registrado_en' => now()]);
        ControlCalidad::query()->create([
            'entrega_id' => $entrega->id,
            'usuario_id' => $this->admin->id,
            'resultado' => 'observado',
            'temperatura_c' => 5.5,
            'acidez' => 19,
            'observaciones' => 'Temperatura sobre el rango',
            'evaluado_en' => now(),
        ]);
        Liquidacion::query()->create([
            'proveedor_id' => $proveedor->id,
            'periodo_inicio' => now()->subDays(6)->toDateString(),
            'periodo_fin' => now()->toDateString(),
            'litros_totales' => 40,
            'precio_litro' => 1.8,
            'monto_total' => 72,
            'estado' => 'pendiente',
            'generada_en' => now(),
        ]);
        Auditoria::query()->create([
            'entidad' => 'proveedor',
            'entidad_id' => $proveedor->id,
            'accion' => 'actualizar',
            'motivo' => 'Cambio de zona del proveedor',
            'usuario_id' => $this->admin->id,
            'ocurrido_en' => now(),
        ]);

        $this->get("/admin/proveedores/{$proveedor->id}?tab=entregas")->assertOk()->assertSee('40.0 L');
        $this->get("/admin/proveedores/{$proveedor->id}?tab=calidad")->assertOk()->assertSee('Temperatura sobre el rango');
        $this->get("/admin/proveedores/{$proveedor->id}?tab=liquidaciones")->assertOk()->assertSee('S/ 72.00');
        $this->get("/admin/proveedores/{$proveedor->id}?tab=auditoria")->assertOk()->assertSee('Cambio de zona del proveedor');
    }

    public function test_la_auditoria_de_la_ficha_solo_trae_los_movimientos_de_ese_proveedor(): void
    {
        $proveedor = Proveedor::factory()->create();
        $otro = Proveedor::factory()->create();

        Auditoria::query()->create([
            'entidad' => 'proveedor', 'entidad_id' => $proveedor->id, 'accion' => 'crear',
            'motivo' => 'Alta del proveedor observado', 'usuario_id' => $this->admin->id, 'ocurrido_en' => now(),
        ]);
        Auditoria::query()->create([
            'entidad' => 'proveedor', 'entidad_id' => $otro->id, 'accion' => 'crear',
            'motivo' => 'Alta de otro proveedor distinto', 'usuario_id' => $this->admin->id, 'ocurrido_en' => now(),
        ]);

        $respuesta = $this->get("/admin/proveedores/{$proveedor->id}?tab=auditoria");

        $respuesta->assertOk();
        $respuesta->assertSee('Alta del proveedor observado');
        $respuesta->assertDontSee('Alta de otro proveedor distinto');
    }

    public function test_una_pestana_desconocida_cae_en_el_resumen_en_vez_de_fallar(): void
    {
        $proveedor = Proveedor::factory()->create();

        $respuesta = $this->get("/admin/proveedores/{$proveedor->id}?tab=inventada");

        $respuesta->assertOk();
        $respuesta->assertViewHas('pestana', 'resumen');
    }

    public function test_la_ficha_de_un_proveedor_inexistente_devuelve_404(): void
    {
        $this->get('/admin/proveedores/999999')->assertNotFound();
    }

    public function test_la_ruta_de_alta_no_se_confunde_con_la_ficha_de_un_proveedor(): void
    {
        $this->get('/admin/proveedores/nuevo')->assertOk()->assertSee('Nuevo proveedor');
    }

    public function test_un_rol_de_solo_consulta_ve_la_ficha_pero_no_las_acciones_de_gestion(): void
    {
        $consulta = Usuario::factory()->create(['roles' => ['consulta']]);
        $proveedor = Proveedor::factory()->create(['nombres' => 'Celestino Apaza']);
        $this->actingAs($consulta, 'operador');

        $respuesta = $this->get("/admin/proveedores/{$proveedor->id}");

        $respuesta->assertOk();
        $respuesta->assertSee('Celestino Apaza');
        $respuesta->assertDontSee('Nuevo proveedor');
        $this->get('/admin/proveedores/nuevo')->assertForbidden();
    }

    public function test_el_resumen_semanal_ignora_entregas_anuladas(): void
    {
        $proveedor = Proveedor::factory()->create();
        $vehiculo = Vehiculo::factory()->create();
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id, 'vehiculo_id' => $vehiculo->id,
            'litros' => 20, 'registrado_en' => now(), 'anulada' => false,
        ]);
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id, 'vehiculo_id' => $vehiculo->id,
            'litros' => 80, 'registrado_en' => now(), 'anulada' => true,
        ]);

        $respuesta = $this->get('/admin/proveedores');

        $respuesta->assertViewHas('resumenSemana', function (array $resumen) use ($proveedor) {
            return abs($resumen[$proveedor->id]['litros'] - 20.0) < 0.01;
        });
    }
}
