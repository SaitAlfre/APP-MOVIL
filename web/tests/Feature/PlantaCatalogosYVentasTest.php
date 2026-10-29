<?php

namespace Tests\Feature;

use App\Application\Movil\AplicarCambioMovilUseCase;
use App\Application\Produccion\GestionRecetas;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Infrastructure\Persistence\Eloquent as M;
use Database\Seeders\PlantaComercialCasosPruebaSeeder;
use Database\Seeders\ProduccionCasosPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantaCatalogosYVentasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(M\Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    private function datosProducto(): array
    {
        return ['nombre' => 'Queso paria fresco', 'presentacion' => '1 kg', 'unidad_produccion' => 'unidad',
            'precio_proveedor' => 15, 'precio_cliente' => 20, 'precio_mayorista' => 17];
    }

    private function cliente(string $tipo = 'minorista'): M\Cliente
    {
        return M\Cliente::create(['codigo' => 'CLI-'.fake()->unique()->numerify('#####'), 'nombre' => 'Distribuidor de Arequipa',
            'tipo' => $tipo, 'documento' => fake()->unique()->numerify('###########'), 'ciudad' => 'Arequipa', 'activo' => true]);
    }

    private function venta(M\Producto $producto, string $tipo, int $compradorId, string $estado = 'completada'): array
    {
        return ['tipo_comprador' => $tipo, $tipo === 'proveedor' ? 'proveedor_id' : 'cliente_id' => $compradorId,
            'estado' => $estado, 'productos' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 0.01]]];
    }

    public function test_admin_no_crea_reclamos_y_proveedores_conservan_su_canal_movil(): void
    {
        $this->get(route('admin.sanciones.index'))->assertOk()->assertDontSee('Nuevo reclamo')->assertDontSee('nuevo-reclamo');
        $this->post('/admin/sanciones/reclamos', [])->assertStatus(405);
        $this->assertDatabaseCount('reclamos_proveedor', 0);
    }

    public function test_producto_exige_receta_y_guarda_tres_precios_opcionales(): void
    {
        $this->post(route('admin.produccion.productos.store'), $this->datosProducto())->assertSessionHasErrors('receta_id');
        $receta = M\Receta::factory()->create(['litros_tanda' => 180, 'rinde_unidades' => 20, 'unidad_base' => 'unidad']);
        $this->get(route('admin.produccion.productos.create'))->assertOk()->assertSee($receta->nombre)->assertDontSee('name="litros_por_unidad"', false)->assertDontSee('data-receta-editor', false);
        $this->post(route('admin.produccion.productos.store'), $this->datosProducto() + ['receta_id' => $receta->id, 'codigo' => 'QP-01', 'stock_minimo' => 5])->assertSessionHasNoErrors();
        $producto = M\Producto::sole();
        $this->assertEquals(9, $producto->litros_por_unidad);
        $this->assertEquals(15, $producto->precio_proveedor);
        $this->assertSame('QP-01', $producto->codigo);
        $this->assertEquals(5, $producto->stock_minimo);
        $this->post(route('admin.produccion.productos.store'), ['nombre' => 'Otro queso'] + $this->datosProducto() + ['receta_id' => $receta->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('productos', 2);
        $this->get(route('admin.produccion.productos.create', ['receta_id' => $receta->id]))->assertOk()->assertSee('usada en 2 productos');
    }

    public function test_admin_tampoco_puede_crear_reclamos_por_sincronizacion_movil(): void
    {
        $this->expectException(CambioMovilRechazadoException::class);
        app(AplicarCambioMovilUseCase::class)->ejecutar(
            auth('operador')->user(), 'reclamo', ['uuid' => 'reclamo-admin-invalido', 'estado' => 'PENDIENTE'],
        );
    }

    public function test_producto_no_admite_modificar_receta_por_el_formulario_del_catalogo(): void
    {
        $this->post(route('admin.produccion.productos.store'), $this->datosProducto() + ['litros_por_unidad' => 9])->assertSessionHasErrors('litros_por_unidad');
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_receta_se_crea_para_producto_existente_y_no_se_duplica(): void
    {
        $producto = M\Producto::factory()->create(['litros_por_unidad' => null, 'existencia' => 6, 'precio_cliente' => 20]);
        $sal = M\Material::create(['nombre' => 'Sal', 'unidad' => 'g', 'existencia' => 500]);
        $datos = ['producto_id' => $producto->id, 'litros_tanda' => 9, 'rinde_unidades' => 1, 'ingredientes' => [['material_id' => $sal->id, 'cantidad' => 20]]];
        $this->post(route('admin.recetas.store'), $datos)->assertSessionHasNoErrors();
        $this->post(route('admin.recetas.store'), $datos)->assertSessionHasErrors('producto_id');
        $this->assertDatabaseCount('productos', 1);
        $this->assertDatabaseCount('receta_ingredientes', 1);
        $this->assertEquals(500, $sal->fresh()->existencia);
        $this->assertEquals(6, $producto->fresh()->existencia);
    }

    public function test_editar_precio_del_producto_conserva_receta_stock_y_lotes(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 6]);
        $receta = M\Receta::factory()->create(['litros_tanda' => 9, 'rinde_unidades' => 1, 'otros_insumos' => 'Paria pasteurizado']);
        app(GestionRecetas::class)->vincular($producto->id, $receta->id);
        $this->put(route('admin.produccion.productos.update', $producto), $this->datosProducto() + ['receta_id' => $receta->id])->assertSessionHasNoErrors();
        $this->assertEquals(9, $producto->fresh()->litros_por_unidad);
        $this->assertSame('Paria pasteurizado', $producto->fresh()->otros_insumos);
        $this->assertEquals(6, $producto->fresh()->existencia);
        $this->put(route('admin.recetas.update', $receta), ['litros_tanda' => 8.5, 'rinde_unidades' => 1, 'nombre' => 'Receta actualizada'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(8.5, $producto->fresh()->litros_por_unidad);
        $this->assertSame('Queso paria fresco', $producto->fresh()->nombre);
        $this->assertEquals(20, $producto->fresh()->precio_cliente);
    }

    public function test_sin_receta_o_con_receta_inactiva_no_crea_produccion_ni_falla_el_calculo(): void
    {
        $producto = M\Producto::factory()->create(['litros_por_unidad' => null]);
        $datos = ['producto_id' => $producto->id, 'fecha' => today()->toDateString(), 'litros_asignados' => 100];
        $this->get(route('admin.produccion.producir.index', $datos))->assertOk()->assertViewHas('unidadesEstimadas', null);
        $this->post(route('admin.produccion.producir.store'), $datos)->assertSessionHasErrors('litros_asignados');
        $producto->update(['litros_por_unidad' => 9, 'receta_activa' => false]);
        $this->post(route('admin.produccion.producir.store'), $datos)->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 0);
    }

    public function test_los_tres_compradores_reciben_su_precio_y_el_servidor_ignora_precios_manipulados(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 20, 'precio_proveedor' => 15, 'precio_cliente' => 20, 'precio_mayorista' => 17]);
        $proveedor = M\Proveedor::factory()->create(['estado' => 'activo']);
        foreach ([['proveedor', $proveedor->id, 15], ['cliente', $this->cliente()->id, 20], ['mayorista', $this->cliente('mayorista')->id, 17]] as [$tipo, $id, $precio]) {
            $this->post(route('admin.ventas.store'), $this->venta($producto, $tipo, $id))->assertSessionHasNoErrors();
            $venta = M\Venta::latest('id')->firstOrFail();
            $this->assertSame($tipo, $venta->tipo_comprador);
            $this->assertEquals(2 * $precio, $venta->total);
            $this->assertEquals($precio, $venta->detalles->sole()->precio_unitario);
        }
        $this->assertEquals(14, $producto->fresh()->existencia);
        $this->assertDatabaseCount('clientes', 2);
        $this->get(route('admin.ventas.index'))->assertOk()->assertSee($proveedor->nombres)->assertSee('Proveedor');
    }

    public function test_rechaza_tipo_de_cliente_falso_comprador_inactivo_y_ambos_identificadores(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 20, 'precio_cliente' => 20, 'precio_mayorista' => 17]);
        $cliente = $this->cliente('mayorista');
        $this->post(route('admin.ventas.store'), $this->venta($producto, 'cliente', $cliente->id))->assertSessionHasErrors('cliente_id');
        $cliente->update(['activo' => false]);
        $this->post(route('admin.ventas.store'), $this->venta($producto, 'mayorista', $cliente->id))->assertSessionHasErrors('cliente_id');
        $proveedor = M\Proveedor::factory()->create(['estado' => 'suspendido']);
        $this->post(route('admin.ventas.store'), $this->venta($producto, 'proveedor', $proveedor->id))->assertSessionHasErrors('proveedor_id');
        $this->post(route('admin.ventas.store'), $this->venta($producto, 'proveedor', $proveedor->id) + ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertEquals(20, $producto->fresh()->existencia);
    }

    public function test_sin_precio_o_producto_inactivo_no_se_registra_venta(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 10]);
        $datos = $this->venta($producto, 'cliente', $this->cliente()->id);
        $this->post(route('admin.ventas.store'), $datos)->assertSessionHasErrors('productos');
        $producto->update(['precio_cliente' => 20, 'activo' => false]);
        $this->post(route('admin.ventas.store'), $datos)->assertSessionHasErrors('productos');
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_venta_pendiente_conserva_precio_y_se_despacha_una_sola_vez(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 10, 'precio_mayorista' => 17]);
        $this->post(route('admin.ventas.store'), $this->venta($producto, 'mayorista', $this->cliente('mayorista')->id, 'pendiente'))->assertSessionHasNoErrors();
        $venta = M\Venta::sole();
        $this->assertEquals(10, $producto->fresh()->existencia);
        $producto->update(['precio_mayorista' => 19]);
        $this->patch(route('admin.ventas.estado', $venta), ['estado' => 'completada'])->assertSessionHasNoErrors();
        $this->patch(route('admin.ventas.estado', $venta), ['estado' => 'completada'])->assertSessionHasErrors('estado');
        $this->assertEquals(8, $producto->fresh()->existencia);
        $this->assertEquals(34, $venta->fresh()->total);
        $this->assertEquals(17, $venta->detalles->sole()->precio_unitario);
        $this->assertDatabaseCount('movimientos_producto', 1);
    }

    public function test_falta_de_stock_revierte_toda_la_venta_y_no_deja_movimientos_parciales(): void
    {
        $primero = M\Producto::factory()->create(['existencia' => 10, 'precio_cliente' => 20]);
        $segundo = M\Producto::factory()->create(['existencia' => 1, 'precio_cliente' => 5]);
        $datos = $this->venta($primero, 'cliente', $this->cliente()->id);
        $datos['productos'][] = ['producto_id' => $segundo->id, 'cantidad' => 2];
        $this->post(route('admin.ventas.store'), $datos)->assertSessionHasErrors('productos');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('movimientos_producto', 0);
        $this->assertEquals(10, $primero->fresh()->existencia);
    }

    public function test_error_en_venta_con_filas_quitadas_conserva_los_productos_restantes(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 0, 'precio_cliente' => 20]);
        $datos = $this->venta($producto, 'cliente', $this->cliente()->id);
        $datos['productos'] = [6 => $datos['productos'][0]];
        $datos['_modal_id'] = 'nueva-venta';
        $this->followingRedirects()->from(route('admin.ventas.index'))->post(route('admin.ventas.store'), $datos)
            ->assertOk()->assertSee('data-restore-open', false)
            ->assertSee('name="productos[6][producto_id]"', false)->assertSee('Cantidad de la fila 7');
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_importes_invalidos_productos_repetidos_y_descuento_excesivo_se_rechazan(): void
    {
        $producto = M\Producto::factory()->create(['existencia' => 10, 'precio_cliente' => 20]);
        $datos = $this->venta($producto, 'cliente', $this->cliente()->id);
        $this->post(route('admin.ventas.store'), $datos + ['descuento' => 41])->assertSessionHasErrors('descuento');
        $datos['productos'][] = $datos['productos'][0];
        $this->post(route('admin.ventas.store'), $datos)->assertSessionHasErrors('productos.0.producto_id');
        $this->put(route('admin.produccion.productos.update', $producto), array_replace($this->datosProducto(), ['precio_cliente' => -1]))->assertSessionHasErrors('precio_cliente');
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_roles_sin_permiso_no_gestionan_catalogo_recetas_ni_ventas(): void
    {
        foreach (['proveedor', 'acopiador', 'calidad'] as $rol) {
            $this->actingAs(M\Usuario::factory()->create(['roles' => [$rol]]), 'operador');
            foreach (['admin.recetas.index', 'admin.produccion.productos.index', 'admin.ventas.index'] as $ruta) {
                $this->get(route($ruta))->assertForbidden();
            }
            foreach (['admin.recetas.store', 'admin.produccion.productos.store', 'admin.ventas.store'] as $ruta) {
                $this->post(route($ruta), [])->assertForbidden();
            }
        }
    }

    public function test_cien_litros_rinden_once_quesos_estimados_y_la_venta_a_arequipa_descuenta_solo_el_producto_real(): void
    {
        $this->seed([ProduccionCasosPruebaSeeder::class, PlantaComercialCasosPruebaSeeder::class]);
        $producto = M\Producto::where('nombre', 'Queso paria fresco DEMO Comercial')->firstOrFail();
        $this->post(route('admin.produccion.producir.store'), [
            'producto_id' => $producto->id, 'fecha' => today()->toDateString(), 'litros_asignados' => 100,
        ])->assertRedirect(route('admin.produccion.historial.index'))->assertSessionHasNoErrors();
        $lote = M\LoteProduccion::sole();
        $this->assertEquals(11, $lote->unidades_estimadas);
        $this->patch(route('admin.produccion.lotes.iniciar', $lote))->assertSessionHasNoErrors();
        $this->post(route('admin.produccion.lotes.finalizar', $lote), [
            'litros_usados' => 99, 'litros_merma_proceso' => 1, 'unidades_producidas' => 10,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(10, $producto->fresh()->existencia);
        $cliente = M\Cliente::where('codigo', 'DEMO-COM-AQP')->firstOrFail();
        $this->post(route('admin.ventas.store'), $this->venta($producto, 'mayorista', $cliente->id))->assertSessionHasNoErrors();
        $this->assertEquals(8, $producto->fresh()->existencia);
        $this->assertEquals(34, M\Venta::sole()->total);
    }

    public function test_datos_demo_de_huata_arequipa_y_lima_son_repetibles_y_no_restauran_stock(): void
    {
        $this->seed(PlantaComercialCasosPruebaSeeder::class);
        $producto = M\Producto::where('nombre', 'Queso paria fresco DEMO Comercial')->firstOrFail();
        $producto->update(['existencia' => 3]);
        $this->seed(PlantaComercialCasosPruebaSeeder::class);
        $this->assertEquals(3, $producto->fresh()->existencia);
        $this->assertDatabaseCount('clientes', 3);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseHas('productos', ['nombre' => 'Yogur natural DEMO sin receta', 'litros_por_unidad' => null]);
    }
}
