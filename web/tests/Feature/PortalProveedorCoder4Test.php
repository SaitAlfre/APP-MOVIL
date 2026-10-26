<?php

namespace Tests\Feature;

use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PortalProveedorCoder4Test extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Proveedor $proveedor;

    private Entrega $entrega;

    private string $token;

    private const FOTO = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aZ1kAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 25));
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $cuenta = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => ['proveedor']]);
        $this->proveedor = Proveedor::factory()->create(['usuario_id' => $cuenta->id, 'capacidad_tacho_l' => 40, 'tachos' => 2]);
        $this->entrega = Entrega::factory()->create([
            'proveedor_id' => $this->proveedor->id, 'litros' => 20, 'tachos' => 1,
            'uuid_movil' => 'entrega-coder4', 'registrado_en' => '2026-10-20 14:00:00',
        ]);
        $this->token = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1234'])->assertOk()->json('token');
    }

    private function reclamo(array $extra = []): ReclamoProveedor
    {
        $this->withToken($this->token)->putJson('/api/movil/cambios/reclamo', array_replace([
            'uuid' => 'reclamo-coder4', 'proveedorCodigo' => $this->proveedor->codigo,
            'entregaUuid' => $this->entrega->uuid_movil, 'litros' => 25,
            'motivo' => 'Diferencia de litros', 'descripcion' => 'El comprobante indica veinticinco litros.',
            'evidencia' => self::FOTO, 'estado' => 'PENDIENTE_ENVIO',
        ], $extra))->assertOk();

        return ReclamoProveedor::query()->where('uuid_movil', $extra['uuid'] ?? 'reclamo-coder4')->sole();
    }

    private function liquidacion(): Liquidacion
    {
        $id = app(GenerarLiquidacionUseCase::class)->ejecutar(
            $this->proveedor->id, new DateTimeImmutable('2026-10-19'), new DateTimeImmutable('2026-10-25'), 1.805,
        )->id;

        return Liquidacion::query()->findOrFail($id);
    }

    private function resolver(ReclamoProveedor $reclamo, array $extra = []): TestResponse
    {
        return $this->actingAs($this->admin, 'operador')->patch(route('admin.sanciones.reclamos.resolver', $reclamo), array_replace([
            'estado' => 'resuelto', 'respuesta' => 'Comprobante revisado; litros corregidos.',
        ], $extra));
    }

    public function test_reenvio_no_duplica_y_la_foto_es_privada(): void
    {
        $reclamo = $this->reclamo();
        $this->reclamo();
        $this->assertDatabaseCount('reclamos_proveedor', 1);
        $this->assertSame(self::FOTO, $reclamo->evidencia);
        $this->get(route('admin.sanciones.reclamos.evidencia', $reclamo))->assertRedirect();
        $this->actingAs($this->admin, 'operador')->get(route('admin.sanciones.reclamos.evidencia', $reclamo))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        auth('operador')->forgetUser();
        $datos = $this->withToken($this->token)->getJson('/api/movil/datos')->assertOk();
        $datos->assertJsonPath('solicitudes.0.evidenciaDisponible', true)->assertJsonMissingPath('solicitudes.0.evidencia');
        $datos->assertJsonPath('solicitudes.0.descripcion', 'El comprobante indica veinticinco litros.');
    }

    public function test_aprobacion_recalcula_pendiente_y_devuelve_respuesta_sin_duplicar_el_ajuste(): void
    {
        $liquidacion = $this->liquidacion();
        $this->assertEquals(36.10, $liquidacion->monto_total);
        $reclamo = $this->reclamo();
        $this->resolver($reclamo)->assertSessionHasNoErrors();
        $this->resolver($reclamo)->assertSessionHasNoErrors();
        $this->assertEquals(25, $this->entrega->fresh()->litros);
        $this->assertEquals(25, $liquidacion->fresh()->litros_totales);
        $this->assertEquals(45.13, $liquidacion->fresh()->monto_total);
        $this->assertEquals(20, $reclamo->fresh()->litros_originales);
        $this->assertDatabaseCount('liquidaciones', 1);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'entrega', 'entidad_id' => $this->entrega->id, 'accion' => 'corregir']);
        auth('operador')->forgetUser();
        $this->withToken($this->token)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonPath('solicitudes.0.estado', 'ATENDIDA')
            ->assertJsonPath('solicitudes.0.respuesta', 'Comprobante revisado; litros corregidos.')
            ->assertJsonPath('pagos.0.total', 45.13);
    }

    public function test_liquidacion_pagada_bloquea_correccion_y_permite_rechazar_con_respuesta(): void
    {
        $liquidacion = $this->liquidacion();
        $liquidacion->update(['estado' => 'pagada', 'pagada_en' => now()]);
        $reclamo = $this->reclamo();
        $this->resolver($reclamo)->assertSessionHasErrors('reclamo');
        $this->assertEquals(20, $this->entrega->fresh()->litros);
        $this->assertEquals(36.10, $liquidacion->fresh()->monto_total);
        $this->assertSame('pendiente', $reclamo->fresh()->estado);
        $this->resolver($reclamo, ['estado' => 'rechazado', 'respuesta' => 'El periodo ya fue pagado.'])->assertSessionHasNoErrors();
        $this->assertSame('rechazado', $reclamo->fresh()->estado);
        $this->assertSame('El periodo ya fue pagado.', $reclamo->fresh()->respuesta);
    }

    public function test_recalcular_litros_conserva_el_descuento_aplicado_una_sola_vez(): void
    {
        $sancion = Sancion::query()->create([
            'proveedor_id' => $this->proveedor->id, 'severidad' => 'media', 'descuento' => 10,
            'motivo' => 'Control observado', 'estado' => 'aprobada',
        ]);
        $liquidacion = $this->liquidacion();
        $this->assertEquals(26.10, $liquidacion->monto_total);
        $reclamo = $this->reclamo();
        $this->resolver($reclamo)->assertSessionHasNoErrors();
        $this->resolver($reclamo)->assertSessionHasNoErrors();
        $this->assertEquals(10, $liquidacion->fresh()->descuento_sanciones);
        $this->assertEquals(35.13, $liquidacion->fresh()->monto_total);
        $this->assertSame($liquidacion->id, $sancion->fresh()->aplicada_liquidacion_id);
    }

    public function test_reclamo_sin_entrega_requiere_vinculo_del_mismo_proveedor(): void
    {
        $reclamo = $this->reclamo(['entregaUuid' => null]);
        $this->assertNull($reclamo->entrega_id);
        $this->resolver($reclamo)->assertSessionHasErrors('reclamo');
        $ajena = Entrega::factory()->create();
        $this->resolver($reclamo, ['entrega_id' => $ajena->id])->assertSessionHasErrors('reclamo');
        $this->resolver($reclamo, ['entrega_id' => $this->entrega->id])->assertSessionHasNoErrors();
        $this->assertSame($this->entrega->id, $reclamo->fresh()->entrega_id);
        $this->assertEquals(25, $this->entrega->fresh()->litros);
    }

    public function test_reclamo_exige_respuesta_y_litros_no_dependen_de_tachos(): void
    {
        $reclamo = $this->reclamo(['litros' => 41]);
        $this->resolver($reclamo, ['respuesta' => ''])->assertSessionHasErrors('respuesta');
        $this->resolver($reclamo)->assertSessionHasNoErrors();
        $this->assertEquals(41, $this->entrega->fresh()->litros);
    }

    public function test_otro_proveedor_no_lee_ni_manipula_un_uuid_ajeno(): void
    {
        $reclamo = $this->reclamo();
        $otraCuenta = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => ['proveedor']]);
        $otro = Proveedor::factory()->create(['usuario_id' => $otraCuenta->id]);
        $token = $this->postJson('/api/movil/sesion', ['username' => $otraCuenta->username, 'pin' => '1234'])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->assertJsonCount(0, 'solicitudes')->assertJsonCount(0, 'pagos');
        $this->withToken($token)->putJson('/api/movil/cambios/reclamo', [
            'uuid' => $reclamo->uuid_movil, 'proveedorCodigo' => $otro->codigo, 'litros' => 25, 'estado' => 'PENDIENTE',
        ])->assertForbidden();
        $this->withToken($this->token)->putJson('/api/movil/cambios/reclamo', [
            'uuid' => $reclamo->uuid_movil, 'litros' => 25, 'estado' => 'ATENDIDA', 'respuesta' => 'Autoaprobado',
        ])->assertForbidden();
    }

    public function test_evidencia_falsa_se_rechaza_sin_guardar_reclamo(): void
    {
        $this->withToken($this->token)->putJson('/api/movil/cambios/reclamo', [
            'uuid' => 'foto-falsa', 'proveedorCodigo' => $this->proveedor->codigo,
            'litros' => 25, 'estado' => 'PENDIENTE', 'evidencia' => 'data:image/png;base64,'.base64_encode('no es una imagen'),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('reclamos_proveedor', 0);
    }

    public function test_periodos_superpuestos_no_generan_doble_pago(): void
    {
        $this->liquidacion();
        $this->actingAs($this->admin, 'operador')->post('/admin/liquidaciones', [
            'proveedor_id' => $this->proveedor->id, 'periodo_inicio' => '2026-10-25', 'periodo_fin' => '2026-10-31', 'precio_litro' => 1.805,
        ])->assertSessionHasErrors('proveedor_id');
        $this->assertDatabaseCount('liquidaciones', 1);
    }

    public function test_el_periodo_financiero_respeta_la_fecha_peruana(): void
    {
        $this->entrega->update(['registrado_en' => '2026-10-26 04:59:59']);
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'litros' => 30, 'registrado_en' => '2026-10-19 04:59:59']);
        $this->assertEquals(20, $this->liquidacion()->litros_totales);
    }

    public function test_pago_se_refleja_en_el_movil_y_no_cambia_su_importe_con_reintentos(): void
    {
        $liquidacion = $this->liquidacion();
        $this->actingAs($this->admin, 'operador')->patch(route('admin.liquidaciones.pagar', $liquidacion->id))->assertSessionHasNoErrors();
        $fechaPago = $liquidacion->fresh()->pagada_en->toDateTimeString();
        $this->patch(route('admin.liquidaciones.pagar', $liquidacion->id))->assertSessionHasErrors('liquidacion');
        $this->assertSame($fechaPago, $liquidacion->fresh()->pagada_en->toDateTimeString());
        auth('operador')->forgetUser();
        $this->withToken($this->token)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonPath('pagos.0.estado', 'PAGADA')->assertJsonPath('pagos.0.total', 36.1);
    }

    public function test_precio_admite_tres_decimales_y_rechaza_precision_que_la_bd_no_guarda(): void
    {
        $this->actingAs($this->admin, 'operador')->post('/admin/liquidaciones', [
            'proveedor_id' => $this->proveedor->id, 'periodo_inicio' => '2026-10-19', 'periodo_fin' => '2026-10-25', 'precio_litro' => 1.8054,
        ])->assertSessionHasErrors('precio_litro');
        $this->assertDatabaseCount('liquidaciones', 0);
        $this->liquidacion();
        $this->assertDatabaseHas('liquidaciones', ['precio_litro' => 1.805, 'monto_total' => 36.1]);
    }

    public function test_comunicado_programado_llega_sin_abrir_previamente_la_web(): void
    {
        foreach (['proveedores', 'calidad'] as $audiencia) {
            Comunicado::query()->create([
                'codigo' => 'PRUEBA-'.$audiencia, 'titulo' => $audiencia, 'contenido' => 'Aviso programado.',
                'audiencia' => $audiencia, 'estado' => 'programado', 'publicar_en' => now()->subMinute(), 'autor_id' => $this->admin->id,
            ]);
        }
        $this->withToken($this->token)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonCount(1, 'comunicados')->assertJsonPath('comunicados.0.titulo', 'proveedores');
    }
}
