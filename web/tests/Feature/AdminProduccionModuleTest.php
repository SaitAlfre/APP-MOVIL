<?php

namespace Tests\Feature;

use App\Application\Produccion\ObtenerSaldoProduccionUseCase;
use App\Domain\Calidad\EstadoCalidad;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use DateTimeImmutable;
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

    /** Queso fresco: 10 L de leche por unidad. */
    private function crearReceta(): Producto
    {
        $this->post('/admin/produccion/productos', [
            'nombre' => 'Queso fresco',
            'presentacion' => '1 kg',
            'unidad_produccion' => 'unidad',
            'litros_por_unidad' => 10,
            'otros_insumos' => 'Cuajo 2 mL, Sal 20 g',
        ])->assertRedirect(route('admin.produccion.productos.index'));

        return Producto::query()->where('nombre', 'Queso fresco')->firstOrFail();
    }

    private function registrarEntregaEvaluada(Vehiculo $vehiculo, float $litros, EstadoCalidad $resultado, string $fecha): Entrega
    {
        // La jornada comparte vehículo con la entrega (igual que en el flujo real, donde la
        // entrega hereda el vehiculo_id de su jornada): necesario para que la merma registrada
        // en Recepción (atada a jornada_id) se agregue bajo el mismo vehículo que el acopio.
        $jornada = Jornada::factory()->create(['vehiculo_id' => $vehiculo->id, 'fecha' => $fecha]);

        $entrega = Entrega::factory()->create([
            'jornada_id' => $jornada->id,
            'vehiculo_id' => $vehiculo->id,
            'litros' => $litros,
            'registrado_en' => $fecha.' 08:00:00',
            'anulada' => false,
        ]);

        ControlCalidad::query()->create([
            'entrega_id' => $entrega->id,
            'usuario_id' => $entrega->usuario_id,
            'resultado' => $resultado->value,
            'temperatura_c' => null,
            'acidez' => null,
            'observaciones' => null,
            'evaluado_en' => now(),
        ]);

        return $entrega;
    }

    // --- Acopio del día ---

    public function test_el_acopio_del_dia_agrupa_litros_aprobados_por_vehiculo_real_y_excluye_rechazados(): void
    {
        $this->comoAdmin();
        $hoy = now()->toDateString();

        $vehiculo1 = Vehiculo::factory()->create(['nombre' => 'Camioneta Norte']);
        $vehiculo2 = Vehiculo::factory()->create(['nombre' => 'Camioneta Sur']);

        $this->registrarEntregaEvaluada($vehiculo1, 60, EstadoCalidad::Aprobado, $hoy);
        $this->registrarEntregaEvaluada($vehiculo2, 40, EstadoCalidad::Observado, $hoy);
        $this->registrarEntregaEvaluada($vehiculo2, 999, EstadoCalidad::Rechazado, $hoy);

        $response = $this->get('/admin/produccion');

        $response->assertOk();
        $response->assertSee('Camioneta Norte');
        $response->assertSee('Camioneta Sur');
        $response->assertSee('100.0 L');
        $response->assertViewHas('acopio', fn ($acopio) => $acopio->litrosTotal() === 100.0);
    }

    // --- Recetas (productos) ---

    public function test_una_receta_requiere_litros_por_unidad_mayor_a_cero(): void
    {
        $this->comoAdmin();

        $response = $this->post('/admin/produccion/productos', [
            'nombre' => 'Queso sin receta',
            'presentacion' => '1 kg',
            'unidad_produccion' => 'unidad',
            'litros_por_unidad' => 0,
        ]);

        $response->assertSessionHasErrors('litros_por_unidad');
        $this->assertDatabaseCount('productos', 0);
    }

    // --- Creación de lotes y saldo compartido del día ---

    /** La recepción vive en su propia sección (ver AdminRecepcionModuleTest); aquí se valida que el lote use el saldo neto de la merma registrada allí y que una asignación bloquee corregirla. */
    public function test_la_recepcion_descuenta_merma_y_el_lote_de_produccion_usa_el_saldo(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create(['placa' => 'ABC-777']);
        $entrega = $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);
        $entrega->jornada->usuario->update(['nombres' => 'Juan Acopiador']);

        $this->get('/admin/produccion')->assertSee('Juan Acopiador')->assertSee('ABC-777');

        $datosRecepcion = ['llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 95, 'motivo_diferencia' => 'Derrame durante el traslado'];
        $this->post(route('admin.recepcion.llegada.store', $entrega->jornada_id), $datosRecepcion)->assertSessionHasNoErrors();
        $this->assertEquals(100, $entrega->refresh()->litros);
        $this->get('/admin/produccion')->assertViewHas('acopio', fn ($acopio) => $acopio->litrosTotal() === 95.0);

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 95])
            ->assertRedirect(route('admin.produccion.historial.index'));
        $lote = LoteProduccion::query()->firstOrFail();
        $this->assertSame('borrador', $lote->estado);
        $this->assertSame(9, $lote->unidades_estimadas);

        // Con una asignación activa ese día, la recepción ya no puede corregirse.
        $this->post(route('admin.recepcion.llegada.store', $entrega->jornada_id), $datosRecepcion)->assertSessionHasErrors('litros_medidos');

        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasNoErrors();
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), ['litros_usados' => 90])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lotes_produccion', [
            'id' => $lote->id, 'estado' => 'finalizado', 'unidades_producidas' => 9, 'litros_usados' => 90, 'litros_sobrantes' => 5,
        ]);
        $this->assertEquals(9.0, (float) $producto->refresh()->existencia);
        $this->assertDatabaseHas('movimientos_producto', ['producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => 9]);
        $this->get('/admin/produccion/historial')->assertSee('Derrame durante el traslado')->assertSee('Juan Acopiador');
    }

    public function test_no_se_puede_asignar_un_lote_con_mas_litros_que_el_saldo_disponible(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $vehiculo = Vehiculo::factory()->create(['activo' => false]);
        $hoy = now()->toDateString();
        $entrega = $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);
        $this->get('/admin/produccion')->assertViewHas('acopio', fn ($acopio) => $acopio->litrosTotal() === 100.0);

        $this->post(route('admin.recepcion.llegada.store', $entrega->jornada_id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 0, 'motivo_diferencia' => 'Pérdida total del contenido',
        ])->assertSessionHasNoErrors();

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 50])
            ->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 0);
    }

    /** Criterio de aceptación de Fase 2: con 500 L disponibles, asignar 300 + 150 deja 50 L disponibles y un tercer intento de 100 L falla. */
    public function test_se_pueden_crear_varios_lotes_de_productos_distintos_el_mismo_dia_repartiendo_el_saldo(): void
    {
        $this->comoAdmin();
        $quesoFresco = $this->crearReceta();
        $yogurt = Producto::factory()->create(['nombre' => 'Yogurt', 'litros_por_unidad' => 5, 'activo' => true]);
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create();
        $this->registrarEntregaEvaluada($vehiculo, 500, EstadoCalidad::Aprobado, $hoy);

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $quesoFresco->id, 'litros_asignados' => 300])
            ->assertSessionHasNoErrors();
        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $yogurt->id, 'litros_asignados' => 150])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('lotes_produccion', 2);

        $saldo = app(ObtenerSaldoProduccionUseCase::class)->ejecutar(new DateTimeImmutable($hoy));
        $this->assertEquals(50.0, $saldo->litrosDisponibles());

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $quesoFresco->id, 'litros_asignados' => 100])
            ->assertSessionHasErrors('litros_asignados');
        $this->assertDatabaseCount('lotes_produccion', 2);
    }

    public function test_cancelar_un_lote_libera_su_reserva_de_litros(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create();
        $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 100])
            ->assertSessionHasNoErrors();
        $lote = LoteProduccion::query()->firstOrFail();

        $this->post(route('admin.produccion.lotes.cancelar', $lote->id), ['motivo' => 'Se dañó el equipo'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lotes_produccion', ['id' => $lote->id, 'estado' => 'cancelado']);

        // Al no contar más contra el saldo, se puede volver a asignar el mismo día.
        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 100])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lotes_produccion', 2);
    }

    public function test_transiciones_de_estado_invalidas_se_rechazan(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create();
        $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 100]);
        $lote = LoteProduccion::query()->firstOrFail();

        // No se puede finalizar directo desde borrador.
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), ['litros_usados' => 90])->assertSessionHasErrors('litros_usados');

        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasNoErrors();
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), ['litros_usados' => 100])->assertSessionHasNoErrors();

        // Un lote finalizado no puede iniciarse ni cancelarse de nuevo.
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasErrors('estado');
        $this->post(route('admin.produccion.lotes.cancelar', $lote->id), ['motivo' => 'tarde'])->assertSessionHasErrors('motivo');
    }

    public function test_finalizar_no_permite_que_usado_mas_merma_de_proceso_superen_lo_asignado(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create();
        $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 100]);
        $lote = LoteProduccion::query()->firstOrFail();
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id));

        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), [
            'litros_usados' => 90, 'litros_merma_proceso' => 20,
        ])->assertSessionHasErrors('litros_usados');

        $this->assertDatabaseHas('lotes_produccion', ['id' => $lote->id, 'estado' => 'en_proceso']);
    }

    // --- Producción ---

    public function test_calcula_las_unidades_estimadas_segun_los_litros_asignados(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $hoy = now()->toDateString();

        $vehiculo = Vehiculo::factory()->create();
        $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);

        $response = $this->get('/admin/produccion/producir?'.http_build_query([
            'fecha' => $hoy,
            'producto_id' => $producto->id,
            'litros_asignados' => 100,
        ]));

        $response->assertOk();
        // 100 L / 10 L por unidad = 10 unidades exactas.
        $response->assertSee('10');
    }

    public function test_no_se_puede_producir_sin_acopios_pendientes(): void
    {
        $this->comoAdmin();

        $response = $this->get('/admin/produccion/producir');

        $response->assertOk();
        $response->assertSee('No hay acopios pendientes de producir');
    }

    public function test_el_historial_muestra_los_kpis_y_el_lote_registrado(): void
    {
        $this->comoAdmin();
        $producto = $this->crearReceta();
        $hoy = now()->toDateString();

        $vehiculo = Vehiculo::factory()->create();
        $this->registrarEntregaEvaluada($vehiculo, 100, EstadoCalidad::Aprobado, $hoy);

        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 100]);
        $lote = LoteProduccion::query()->firstOrFail();
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id));
        $this->post(route('admin.produccion.lotes.finalizar', $lote->id), ['litros_usados' => 100]);

        $response = $this->get('/admin/produccion/historial');

        $response->assertOk();
        $response->assertSee('Queso fresco');
        $this->assertDatabaseCount('lotes_produccion', 1);
        $this->assertSame(10, LoteProduccion::query()->firstOrFail()->unidades_producidas);
    }

    // --- Permisos ---

    public function test_un_proveedor_no_puede_acceder_al_modulo_de_produccion(): void
    {
        $proveedor = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->actingAs($proveedor, 'operador');

        $this->get('/admin/produccion')->assertForbidden();
        $this->get('/admin/produccion/producir')->assertForbidden();
        $this->get('/admin/produccion/historial')->assertForbidden();
        $this->get('/admin/produccion/productos')->assertForbidden();
        $this->post('/admin/produccion/producir', [])->assertForbidden();
    }
}
