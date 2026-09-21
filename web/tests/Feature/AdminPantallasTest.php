<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Importacion;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada pantalla del panel debe responder 200 con datos reales cargados.
 * Cubre también las pestañas y fichas de detalle, que no tienen ruta propia en el menú.
 */
class AdminPantallasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_todas_las_pantallas_de_solo_lectura_responden_con_datos(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $vehiculo = Vehiculo::factory()->create();
        $acopiador = Usuario::factory()->create(['roles' => ['acopiador'], 'nombres' => 'Evaristo Mamani']);
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id, 'nombres' => 'Celestino Apaza']);
        $jornada = Jornada::factory()->create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id]);
        Entrega::factory()->create([
            'jornada_id' => $jornada->id,
            'proveedor_id' => $proveedor->id,
            'zona_id' => $zona->id,
            'vehiculo_id' => $vehiculo->id,
            'litros' => 42.5,
        ]);
        Producto::factory()->create(['nombre' => 'Queso fresco']);
        Liquidacion::query()->create([
            'proveedor_id' => $proveedor->id,
            'periodo_inicio' => now()->subDays(6)->toDateString(),
            'periodo_fin' => now()->toDateString(),
            'litros_totales' => 120,
            'precio_litro' => 1.8,
            'monto_total' => 216,
            'estado' => 'pendiente',
            'generada_en' => now(),
        ]);
        Auditoria::query()->create([
            'entidad' => 'proveedor',
            'entidad_id' => $proveedor->id,
            'accion' => 'crear',
            'motivo' => 'Alta de proveedor',
            'usuario_id' => $this->admin->id,
            'ocurrido_en' => now(),
        ]);

        $liquidacion = Liquidacion::query()->firstOrFail();
        $registroAuditoria = Auditoria::query()->firstOrFail();

        $rutas = [
            '/admin/dashboard',
            '/admin/usuarios',
            '/admin/usuarios/nuevo',
            "/admin/usuarios/{$acopiador->id}/editar",
            '/admin/acopiadores',
            '/admin/acopiadores/catalogo',
            '/admin/jornadas',
            '/admin/acopiadores/jornadas/nueva',
            "/admin/acopiadores/jornadas/{$jornada->id}",
            '/admin/proveedores',
            '/admin/proveedores/nuevo',
            "/admin/proveedores/{$proveedor->id}",
            "/admin/proveedores/{$proveedor->id}?tab=entregas",
            "/admin/proveedores/{$proveedor->id}?tab=calidad",
            "/admin/proveedores/{$proveedor->id}?tab=liquidaciones",
            "/admin/proveedores/{$proveedor->id}?tab=auditoria",
            "/admin/proveedores/{$proveedor->id}/editar",
            '/admin/zonas-vehiculos',
            '/admin/zonas-vehiculos?tab=rutas',
            '/admin/zonas-vehiculos?tab=vehiculos',
            '/admin/zonas/nueva',
            '/admin/vehiculos/nuevo',
            '/admin/recepcion',
            '/admin/entregas',
            '/admin/calidad',
            '/admin/calidad?tab=reglas',
            '/admin/calidad/nuevo',
            '/admin/sanciones',
            '/admin/liquidaciones',
            '/admin/liquidaciones/nueva',
            "/admin/liquidaciones/{$liquidacion->id}",
            '/admin/produccion',
            '/admin/produccion/producir',
            '/admin/produccion/historial',
            '/admin/produccion/productos',
            '/admin/produccion/productos/nuevo',
            '/admin/inventario',
            '/admin/inventario?tab=movimientos',
            '/admin/ventas',
            '/admin/ventas?tab=clientes',
            '/admin/comunicados',
            '/admin/reportes',
            '/admin/importaciones',
            '/admin/auditoria',
            "/admin/auditoria/{$registroAuditoria->id}",
            '/admin/configuracion',
            '/admin/design-system',
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_la_vista_previa_de_una_importacion_se_muestra_con_sus_errores(): void
    {
        $importacion = Importacion::query()->create([
            'token' => 'token-de-prueba',
            'tipo' => 'proveedores',
            'archivo_original' => 'proveedores.csv',
            'ruta_archivo' => 'importaciones/token-de-prueba.csv',
            'estado' => 'con_errores',
            'filas_total' => 2,
            'filas_error' => 1,
            'vista_previa' => [['codigo' => 'PRV-001', 'nombres' => 'Celestino Apaza']],
            'errores' => ['Fila 3: contiene campos obligatorios vacíos.'],
            'usuario_id' => $this->admin->id,
        ]);

        $respuesta = $this->get("/admin/importaciones/{$importacion->id}/vista-previa");

        $respuesta->assertOk();
        $respuesta->assertSee('Fila 3: contiene campos obligatorios vacíos.', false);
        $respuesta->assertSee('Corrige el archivo antes de continuar');
    }

    public function test_la_navegacion_del_panel_solo_muestra_los_modulos_permitidos(): void
    {
        $calidad = Usuario::factory()->create(['roles' => ['calidad']]);
        $this->actingAs($calidad, 'operador');

        $respuesta = $this->get('/admin/calidad');

        $respuesta->assertOk();
        $respuesta->assertSee('Calidad');
        $respuesta->assertDontSee('Usuarios y roles');
        $respuesta->assertDontSee('Design System');
    }
}
