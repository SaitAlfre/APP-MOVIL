<?php

namespace Tests\Feature;

use App\Application\Calidad\GuardarAnalisisCalidadUseCase;
use App\Application\Movil\ExportarDatosMovilQuery;
use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Proveedores\EstadoProveedor;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use Tests\TestCase;

class InfraccionesCalidadTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $tecnico;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(18, 0));
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->tecnico = Usuario::factory()->create(['roles' => ['calidad']]);
        $this->proveedor = Proveedor::factory()->create(['nombres' => 'Proveedor Ficticio de Calidad']);
        Configuracion::query()->updateOrCreate(['clave' => 'inicio_semana'], ['valor' => 'lunes']);
        $this->actingAs($this->admin, 'operador');
    }

    /** @param array<string, mixed> $extra */
    private function analisis(array $extra = []): AnalisisCalidad
    {
        $uuid = (string) Str::uuid();

        return AnalisisCalidad::query()->create([
            'uuid' => $uuid, 'codigo_muestra' => 'AN-'.substr($uuid, 0, 12),
            'proveedor_id' => $this->proveedor->id, 'usuario_id' => $this->tecnico->id,
            'estado' => EstadoAnalisis::Rechazado, 'agua_anadida' => 3.0,
            'alertas' => ['Lectura de agua pendiente de revisión técnica'], 'visita' => [],
            'registrado_en' => '2026-10-29 15:00:00', ...$extra,
        ]);
    }

    private function registrar(AnalisisCalidad $analisis): InfraccionCalidad
    {
        $this->post(route('admin.calidad.incidencias.store', $analisis->uuid), [
            'motivo' => 'Control sorpresa con indicios de adulteración; evidencia revisable.',
        ])->assertSessionHasNoErrors()->assertRedirect();

        return InfraccionCalidad::query()->where('analisis_calidad_id', $analisis->id)->sole();
    }

    /** @param array<string, mixed> $extra */
    private function resolver(InfraccionCalidad $incidencia, array $extra = []): TestResponse
    {
        return $this->post(route('admin.calidad.incidencias.resolver', $incidencia->id), [
            'decision' => 'confirmar', 'descuento_litro' => '0.30',
            'respuesta' => 'Adulteración confirmada tras revisar la muestra y su comprobante.', ...$extra,
        ]);
    }

    public function test_el_tecnico_abre_un_caso_sin_castigar_ni_publicar_y_el_reenvio_no_duplica(): void
    {
        $analisis = $this->analisis();
        $this->actingAs($this->tecnico, 'operador');
        $caso = $this->registrar($analisis);
        $this->registrar($analisis);

        $this->assertSame('pendiente', $caso->estado);
        $this->assertSame($this->tecnico->id, $caso->registrado_por);
        $this->assertDatabaseCount('infracciones_calidad', 1);
        $this->assertDatabaseCount('sanciones', 0);
        $this->assertDatabaseCount('comunicados', 0);
        $this->assertSame(EstadoProveedor::Activo, $this->proveedor->fresh()->estado);
    }

    public function test_el_tecnico_no_puede_confirmar_ni_descartar_casos_aunque_gestione_calidad(): void
    {
        $caso = $this->registrar($this->analisis());
        $this->actingAs($this->tecnico, 'operador');

        $this->resolver($caso)->assertForbidden();
        $this->resolver($caso, ['decision' => 'descartar'])->assertForbidden();
        $this->assertSame('pendiente', $caso->fresh()->estado);
        $this->assertDatabaseCount('comunicados', 0);
    }

    public function test_un_resultado_aprobado_no_se_convierte_en_infraccion(): void
    {
        $analisis = $this->analisis(['estado' => EstadoAnalisis::Aprobado, 'agua_anadida' => 0]);
        $this->post(route('admin.calidad.incidencias.store', $analisis->uuid), [
            'motivo' => 'Intento de sancionar una muestra aprobada sin incidencia técnica.',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('infracciones_calidad', 0);
    }

    public function test_confirmar_exige_motivo_y_descuento_de_veinte_a_cincuenta_centimos(): void
    {
        $caso = $this->registrar($this->analisis());

        foreach (['0.19', '0.51', '-0.20', '0.205', 'texto'] as $descuento) {
            $this->resolver($caso, ['descuento_litro' => $descuento])->assertSessionHasErrors('descuento_litro');
            $this->assertSame('pendiente', $caso->fresh()->estado);
        }
        $this->resolver($caso, ['respuesta' => ''])->assertSessionHasErrors('respuesta');
        $this->assertDatabaseCount('comunicados', 0);
    }

    public function test_confirmar_guarda_semana_numero_y_dos_avisos_con_nombre_sin_duplicar_al_reintentar(): void
    {
        $caso = $this->registrar($this->analisis());
        $this->resolver($caso, ['descuento_litro' => '0.20'])->assertSessionHasNoErrors();
        $this->resolver($caso, ['descuento_litro' => '0.20'])->assertSessionHasNoErrors();

        $caso->refresh();
        $this->assertSame('confirmada', $caso->estado);
        $this->assertEquals(0.20, $caso->descuento_litro);
        $this->assertEquals(1, $caso->numero_infraccion);
        $this->assertSame('2026-10-26', $caso->semana_inicio->toDateString());
        $this->assertSame('2026-11-01', $caso->semana_fin->toDateString());
        $this->assertSame($this->admin->id, $caso->confirmado_por);
        $this->assertDatabaseCount('comunicados', 2);
        $this->assertDatabaseHas('comunicados', ['infraccion_calidad_id' => $caso->id,
            'proveedor_id' => $this->proveedor->id, 'zona_id' => null, 'estado' => 'publicado']);
        $this->assertDatabaseHas('comunicados', ['infraccion_calidad_id' => $caso->id,
            'proveedor_id' => null, 'zona_id' => $this->proveedor->zona_id, 'estado' => 'publicado']);
        foreach (Comunicado::query()->get() as $aviso) {
            $this->assertStringContainsString($this->proveedor->nombres, $aviso->contenido);
        }
        $this->assertDatabaseHas('auditorias', [
            'entidad' => 'infraccion_calidad', 'entidad_id' => $caso->id,
            'usuario_id' => $this->admin->id, 'accion' => 'actualizar',
        ]);
    }

    public function test_la_semana_respeta_el_inicio_configurado_y_la_fecha_peruana_de_la_muestra(): void
    {
        Configuracion::query()->whereKey('inicio_semana')->update(['valor' => 'jueves']);
        $caso = $this->registrar($this->analisis(['registrado_en' => '2026-10-29 02:00:00']));
        $this->resolver($caso)->assertSessionHasNoErrors();

        $this->assertSame('2026-10-22', $caso->fresh()->semana_inicio->toDateString());
        $this->assertSame('2026-10-28', $caso->fresh()->semana_fin->toDateString());
    }

    public function test_descartar_no_cuenta_como_infraccion_ni_genera_avisos_y_no_admite_confirmacion_posterior(): void
    {
        $caso = $this->registrar($this->analisis());
        $this->resolver($caso, ['decision' => 'descartar', 'descuento_litro' => null,
            'respuesta' => 'La repetición de la prueba descartó la presunta adulteración.'])->assertSessionHasNoErrors();

        $this->assertSame('descartada', $caso->fresh()->estado);
        $this->assertNull($caso->fresh()->numero_infraccion);
        $this->assertDatabaseCount('comunicados', 0);
        $this->resolver($caso)->assertSessionHasErrors();
        $siguiente = $this->registrar($this->analisis());
        $this->resolver($siguiente)->assertSessionHasNoErrors();
        $this->assertEquals(1, $siguiente->fresh()->numero_infraccion);
    }

    public function test_la_tercera_infraccion_historica_retira_la_ficha_y_revoca_el_acceso_vinculado(): void
    {
        $cuenta = Usuario::factory()->create(['roles' => ['proveedor'], 'pin_hash' => '2468']);
        $this->proveedor->update(['usuario_id' => $cuenta->id, 'dni' => $cuenta->dni]);
        $sesion = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '2468'])->assertOk()->json();

        foreach (['2023-01-17 15:00:00', '2025-06-17 15:00:00', '2026-10-29 15:00:00'] as $indice => $fecha) {
            $caso = $this->registrar($this->analisis(['registrado_en' => $fecha]));
            $this->resolver($caso, ['descuento_litro' => '0.50'])->assertSessionHasNoErrors();
            $this->assertEquals($indice + 1, $caso->fresh()->numero_infraccion);
            $this->assertSame($indice < 2 ? EstadoProveedor::Activo : EstadoProveedor::Retirado, $this->proveedor->fresh()->estado);
        }

        $this->assertTrue($cuenta->fresh()->bloqueado_manualmente);
        $this->withToken($sesion['token'])->getJson('/api/movil/datos')->assertUnauthorized();
        $this->assertDatabaseCount('infracciones_calidad', 3);
        $this->assertDatabaseCount('comunicados', 6);
    }

    public function test_los_avisos_individuales_y_de_zona_no_llegan_a_proveedores_ajenos(): void
    {
        $cuentaPropia = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->proveedor->update(['usuario_id' => $cuentaPropia->id]);
        $cuentaVecina = Usuario::factory()->create(['roles' => ['proveedor']]);
        Proveedor::factory()->create(['usuario_id' => $cuentaVecina->id, 'zona_id' => $this->proveedor->zona_id]);
        $cuentaAjena = Usuario::factory()->create(['roles' => ['proveedor']]);
        Proveedor::factory()->create(['usuario_id' => $cuentaAjena->id]);
        $caso = $this->registrar($this->analisis());
        $this->resolver($caso)->assertSessionHasNoErrors();
        $individual = Comunicado::query()->where('proveedor_id', $this->proveedor->id)->sole();
        $zonal = Comunicado::query()->where('zona_id', $this->proveedor->zona_id)->sole();
        $exportar = app(ExportarDatosMovilQuery::class);

        $propios = collect($exportar->ejecutar($cuentaPropia)['comunicados'])->pluck('id');
        $vecinos = collect($exportar->ejecutar($cuentaVecina)['comunicados'])->pluck('id');
        $ajenos = collect($exportar->ejecutar($cuentaAjena)['comunicados'])->pluck('id');
        $tecnico = collect($exportar->ejecutar($this->tecnico)['comunicados'])->pluck('id');

        $this->assertTrue($propios->contains($individual->id));
        $this->assertTrue($propios->contains($zonal->id));
        $this->assertFalse($vecinos->contains($individual->id));
        $this->assertTrue($vecinos->contains($zonal->id));
        $this->assertCount(0, $ajenos);
        $this->assertCount(0, $tecnico);
    }

    public function test_una_muestra_futura_no_se_confirma_ni_genera_consecuencias(): void
    {
        $caso = $this->registrar($this->analisis(['registrado_en' => now()->addDay()]));
        $this->resolver($caso)->assertSessionHasErrors();

        $this->assertSame('pendiente', $caso->fresh()->estado);
        $this->assertDatabaseCount('comunicados', 0);
    }

    public function test_el_aviso_zonal_conserva_los_destinatarios_del_momento_de_publicacion(): void
    {
        $cuentaVecina = Usuario::factory()->create(['roles' => ['proveedor']]);
        $vecino = Proveedor::factory()->create(['usuario_id' => $cuentaVecina->id, 'zona_id' => $this->proveedor->zona_id]);
        $cuentaAjena = Usuario::factory()->create(['roles' => ['proveedor']]);
        $ajeno = Proveedor::factory()->create(['usuario_id' => $cuentaAjena->id]);
        $otraZona = $ajeno->zona_id;
        $caso = $this->registrar($this->analisis());
        $this->resolver($caso)->assertSessionHasNoErrors();
        $aviso = Comunicado::query()->where('zona_id', $this->proveedor->zona_id)->sole();

        $vecino->update(['zona_id' => $otraZona]);
        $ajeno->update(['zona_id' => $this->proveedor->zona_id]);
        $exportar = app(ExportarDatosMovilQuery::class);
        $avisosVecino = collect($exportar->ejecutar($cuentaVecina)['comunicados'])->pluck('id');
        $avisosAjeno = collect($exportar->ejecutar($cuentaAjena)['comunicados'])->pluck('id');

        $this->assertTrue($avisosVecino->contains($aviso->id));
        $this->assertFalse($avisosAjeno->contains($aviso->id));
    }

    public function test_una_liquidacion_pagada_de_la_semana_bloquea_la_confirmacion_sin_alterarla(): void
    {
        $pagada = Liquidacion::query()->create([
            'proveedor_id' => $this->proveedor->id, 'periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-10-28',
            'litros_totales' => 100, 'precio_litro' => 1.8, 'monto_total' => 180,
            'estado' => 'pagada', 'generada_en' => now()->subDay(), 'pagada_en' => now()->subDay(),
        ]);
        $caso = $this->registrar($this->analisis());
        $this->resolver($caso)->assertSessionHasErrors();

        $this->assertSame('pendiente', $caso->fresh()->estado);
        $this->assertEquals(180, $pagada->fresh()->monto_total);
        $this->assertDatabaseCount('comunicados', 0);
    }

    public function test_guardar_un_analisis_observado_ya_no_inventa_un_descuento_fijo(): void
    {
        Entrega::factory()->create(['proveedor_id' => $this->proveedor->id, 'registrado_en' => now()]);
        $this->post('/admin/calidad', [
            'proveedor_id' => $this->proveedor->id, 'fecha' => now('America/Lima')->toDateString(),
            'hora' => '12:00', 'unidad_congelacion' => '°C', 'valores' => ['grasa' => '2.1'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('analisis_calidad', 1);
        $this->assertDatabaseCount('sanciones', 0);
        $this->assertDatabaseCount('comunicados', 0);
    }

    public function test_la_evidencia_de_un_caso_pendiente_o_confirmado_no_se_sobrescribe_desde_sincronizacion(): void
    {
        foreach ([false, true] as $confirmar) {
            $analisis = $this->analisis();
            $caso = $this->registrar($analisis);
            if ($confirmar) {
                $this->resolver($caso)->assertSessionHasNoErrors();
            }
            $datos = [
                'uuid' => $analisis->uuid, 'proveedor_id' => $analisis->proveedor_id,
                'registrado_en' => CarbonImmutable::instance($analisis->registrado_en),
                'valores' => ['agua' => 0.0], 'estado' => EstadoAnalisis::Aprobado,
                'alertas' => [], 'origen' => 'MANUAL',
            ];

            try {
                app(GuardarAnalisisCalidadUseCase::class)->ejecutar($datos, $this->tecnico);
                $this->fail('No debe ser posible cambiar la evidencia de un caso de calidad.');
            } catch (InvalidArgumentException $error) {
                $this->assertStringContainsString('no puede sobrescribirse', $error->getMessage());
            }

            $this->assertSame(EstadoAnalisis::Rechazado, $analisis->fresh()->estado);
            $this->assertSame(3.0, $analisis->fresh()->agua_anadida);
            $this->assertSame($confirmar ? 'confirmada' : 'pendiente', $caso->fresh()->estado);
        }
    }

    public function test_un_reenvio_identico_de_la_evidencia_se_acepta_sin_duplicar_el_caso_ni_sus_avisos(): void
    {
        $analisis = $this->analisis();
        $caso = $this->registrar($analisis);
        $this->resolver($caso)->assertSessionHasNoErrors();
        $datos = [
            'uuid' => $analisis->uuid, 'proveedor_id' => $analisis->proveedor_id,
            'registrado_en' => CarbonImmutable::instance($analisis->registrado_en),
            'valores' => ['agua' => 3.0], 'estado' => EstadoAnalisis::Rechazado,
            'alertas' => $analisis->alertas, 'origen' => 'MANUAL',
        ];

        $reenvio = app(GuardarAnalisisCalidadUseCase::class)->ejecutar($datos, $this->tecnico);

        $this->assertSame($analisis->id, $reenvio->id);
        $this->assertDatabaseCount('analisis_calidad', 1);
        $this->assertDatabaseCount('infracciones_calidad', 1);
        $this->assertDatabaseCount('comunicados', 2);
        $this->assertEquals(1, $caso->fresh()->numero_infraccion);
    }

    public function test_cambiar_el_inicio_de_semana_no_mueve_el_periodo_de_un_caso_ya_reportado(): void
    {
        $caso = $this->registrar($this->analisis());
        $this->assertSame('2026-10-26', $caso->semana_inicio->toDateString());
        $this->assertSame('2026-11-01', $caso->semana_fin->toDateString());
        Configuracion::query()->whereKey('inicio_semana')->update(['valor' => 'jueves']);

        $this->resolver($caso)->assertSessionHasNoErrors();

        $this->assertSame('2026-10-26', $caso->fresh()->semana_inicio->toDateString());
        $this->assertSame('2026-11-01', $caso->fresh()->semana_fin->toDateString());
    }

    public function test_los_avisos_de_una_infraccion_no_se_editan_ni_se_retiraran_desde_web_o_movil(): void
    {
        $caso = $this->registrar($this->analisis());
        $this->resolver($caso)->assertSessionHasNoErrors();
        $token = $this->postJson('/api/movil/sesion', ['username' => $this->admin->username, 'pin' => '1234'])
            ->assertOk()->json('token');

        foreach (Comunicado::query()->get() as $aviso) {
            $contenido = $aviso->contenido;
            $this->put(route('admin.comunicados.update', $aviso), [
                'titulo' => 'Resultado cambiado', 'contenido' => 'Contenido cambiado desde el panel.',
                'audiencia' => 'todos', 'estado' => 'borrador',
            ])->assertStatus(409);

            foreach ([false, true] as $eliminado) {
                $this->withToken($token)->putJson('/api/movil/cambios/comunicado', [
                    'uuid' => 'aviso-calidad-'.$aviso->id, 'servidorId' => $aviso->id,
                    'mensaje' => $eliminado ? null : 'Contenido cambiado desde el celular.',
                    'publicadoEn' => now()->getTimestampMs(), 'eliminado' => $eliminado,
                ])->assertUnprocessable();
            }

            $this->assertSame($contenido, $aviso->fresh()->contenido);
            $this->assertSame('publicado', $aviso->fresh()->estado);
            $this->assertSame('proveedores', $aviso->fresh()->audiencia);
        }
    }

    public function test_las_pestanas_y_el_detalle_se_renderizan_en_cada_estado_del_caso(): void
    {
        $analisis = $this->analisis();
        foreach (['analisis', 'incidencias', 'reglas'] as $tab) {
            $this->get(route('admin.calidad.index', ['tab' => $tab]))->assertOk();
        }
        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()->assertSee('Reportar posible adulteración');
        $caso = $this->registrar($analisis);
        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()->assertSee('Confirmar infracción y castigo');

        $this->actingAs($this->tecnico, 'operador');
        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()->assertDontSee('Confirmar infracción y castigo');
        $this->actingAs($this->admin, 'operador');
        $this->resolver($caso)->assertSessionHasNoErrors();
        $this->get(route('admin.calidad.show', $analisis->uuid))->assertOk()->assertSee('0.30');

        $otro = $this->analisis();
        $descartado = $this->registrar($otro);
        $this->resolver($descartado, ['decision' => 'descartar', 'descuento_litro' => null])->assertSessionHasNoErrors();
        $this->get(route('admin.calidad.show', $otro->uuid))->assertOk()->assertSee('Este caso no cuenta como infracción');

        $aprobado = $this->analisis(['estado' => EstadoAnalisis::Aprobado, 'agua_anadida' => 0]);
        $this->get(route('admin.calidad.show', $aprobado->uuid))->assertOk()->assertDontSee('Reportar posible adulteración');
    }

    public function test_el_filtro_por_dia_peruano_y_tecnico_acota_analisis_casos_y_conteos(): void
    {
        $this->analisis(['registrado_en' => '2026-10-29 04:59:59']);
        $incluido = $this->analisis(['registrado_en' => '2026-10-29 05:00:00']);
        $this->registrar($incluido);
        $this->analisis(['registrado_en' => '2026-10-30 05:00:00']);
        $this->analisis(['usuario_id' => $this->admin->id]);

        $filtros = ['desde' => '2026-10-29', 'hasta' => '2026-10-29', 'tecnico' => $this->tecnico->id];
        $this->get(route('admin.calidad.index', $filtros))->assertOk()
            ->assertViewHas('analisis', fn ($paginador) => $paginador->total() === 1)
            ->assertViewHas('conteos', fn ($conteos) => $conteos['rechazado'] === 1 && array_sum($conteos) === 1);
        $this->get(route('admin.calidad.index', ['tab' => 'incidencias', ...$filtros]))->assertOk()
            ->assertViewHas('incidencias', fn ($paginador) => $paginador->total() === 1)
            ->assertViewHas('resumenIncidencias', fn ($conteos) => $conteos['pendientes'] === 1);
        $this->get(route('admin.calidad.index', ['desde' => '2026-10-29', 'hasta' => '2026-10-28']))
            ->assertSessionHasErrors('hasta');
    }
}
