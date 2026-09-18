<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Insumo;
use App\Infrastructure\Persistence\Eloquent\LoteInsumo;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProduccionModuleTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    private function crearInsumo(string $nombre, string $unidad): Insumo
    {
        return Insumo::factory()->create(['nombre' => $nombre, 'unidad' => $unidad]);
    }

    /** Crea el producto "Queso fresco" con una receta activa (10 quesos = 100 L leche + 20 ml cuajo + 200 g sal). */
    private function crearProductoConRecetaActiva(): array
    {
        $leche = Insumo::query()->where('nombre', 'Leche')->firstOrFail();
        $cuajo = $this->crearInsumo('Cuajo', 'ml');
        $sal = $this->crearInsumo('Sal', 'kg');

        $this->post('/admin/produccion/productos', [
            'nombre' => 'Queso fresco',
            'presentacion' => '1 kg',
            'unidad_produccion' => 'unidad',
            'contenido_por_unidad' => 1,
            'unidad_contenido' => 'kg',
        ])->assertRedirect(route('admin.produccion.productos.index'));

        $producto = Producto::query()->where('nombre', 'Queso fresco')->firstOrFail();

        $this->post('/admin/produccion/recetas', [
            'producto_id' => $producto->id,
            'nombre' => 'Receta base',
            'rendimiento_base' => 10,
            'rendimiento_unidad' => 'unidad',
            'ingredientes' => [
                ['insumo_id' => $leche->id, 'cantidad' => 100, 'unidad' => 'L'],
                ['insumo_id' => $cuajo->id, 'cantidad' => 20, 'unidad' => 'ml'],
                ['insumo_id' => $sal->id, 'cantidad' => 200, 'unidad' => 'g'],
            ],
        ])->assertRedirect(route('admin.produccion.recetas.index', ['producto_id' => $producto->id]));

        $receta = Receta::query()->where('producto_id', $producto->id)->firstOrFail();

        $this->patch("/admin/produccion/recetas/{$receta->id}/activar")
            ->assertRedirect(route('admin.produccion.recetas.index', ['producto_id' => $producto->id]));

        return compact('producto', 'receta', 'leche', 'cuajo', 'sal');
    }

    // --- Inventario: entradas, catálogo, historial ---

    public function test_un_admin_puede_crear_un_insumo_y_registrar_entradas_del_mismo_insumo(): void
    {
        $this->comoAdmin();
        $sal = $this->crearInsumo('Sal', 'kg');

        $this->post('/admin/produccion/inventario/entradas', [
            'insumo_id' => $sal->id,
            'cantidad' => 10,
            'unidad' => 'kg',
            'fecha' => now()->toDateString(),
        ])->assertRedirect(route('admin.produccion.inventario.index'));

        $this->post('/admin/produccion/inventario/entradas', [
            'insumo_id' => $sal->id,
            'cantidad' => 5,
            'unidad' => 'kg',
            'fecha' => now()->toDateString(),
        ])->assertRedirect(route('admin.produccion.inventario.index'));

        // Dos entradas del mismo insumo, no dos insumos "Sal" distintos.
        $this->assertDatabaseCount('insumos', $this->countInsumosIncludingLeche());
        $this->assertDatabaseCount('entradas_insumo', 2);
        $this->assertEquals(15.0, (float) $sal->refresh()->existencia);

        $response = $this->get("/admin/produccion/inventario/insumos/{$sal->id}");
        $response->assertOk();
        $response->assertSee('Sal');
    }

    private function countInsumosIncludingLeche(): int
    {
        return Insumo::query()->count();
    }

    public function test_no_se_puede_registrar_una_entrada_con_cantidad_invalida(): void
    {
        $this->comoAdmin();
        $sal = $this->crearInsumo('Sal', 'kg');

        $response = $this->post('/admin/produccion/inventario/entradas', [
            'insumo_id' => $sal->id,
            'cantidad' => 0,
            'unidad' => 'kg',
            'fecha' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('entradas_insumo', 0);
    }

    public function test_un_admin_puede_registrar_un_ajuste_de_inventario_con_motivo(): void
    {
        $admin = $this->comoAdmin();
        $sal = $this->crearInsumo('Sal', 'kg');
        $sal->update(['existencia' => 10]);

        $this->post("/admin/produccion/inventario/insumos/{$sal->id}/ajuste", [
            'delta' => -2,
            'motivo' => 'Merma por derrame',
        ])->assertRedirect(route('admin.produccion.inventario.insumos.show', $sal->id));

        $this->assertEquals(8.0, (float) $sal->refresh()->existencia);
        $this->assertDatabaseHas('movimientos_insumo', [
            'insumo_id' => $sal->id,
            'tipo' => 'ajuste',
            'motivo' => 'Merma por derrame',
            'usuario_id' => $admin->id,
        ]);
    }

    public function test_no_se_puede_ajustar_inventario_sin_motivo(): void
    {
        $this->comoAdmin();
        $sal = $this->crearInsumo('Sal', 'kg');

        $response = $this->post("/admin/produccion/inventario/insumos/{$sal->id}/ajuste", ['delta' => 5, 'motivo' => '']);

        $response->assertSessionHasErrors('motivo');
    }

    // --- Integración Calidad -> Inventario (leche) ---

    public function test_una_entrega_aprobada_por_calidad_genera_una_sola_entrada_de_leche(): void
    {
        $this->comoAdmin();
        $leche = Insumo::query()->where('nombre', 'Leche')->firstOrFail();
        $entrega = Entrega::factory()->create(['litros' => 45.5]);

        $this->post('/admin/calidad', [
            'entrega_id' => $entrega->id,
            'resultado' => 'aprobado',
        ])->assertRedirect(route('admin.calidad.index'));

        $this->assertEquals(45.5, (float) $leche->refresh()->existencia);
        $this->assertDatabaseCount('entradas_insumo', 1);
        $this->assertDatabaseHas('entradas_insumo', ['insumo_id' => $leche->id, 'entrega_id' => $entrega->id, 'cantidad' => 45.5]);
    }

    public function test_una_entrega_rechazada_no_incrementa_la_disponibilidad_de_leche(): void
    {
        $this->comoAdmin();
        $leche = Insumo::query()->where('nombre', 'Leche')->firstOrFail();
        $entrega = Entrega::factory()->create(['litros' => 30]);

        $this->post('/admin/calidad', [
            'entrega_id' => $entrega->id,
            'resultado' => 'rechazado',
        ])->assertRedirect(route('admin.calidad.index'));

        $this->assertEquals(0.0, (float) $leche->refresh()->existencia);
        $this->assertDatabaseCount('entradas_insumo', 0);
    }

    public function test_la_leche_sin_evaluar_por_calidad_no_esta_disponible_para_fabricar(): void
    {
        $this->comoAdmin();
        $leche = Insumo::query()->where('nombre', 'Leche')->firstOrFail();
        Entrega::factory()->create(['litros' => 999]);

        $this->assertEquals(0.0, (float) $leche->refresh()->existencia);
        $this->assertDatabaseCount('entradas_insumo', 0);
    }

    // --- Productos y recetas ---

    public function test_un_producto_sin_receta_activa_no_puede_fabricarse(): void
    {
        $this->comoAdmin();

        $this->post('/admin/produccion/productos', [
            'nombre' => 'Yogurt natural',
            'presentacion' => '1 L',
            'unidad_produccion' => 'unidad',
        ])->assertRedirect(route('admin.produccion.productos.index'));

        $producto = Producto::query()->where('nombre', 'Yogurt natural')->firstOrFail();
        $this->assertNull($producto->receta_activa_id);

        $response = $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-001',
            'producto_id' => $producto->id,
            'cantidad_planificada' => 5,
            'fecha_planificada' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('codigo');
        $this->assertDatabaseCount('lotes_produccion', 0);
    }

    public function test_activar_una_receta_archiva_la_version_activa_anterior(): void
    {
        $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();

        $this->post('/admin/produccion/recetas', [
            'producto_id' => $datos['producto']->id,
            'nombre' => 'Receta ajustada',
            'rendimiento_base' => 12,
            'rendimiento_unidad' => 'unidad',
            'ingredientes' => [
                ['insumo_id' => $datos['leche']->id, 'cantidad' => 110, 'unidad' => 'L'],
            ],
        ])->assertRedirect(route('admin.produccion.recetas.index', ['producto_id' => $datos['producto']->id]));

        $nuevaVersion = Receta::query()->where('producto_id', $datos['producto']->id)->where('version', 2)->firstOrFail();

        $this->patch("/admin/produccion/recetas/{$nuevaVersion->id}/activar")
            ->assertRedirect(route('admin.produccion.recetas.index', ['producto_id' => $datos['producto']->id]));

        $this->assertDatabaseHas('recetas', ['id' => $datos['receta']->id, 'estado' => 'archivada']);
        $this->assertDatabaseHas('recetas', ['id' => $nuevaVersion->id, 'estado' => 'activa']);
        $this->assertDatabaseHas('productos', ['id' => $datos['producto']->id, 'receta_activa_id' => $nuevaVersion->id]);
    }

    public function test_editar_una_receta_en_borrador_sin_uso_la_actualiza_en_sitio(): void
    {
        $this->comoAdmin();
        $leche = Insumo::query()->where('nombre', 'Leche')->firstOrFail();

        $this->post('/admin/produccion/productos', [
            'nombre' => 'Mantequilla',
            'presentacion' => '250 g',
            'unidad_produccion' => 'unidad',
        ]);
        $producto = Producto::query()->where('nombre', 'Mantequilla')->firstOrFail();

        $this->post('/admin/produccion/recetas', [
            'producto_id' => $producto->id,
            'nombre' => 'Receta borrador',
            'rendimiento_base' => 5,
            'rendimiento_unidad' => 'unidad',
            'ingredientes' => [['insumo_id' => $leche->id, 'cantidad' => 20, 'unidad' => 'L']],
        ]);
        $receta = Receta::query()->where('producto_id', $producto->id)->firstOrFail();

        $this->put("/admin/produccion/recetas/{$receta->id}", [
            'nombre' => 'Receta borrador editada',
            'rendimiento_base' => 6,
            'rendimiento_unidad' => 'unidad',
            'ingredientes' => [['insumo_id' => $leche->id, 'cantidad' => 25, 'unidad' => 'L']],
        ])->assertRedirect(route('admin.produccion.recetas.index', ['producto_id' => $producto->id]));

        $this->assertDatabaseCount('recetas', 1);
        $this->assertDatabaseHas('recetas', ['id' => $receta->id, 'nombre' => 'Receta borrador editada', 'version' => 1]);
    }

    public function test_editar_una_receta_ya_usada_en_un_lote_crea_una_nueva_version_sin_alterar_el_lote_historico(): void
    {
        $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();
        $this->abastecerParaVeinteQuesos($datos);

        $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-HIST',
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 10,
            'fecha_planificada' => now()->toDateString(),
        ]);
        $lote = LoteProduccion::query()->where('codigo', 'LP-HIST')->firstOrFail();
        $this->assertEquals($datos['receta']->id, $lote->receta_id);

        // La receta ya fue usada por este lote: editarla debe crear una v2, sin tocar la v1.
        $this->put("/admin/produccion/recetas/{$datos['receta']->id}", [
            'nombre' => 'Receta base modificada',
            'rendimiento_base' => 10,
            'rendimiento_unidad' => 'unidad',
            'ingredientes' => [['insumo_id' => $datos['leche']->id, 'cantidad' => 999, 'unidad' => 'L']],
        ])->assertRedirect(route('admin.produccion.recetas.index', ['producto_id' => $datos['producto']->id]));

        $this->assertDatabaseHas('recetas', ['id' => $datos['receta']->id, 'nombre' => 'Receta base', 'version' => 1]);
        $this->assertDatabaseHas('recetas', ['producto_id' => $datos['producto']->id, 'version' => 2, 'nombre' => 'Receta base modificada']);
        $lote->refresh();
        $this->assertEquals($datos['receta']->id, $lote->receta_id);
    }

    // --- Flujo completo de producción ---

    private function abastecerParaVeinteQuesos(array $datos, bool $cuajoSuficiente = true): void
    {
        // Leche llega vía Calidad (200 L exactos para 20 quesos).
        $entrega = Entrega::factory()->create(['litros' => 200]);
        $this->post('/admin/calidad', ['entrega_id' => $entrega->id, 'resultado' => 'aprobado']);

        $this->post('/admin/produccion/inventario/entradas', [
            'insumo_id' => $datos['cuajo']->id,
            'cantidad' => $cuajoSuficiente ? 50 : 30,
            'unidad' => 'ml',
            'fecha' => now()->toDateString(),
        ]);

        $this->post('/admin/produccion/inventario/entradas', [
            'insumo_id' => $datos['sal']->id,
            'cantidad' => 1,
            'unidad' => 'kg',
            'fecha' => now()->toDateString(),
        ]);
    }

    public function test_calcula_la_necesidad_de_materiales_segun_la_receta_y_la_cantidad_planificada(): void
    {
        $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();

        $response = $this->get('/admin/produccion/lotes/nuevo?'.http_build_query([
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 20,
        ]));

        $response->assertOk();
        // 100 L leche / 10 * 20 = 200 L; 20 ml cuajo / 10 * 20 = 40 ml; 200 g sal / 10 * 20 = 400 g = 0.4 kg.
        $response->assertSee('200.000');
        $response->assertSee('40.000');
        $response->assertSee('0.400');
    }

    public function test_no_se_puede_iniciar_un_lote_sin_disponibilidad_suficiente_y_no_reserva_nada(): void
    {
        $admin = $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();
        $this->abastecerParaVeinteQuesos($datos, cuajoSuficiente: false); // solo 30 ml de cuajo, faltan 40

        $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-002',
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 20,
            'fecha_planificada' => now()->toDateString(),
        ]);
        $lote = LoteProduccion::query()->where('codigo', 'LP-002')->firstOrFail();

        $response = $this->patch("/admin/produccion/lotes/{$lote->id}/iniciar");
        $response->assertSessionHasErrors('lote');

        $lote->refresh();
        $this->assertSame('borrador', $lote->estado->value);
        // Ningún insumo quedó parcialmente reservado (todo o nada).
        $this->assertEquals(0.0, (float) $datos['leche']->refresh()->reservado);
        $this->assertEquals(0.0, (float) $datos['sal']->refresh()->reservado);
        $this->assertEquals(0.0, (float) $datos['cuajo']->refresh()->reservado);
    }

    public function test_flujo_completo_iniciar_consumir_y_finalizar_con_cantidad_distinta_a_la_planificada(): void
    {
        $admin = $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();
        $this->abastecerParaVeinteQuesos($datos);

        $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-003',
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 20,
            'fecha_planificada' => now()->toDateString(),
            'observaciones' => 'Lote de prueba',
        ])->assertRedirect();

        $lote = LoteProduccion::query()->where('codigo', 'LP-003')->firstOrFail();

        // Iniciar reserva los materiales.
        $this->patch("/admin/produccion/lotes/{$lote->id}/iniciar")->assertRedirect(route('admin.produccion.lotes.show', $lote->id));
        $lote->refresh();
        $this->assertSame('en_proceso', $lote->estado->value);
        $this->assertEquals(200.0, (float) $datos['leche']->refresh()->reservado);
        $this->assertEquals(40.0, (float) $datos['cuajo']->refresh()->reservado);
        $this->assertEquals(0.4, round((float) $datos['sal']->refresh()->reservado, 3));

        // Reintentar iniciar el mismo lote no debe duplicar la reserva.
        $this->patch("/admin/produccion/lotes/{$lote->id}/iniciar")->assertSessionHasErrors('lote');
        $this->assertEquals(200.0, (float) $datos['leche']->refresh()->reservado);

        // Consumo parcial: leche 150 de 200, cuajo 40 de 40, sal aún no informada.
        $this->post("/admin/produccion/lotes/{$lote->id}/consumo", [
            'consumo' => [$datos['leche']->id => 150, $datos['cuajo']->id => 40],
        ])->assertRedirect(route('admin.produccion.lotes.show', $lote->id));

        $this->assertEquals(50.0, (float) $datos['leche']->refresh()->existencia); // 200 - 150
        $this->assertEquals(50.0, (float) $datos['leche']->refresh()->reservado); // 200 - 150
        $this->assertEquals(10.0, (float) $datos['cuajo']->refresh()->existencia); // 50 - 40
        $this->assertEquals(0.0, (float) $datos['cuajo']->refresh()->reservado);

        // Reenviar el mismo consumo (reintento de red) no debe duplicar el descuento.
        $this->post("/admin/produccion/lotes/{$lote->id}/consumo", [
            'consumo' => [$datos['leche']->id => 150],
        ]);
        $this->assertEquals(50.0, (float) $datos['leche']->refresh()->existencia);

        // Finalizar con 19 en vez de 20: sólo entran 19 al inventario y sobra de leche se libera.
        $this->patch("/admin/produccion/lotes/{$lote->id}/finalizar", [
            'cantidad_obtenida' => 19,
            'consumo' => [$datos['sal']->id => 0.4],
        ])->assertRedirect(route('admin.produccion.lotes.show', $lote->id));

        $lote->refresh();
        $this->assertSame('finalizado', $lote->estado->value);
        $this->assertEquals(19.0, (float) $lote->cantidad_obtenida);
        $this->assertEquals(19.0, (float) $datos['producto']->refresh()->existencia);
        $this->assertDatabaseHas('movimientos_producto', ['producto_id' => $datos['producto']->id, 'tipo' => 'produccion', 'cantidad' => 19]);

        // La leche reservada y no consumida (50 L) se liberó, no se devolvió a existencia extra.
        $this->assertEquals(0.0, (float) $datos['leche']->refresh()->reservado);
        $this->assertEquals(50.0, (float) $datos['leche']->refresh()->existencia);
        $this->assertEquals(0.6, round((float) $datos['sal']->refresh()->existencia, 3));
    }

    public function test_no_se_puede_finalizar_sin_informar_el_consumo_de_todos_los_insumos(): void
    {
        $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();
        $this->abastecerParaVeinteQuesos($datos);

        $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-004',
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 10,
            'fecha_planificada' => now()->toDateString(),
        ]);
        $lote = LoteProduccion::query()->where('codigo', 'LP-004')->firstOrFail();
        $this->patch("/admin/produccion/lotes/{$lote->id}/iniciar");

        $response = $this->patch("/admin/produccion/lotes/{$lote->id}/finalizar", ['cantidad_obtenida' => 10]);

        $response->assertSessionHasErrors('cantidad_obtenida');
        $this->assertSame('en_proceso', $lote->refresh()->estado->value);
        $this->assertDatabaseCount('movimientos_producto', 0);
    }

    public function test_cancelar_un_lote_en_proceso_libera_solo_lo_no_consumido_y_conserva_el_historial(): void
    {
        $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();
        $this->abastecerParaVeinteQuesos($datos);

        $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-005',
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 10,
            'fecha_planificada' => now()->toDateString(),
        ]);
        $lote = LoteProduccion::query()->where('codigo', 'LP-005')->firstOrFail();
        $this->patch("/admin/produccion/lotes/{$lote->id}/iniciar");

        // Consume parte de la leche antes de cancelar.
        $this->post("/admin/produccion/lotes/{$lote->id}/consumo", ['consumo' => [$datos['leche']->id => 60]]);

        $this->patch("/admin/produccion/lotes/{$lote->id}/cancelar", ['motivo' => 'Falla en el equipo'])
            ->assertRedirect(route('admin.produccion.lotes.show', $lote->id));

        $lote->refresh();
        $this->assertSame('cancelado', $lote->estado->value);
        $this->assertSame('Falla en el equipo', $lote->motivo_cancelacion);

        // Reservado 100 L, consumido 60 L -> se liberan sólo los 40 L restantes.
        $this->assertEquals(0.0, (float) $datos['leche']->refresh()->reservado);
        $this->assertEquals(140.0, (float) $datos['leche']->refresh()->existencia); // 200 - 60 consumidos

        $detalle = LoteInsumo::query()->where('lote_produccion_id', $lote->id)->where('insumo_id', $datos['leche']->id)->firstOrFail();
        $this->assertEquals(60.0, (float) $detalle->cantidad_consumida);
    }

    public function test_no_se_puede_cancelar_un_lote_ya_finalizado(): void
    {
        $this->comoAdmin();
        $datos = $this->crearProductoConRecetaActiva();
        $this->abastecerParaVeinteQuesos($datos);

        $this->post('/admin/produccion/lotes', [
            'codigo' => 'LP-006',
            'producto_id' => $datos['producto']->id,
            'cantidad_planificada' => 5,
            'fecha_planificada' => now()->toDateString(),
        ]);
        $lote = LoteProduccion::query()->where('codigo', 'LP-006')->firstOrFail();
        $this->patch("/admin/produccion/lotes/{$lote->id}/iniciar");
        $this->patch("/admin/produccion/lotes/{$lote->id}/finalizar", [
            'cantidad_obtenida' => 5,
            'consumo' => [
                $datos['leche']->id => 50,
                $datos['cuajo']->id => 10,
                $datos['sal']->id => 0.1,
            ],
        ]);

        $response = $this->patch("/admin/produccion/lotes/{$lote->id}/cancelar", ['motivo' => 'Ya no aplica']);

        $response->assertSessionHasErrors('motivo');
        $this->assertSame('finalizado', $lote->refresh()->estado->value);
    }

    // --- Permisos ---

    public function test_un_proveedor_no_puede_acceder_al_modulo_de_produccion(): void
    {
        $proveedor = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->actingAs($proveedor, 'operador');

        $this->get('/admin/produccion')->assertForbidden();
        $this->get('/admin/produccion/inventario')->assertForbidden();
        $this->get('/admin/produccion/productos')->assertForbidden();
        $this->get('/admin/produccion/recetas')->assertForbidden();
        $this->get('/admin/produccion/lotes')->assertForbidden();
    }
}
