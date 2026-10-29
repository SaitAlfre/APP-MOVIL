<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Cliente;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Importacion;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminFigmaModulesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_todos_los_modulos_del_figma_tienen_rutas_reales(): void
    {
        foreach (['entregas', 'sanciones', 'inventario', 'ventas', 'comunicados', 'importaciones', 'configuracion', 'design-system'] as $modulo) {
            $this->get("/admin/{$modulo}")->assertOk();
        }
    }

    public function test_venta_completada_descuenta_stock_y_registra_movimiento(): void
    {
        $producto = Producto::factory()->create(['existencia' => 20, 'unidad_produccion' => 'kg', 'precio_mayorista' => 12]);
        $cliente = Cliente::create(['codigo' => 'CLI-001', 'tipo' => 'mayorista', 'nombre' => 'Mercado Huata', 'documento' => '20123456789']);

        $this->post('/admin/ventas', [
            'tipo_comprador' => 'mayorista', 'cliente_id' => $cliente->id, 'descuento' => 5, 'estado' => 'completada',
            'productos' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio_unitario' => 12]],
        ])->assertRedirect();

        $this->assertDatabaseHas('ventas', ['cliente_id' => $cliente->id, 'subtotal' => 36, 'total' => 31, 'estado' => 'completada']);
        $this->assertEquals(17.0, (float) $producto->fresh()->existencia);
        $this->assertDatabaseHas('movimientos_producto', ['producto_id' => $producto->id, 'cantidad' => -3]);
    }

    public function test_ajuste_no_permite_inventario_negativo(): void
    {
        $producto = Producto::factory()->create(['existencia' => 2]);

        $this->post('/admin/inventario/ajustes', [
            'producto_id' => $producto->id, 'operacion' => 'salida', 'cantidad' => 3, 'motivo' => 'Merma',
        ])->assertNotFound();

        $this->assertEquals(2.0, (float) $producto->fresh()->existencia);
        $this->assertSame(0, MovimientoProducto::count());
    }

    public function test_consultar_sanciones_no_inventa_castigos_a_partir_de_un_control_rechazado(): void
    {
        $entrega = Entrega::factory()->create();
        ControlCalidad::create([
            'entrega_id' => $entrega->id, 'usuario_id' => $this->admin->id, 'resultado' => 'rechazado',
            'observaciones' => 'Agua añadida', 'evaluado_en' => now(),
        ]);

        $this->get('/admin/sanciones')->assertOk()
            ->assertSee('Calidad › Casos y sanciones')
            ->assertSee(route('admin.calidad.index', ['tab' => 'incidencias']), false);
        $this->assertDatabaseCount('sanciones', 0);
        $this->assertDatabaseCount('comunicados', 0);
    }

    public function test_sancion_aprobada_se_descuenta_una_sola_vez_de_la_liquidacion(): void
    {
        TarifaLeche::factory()->create(['precio' => 2, 'desde' => '2020-01-01', 'hasta' => '2035-12-31']);
        $entrega = Entrega::factory()->create(['litros' => 100, 'registrado_en' => now()]);
        $sancion = Sancion::create([
            'proveedor_id' => $entrega->proveedor_id, 'tipo' => 'calidad', 'severidad' => 'alta',
            'descuento' => 30, 'motivo' => 'Control rechazado', 'estado' => 'aprobada',
        ]);

        $this->post('/admin/liquidaciones', [
            'proveedor_id' => $entrega->proveedor_id, 'periodo_inicio' => today('America/Lima')->toDateString(),
            'periodo_fin' => today('America/Lima')->toDateString(), 'precio_litro' => 2,
        ])->assertRedirect('/admin/liquidaciones');

        $this->assertDatabaseHas('liquidaciones', ['monto_total' => 170, 'descuento_sanciones' => 30]);
        $this->assertNotNull($sancion->fresh()->aplicada_liquidacion_id);
    }

    public function test_comunicado_publicado_y_configuracion_se_persisten(): void
    {
        $this->post('/admin/comunicados', [
            'titulo' => 'Nuevo precio', 'contenido' => 'El precio cambia desde mañana.',
            'audiencia' => 'proveedores', 'estado' => 'publicado',
        ])->assertRedirect();
        $this->assertDatabaseHas('comunicados', ['titulo' => 'Nuevo precio', 'estado' => 'publicado']);
        $this->assertNotNull(Comunicado::first()->publicado_en);

        $this->put('/admin/configuracion', [
            'organizacion_nombre' => 'Ecolactea Huata', 'organizacion_ruc' => '20123456789',
            'organizacion_direccion' => 'Huata', 'organizacion_telefono' => '999999999',
            'moneda' => 'PEN', 'inicio_semana' => 'jueves', 'dia_pago' => 'miércoles',
            'precio_base_litro' => 1.95, 'login_intentos_maximos' => 4,
            'login_bloqueo_minutos' => 30, 'requerir_cambio_pin' => 1,
        ])->assertRedirect();
        $this->assertSame('4', Configuracion::find('login_intentos_maximos')->valor);
    }

    public function test_importacion_csv_valida_y_confirma_proveedores(): void
    {
        $csv = "codigo,nombres,dni,telefono,direccion,zona,tachos,capacidad_tacho_l\nPRV-900,Ana Huanca,12345678,999999999,Huata Norte,Huata Norte,2,40\n";
        $respuesta = $this->post('/admin/importaciones/validar', [
            'tipo' => 'proveedores', 'archivo' => UploadedFile::fake()->createWithContent('proveedores.csv', $csv),
        ]);
        $importacion = Importacion::firstOrFail();
        $respuesta->assertRedirect(route('admin.importaciones.preview', $importacion));

        $this->post(route('admin.importaciones.confirmar', $importacion))->assertRedirect(route('admin.importaciones.index'));
        $this->assertDatabaseHas('proveedores', ['codigo' => 'PRV-900', 'dni' => '12345678']);
        $this->assertSame(1, Proveedor::where('codigo', 'PRV-900')->count());
    }
}
