<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Cliente;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pestañas de inventario, ventas, calidad y producción: cada una debe traer su propio
 * contenido y no filtrarse en las demás.
 */
class AdminPestanasModulosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    public function test_inventario_marca_stock_bajo_por_debajo_del_umbral(): void
    {
        Producto::factory()->create(['nombre' => 'Queso fresco', 'existencia' => 4]);
        Producto::factory()->create(['nombre' => 'Yogurt natural', 'existencia' => 25]);

        $respuesta = $this->get('/admin/inventario');

        $respuesta->assertOk();
        $respuesta->assertSee('Queso fresco');
        $respuesta->assertSee('Stock bajo');
        $respuesta->assertSee('Normal');
    }

    public function test_los_movimientos_viven_en_su_pestana_y_muestran_el_signo(): void
    {
        $producto = Producto::factory()->create(['nombre' => 'Queso fresco', 'unidad_produccion' => 'kg']);
        MovimientoProducto::query()->create([
            'producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => 86,
            'unidad' => 'kg', 'motivo' => 'Lote LP-0920-01', 'usuario_id' => $this->admin->id, 'fecha' => now(),
        ]);
        MovimientoProducto::query()->create([
            'producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => -2,
            'unidad' => 'kg', 'motivo' => 'Salida: merma de cámara', 'usuario_id' => $this->admin->id, 'fecha' => now(),
        ]);

        $resumen = $this->get('/admin/inventario');
        $resumen->assertViewHas('tab', 'resumen');
        $resumen->assertDontSee('Lote LP-0920-01');

        $movimientos = $this->get('/admin/inventario?tab=movimientos');
        $movimientos->assertOk();
        $movimientos->assertViewHas('tab', 'movimientos');
        $movimientos->assertSee('Lote LP-0920-01');
        $movimientos->assertSee('+86.00 kg');
        $movimientos->assertSee('-2.00 kg');
    }

    public function test_sin_productos_el_inventario_remite_a_produccion(): void
    {
        $respuesta = $this->get('/admin/inventario');

        $respuesta->assertOk();
        $respuesta->assertSee('Aún no hay productos activos');
        $respuesta->assertSee('Ir a Producción', false);
    }

    public function test_el_total_de_ventas_de_hoy_solo_cuenta_las_completadas(): void
    {
        $cliente = Cliente::query()->create(['codigo' => 'CLI-001', 'tipo' => 'mayorista', 'nombre' => 'Mercado Huata', 'documento' => '20123456789']);

        Venta::query()->create([
            'codigo' => 'VTA-0001', 'cliente_id' => $cliente->id, 'usuario_id' => $this->admin->id,
            'subtotal' => 100, 'descuento' => 0, 'total' => 100, 'estado' => 'completada', 'vendida_en' => now(),
        ]);
        Venta::query()->create([
            'codigo' => 'VTA-0002', 'cliente_id' => $cliente->id, 'usuario_id' => $this->admin->id,
            'subtotal' => 500, 'descuento' => 0, 'total' => 500, 'estado' => 'pendiente', 'vendida_en' => now(),
        ]);
        Venta::query()->create([
            'codigo' => 'VTA-0003', 'cliente_id' => $cliente->id, 'usuario_id' => $this->admin->id,
            'subtotal' => 900, 'descuento' => 0, 'total' => 900, 'estado' => 'completada', 'vendida_en' => now()->subWeek(),
        ]);

        $respuesta = $this->get('/admin/ventas');

        $respuesta->assertOk();
        $respuesta->assertViewHas('ventasHoy', fn ($total) => abs((float) $total - 100.0) < 0.01);
        $respuesta->assertSee('VTA-0002');
        $respuesta->assertSee('Pendiente');
    }

    public function test_los_clientes_viven_en_su_propia_pestana(): void
    {
        Cliente::query()->create(['codigo' => 'CLI-007', 'tipo' => 'restaurante', 'nombre' => 'Restaurante El Lago', 'documento' => '20234567890']);

        $ventas = $this->get('/admin/ventas');
        $ventas->assertViewHas('tab', 'ventas');
        $ventas->assertSee('Sin ventas registradas');

        $clientes = $this->get('/admin/ventas?tab=clientes');
        $clientes->assertOk();
        $clientes->assertViewHas('tab', 'clientes');
        $clientes->assertSee('Restaurante El Lago');
        $clientes->assertSee('CLI-007');
    }

    public function test_la_pestana_de_reglas_de_calidad_publica_las_referencias_de_la_app(): void
    {
        $respuesta = $this->get('/admin/calidad?tab=reglas');

        $respuesta->assertOk();
        $respuesta->assertViewHas('pestana', 'reglas');
        $respuesta->assertSee('Punto de congelación');
        $respuesta->assertSee('0.0 a 8.0 °C', false);
        $respuesta->assertSee('-0.555 a -0.515 °C', false);
        $respuesta->assertSee('mismas referencias que usa la app de calidad', false);
    }

    public function test_un_valor_fuera_de_rango_se_resalta_en_vez_de_ocultarse(): void
    {
        $proveedor = Proveedor::factory()->create();
        $this->post('/admin/calidad', [
            'proveedor_id' => $proveedor->id, 'fecha' => now('America/Lima')->toDateString(), 'hora' => '07:30',
            'unidad_congelacion' => '°C', 'valores' => ['temperatura' => '9.5', 'grasa' => '3.5'], 'observaciones' => 'Llegó tibia',
        ])->assertSessionHasNoErrors();
        $analisis = AnalisisCalidad::query()->sole();

        $this->get('/admin/calidad')->assertOk()->assertSee('1 fuera de referencia')->assertSee('text-eh-red', false);
        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()
            ->assertSee('9.5 °C', false)->assertSee('Fuera de referencia')->assertSee('Llegó tibia');
    }

    public function test_la_navegacion_de_produccion_marca_la_subpagina_activa(): void
    {
        $rutas = [
            '/admin/produccion' => 'acopio',
            '/admin/produccion/producir' => 'producir',
            '/admin/produccion/historial' => 'historial',
            '/admin/produccion/productos' => 'recetas',
        ];

        foreach ($rutas as $ruta => $clave) {
            $respuesta = $this->get($ruta);

            $respuesta->assertOk();
            // Exactamente una pestaña resaltada (el enlace activo del menú lateral usa otras clases).
            $this->assertSame(
                1,
                substr_count($respuesta->getContent(), 'border-eh-primary text-eh-primary'),
                "La subpágina {$clave} debería marcar exactamente una pestaña activa.",
            );
        }
    }

    public function test_las_pestanas_conservan_el_filtro_de_calidad_al_cambiar(): void
    {
        $respuesta = $this->get('/admin/calidad?resultado=rechazado');

        $respuesta->assertOk();
        $respuesta->assertSee('tab=reglas&amp;resultado=rechazado', false);
    }
}
