<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Celular -> panel: lo que se crea o cambia en la app llega a la base del panel con sus mismas reglas, y
 * reenviar el mismo cambio no duplica nada.
 */
class CambiosMovilTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['username' => 'admin_app', 'pin_hash' => '1357', 'roles' => ['admin']]);
        $this->zona = Zona::factory()->create(['nombre' => 'FAON-MARKAPAJO']);
    }

    private function token(string $username, string $pin = '1357'): string
    {
        return $this->postJson('/api/movil/sesion', ['username' => $username, 'pin' => $pin])->assertOk()->json('token');
    }

    private function cambiar(string $token, string $entidad, array $datos)
    {
        return $this->withToken($token)->putJson("/api/movil/cambios/{$entidad}", $datos);
    }

    public function test_zonas_y_vehiculos_creados_o_editados_en_la_app_llegan_al_panel(): void
    {
        $token = $this->token('admin_app');

        $id = $this->cambiar($token, 'zona', ['nombre' => 'ZONA NUEVA', 'activo' => true])->assertOk()->json('id');
        $this->cambiar($token, 'zona', ['nombre' => 'ZONA NUEVA', 'activo' => true])->assertOk()->assertJsonPath('id', $id);
        $this->cambiar($token, 'zona', ['servidorId' => $id, 'nombre' => 'ZONA RENOMBRADA', 'activo' => false])->assertOk();
        $this->assertDatabaseHas('zonas', ['id' => $id, 'nombre' => 'ZONA RENOMBRADA', 'activo' => false]);
        $this->assertSame(2, Zona::count());

        $this->cambiar($token, 'vehiculo', ['nombre' => 'Isuzu', 'placa' => 'abc-123', 'activo' => true])->assertOk();
        $this->assertDatabaseHas('vehiculos', ['placa' => 'ABC-123', 'nombre' => 'Isuzu']);
    }

    public function test_una_cuenta_creada_en_la_app_entra_al_panel_y_conserva_sus_roles_solo_web(): void
    {
        $token = $this->token('admin_app');
        $datos = ['username' => 'nuevo_app', 'nombres' => 'Lucía Ramos Mamani', 'dni' => '99007777', 'roles' => ['ACOPIADOR'], 'activo' => true, 'pin' => '2468'];

        $id = $this->cambiar($token, 'usuario', $datos)->assertOk()->json('id');
        $this->token('nuevo_app', '2468');

        Usuario::query()->whereKey($id)->update(['roles' => ['acopiador', 'produccion']]);
        $this->cambiar($token, 'usuario', [...$datos, 'servidorId' => $id, 'roles' => ['ACOPIADOR', 'CALIDAD'], 'pin' => null])->assertOk();
        $this->assertEqualsCanonicalizing(['acopiador', 'calidad', 'produccion'], Usuario::query()->findOrFail($id)->roles);

        $this->cambiar($token, 'usuario', [...$datos, 'username' => 'sin_pin', 'pin' => null])
            ->assertUnprocessable()->assertJsonPath('codigo', 'pin_requerido');
    }

    public function test_proveedor_y_su_cuenta_desde_la_app(): void
    {
        $token = $this->token('admin_app');
        $cuenta = Usuario::factory()->create(['username' => 'prov_app', 'roles' => ['proveedor']]);
        $datos = ['codigo' => 'PRV-APP-01', 'nombres' => 'Teresa Huanca Condori', 'dni' => '99008888', 'telefono' => null, 'direccion' => null,
            'zonaNombre' => 'FAON-MARKAPAJO', 'tachos' => 2, 'capacidadTachoL' => 40, 'estado' => 'ACTIVO', 'usuarioUsername' => 'prov_app'];

        $id = $this->cambiar($token, 'proveedor', $datos)->assertOk()->json('id');
        $this->cambiar($token, 'proveedor', [...$datos, 'servidorId' => $id, 'estado' => 'SUSPENDIDO'])->assertOk();

        $proveedor = Proveedor::query()->findOrFail($id);
        $this->assertSame('suspendido', $proveedor->estado->value);
        $this->assertSame($cuenta->id, $proveedor->usuario_id);
    }

    public function test_solo_el_admin_cambia_catalogos(): void
    {
        Usuario::factory()->create(['username' => 'acop_app', 'pin_hash' => '1357', 'roles' => ['acopiador']]);

        $this->cambiar($this->token('acop_app'), 'zona', ['nombre' => 'X', 'activo' => true])->assertForbidden();
    }

    public function test_jornada_abierta_y_cerrada_en_la_app_sin_entregas(): void
    {
        $acopiador = Usuario::factory()->create(['username' => 'acop_app', 'pin_hash' => '1357', 'roles' => ['acopiador']]);
        Vehiculo::factory()->create(['placa' => 'V1A-123']);
        $token = $this->token('acop_app');
        $datos = ['uuid' => 'jor-app-1', 'zonaNombre' => 'FAON-MARKAPAJO', 'vehiculoPlaca' => 'V1A-123', 'abiertaEn' => 1_790_000_000_000, 'cerradaEn' => null];

        $this->cambiar($token, 'jornada', $datos)->assertOk();
        $this->cambiar($token, 'jornada', [...$datos, 'cerradaEn' => 1_790_000_900_000])->assertOk();

        $jornada = Jornada::query()->where('uuid_movil', 'jor-app-1')->sole();
        $this->assertSame($acopiador->id, $jornada->usuario_id);
        $this->assertNotNull($jornada->cerrada_en);
    }

    public function test_comunicado_publicado_y_retirado_desde_la_app(): void
    {
        $token = $this->token('admin_app');
        $datos = ['uuid' => 'com-1', 'mensaje' => "Recojo temprano\nMañana a las 5 am", 'publicadoEn' => 1_790_000_000_000, 'eliminado' => false];

        $this->cambiar($token, 'comunicado', $datos)->assertOk();
        $this->assertDatabaseHas('comunicados', ['codigo' => 'MOV-com-1', 'titulo' => 'Recojo temprano', 'estado' => 'publicado']);

        $this->cambiar($token, 'comunicado', [...$datos, 'mensaje' => null, 'eliminado' => true])->assertOk();
        $this->assertSame('borrador', Comunicado::query()->where('codigo', 'MOV-com-1')->value('estado'));
    }

    public function test_el_analisis_de_la_app_llega_completo_y_califica_las_entregas_del_dia(): void
    {
        $tecnico = Usuario::factory()->create(['username' => 'cal_app', 'pin_hash' => '1357', 'roles' => ['calidad']]);
        $proveedor = Proveedor::factory()->create(['codigo' => 'PRV-CAL', 'zona_id' => $this->zona->id]);
        $token = $this->token('cal_app');
        $datos = ['uuid' => 'cc-1', 'proveedorCodigo' => 'PRV-CAL', 'codigoMuestra' => 'AN-CC1', 'origenCaptura' => 'ESCANER',
            'serialAnalizador' => 'LS-900', 'temperatura' => 6.0, 'grasa' => 2.1, 'sng' => 8.7, 'aguaAnadida' => 0.0, 'puntoCongelacion' => -0.53,
            'estado' => 'OBSERVADO', 'alertas' => ['Grasa: 2.1 · Referencia: 3.0 a 6.0 %'], 'observaciones' => 'Revisar alimentación',
            'visita' => ['tecnicoNombre' => 'Rosa Apaza', 'unidadCongelacion' => '°C', 'parametrosAlertados' => ['grasa']],
            'registradoEn' => now()->getTimestampMs()];

        // Sin entrega todavía: el análisis igual se guarda; la entrega se califica cuando llega.
        $id = $this->cambiar($token, 'calidad', $datos)->assertOk()->json('id');
        $this->cambiar($token, 'calidad', $datos)->assertOk()->assertJsonPath('id', $id);
        $entrega = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'registrado_en' => now()->subHour()]);

        $analisis = AnalisisCalidad::query()->sole();
        $this->assertSame('cc-1', $analisis->uuid);
        $this->assertSame('AN-CC1', $analisis->codigo_muestra);
        $this->assertSame(2.1, $analisis->grasa);
        $this->assertSame(-0.53, $analisis->punto_congelacion);
        $this->assertSame('OBSERVADO', $analisis->estado->value);
        $this->assertSame($tecnico->id, $analisis->usuario_id);
        $this->assertSame('Rosa Apaza', $analisis->visita['tecnicoNombre']);
        $this->assertDatabaseHas('controles_calidad', ['entrega_id' => $entrega->id, 'resultado' => 'observado', 'analisis_calidad_id' => $analisis->id]);
    }

    public function test_reclamo_del_proveedor_y_su_resolucion_por_el_admin(): void
    {
        $cuenta = Usuario::factory()->create(['username' => 'prov_app', 'pin_hash' => '1357', 'roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $cuenta->id]);
        $entrega = Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 40, 'uuid_movil' => 'ent-1']);
        $datos = ['uuid' => 'sol-1', 'entregaUuid' => 'ent-1', 'litros' => 42, 'motivo' => 'Faltan 2 litros', 'estado' => 'PENDIENTE'];

        $this->cambiar($this->token('prov_app'), 'reclamo', $datos)->assertOk();
        $this->cambiar($this->token('admin_app'), 'reclamo', [...$datos, 'estado' => 'ATENDIDA'])->assertOk();

        $reclamo = ReclamoProveedor::query()->where('uuid_movil', 'sol-1')->sole();
        $this->assertSame($entrega->id, $reclamo->entrega_id);
        $this->assertSame('resuelto', $reclamo->estado);
    }

    public function test_liquidacion_aprobada_y_pagada_en_la_app(): void
    {
        $proveedor = Proveedor::factory()->create(['codigo' => 'PRV-LIQ']);
        Entrega::factory()->create(['proveedor_id' => $proveedor->id, 'litros' => 50, 'registrado_en' => '2026-09-15 12:00:00']);
        $token = $this->token('admin_app');
        $datos = ['proveedorCodigo' => 'PRV-LIQ', 'desde' => '2026-09-14', 'hasta' => '2026-09-20', 'precio' => 1.8, 'estado' => 'APROBADA'];

        $id = $this->cambiar($token, 'liquidacion', $datos)->assertOk()->json('id');
        $this->cambiar($token, 'liquidacion', [...$datos, 'estado' => 'PAGADA'])->assertOk()->assertJsonPath('id', $id);

        $liquidacion = Liquidacion::query()->findOrFail($id);
        $this->assertSame('pagada', $liquidacion->estado->value);
        $this->assertSame(1, Liquidacion::count());
    }

    public function test_corregir_en_la_app_una_entrega_registrada_en_la_web_no_la_duplica(): void
    {
        Vehiculo::factory()->create(['placa' => 'V1A-123']);
        $proveedor = Proveedor::factory()->create(['codigo' => 'PRV-WEB', 'zona_id' => $this->zona->id]);
        $jornada = Jornada::factory()->create(['usuario_id' => $this->admin->id, 'zona_id' => $this->zona->id]);
        $entrega = Entrega::factory()->create(['jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'litros' => 40]);

        $this->withToken($this->token('admin_app'))->putJson("/api/movil/entregas/web-entrega-{$entrega->id}", [
            'jornadaId' => "web-jornada-{$jornada->id}", 'jornadaAbiertaEn' => 1_790_000_000_000, 'jornadaCerradaEn' => null,
            'proveedorCodigo' => 'PRV-WEB', 'zonaNombre' => 'FAON-MARKAPAJO', 'vehiculoPlaca' => 'V1A-123',
            'acopiadorUsername' => 'admin_app', 'registradoEn' => 1_790_000_100_000, 'litros' => 35.5, 'tachos' => 1,
            'observaciones' => null, 'anulada' => false, 'motivo' => 'Corrección', 'actualizadoEn' => 1_790_000_200_000,
        ])->assertOk();

        $this->assertSame(1, Entrega::count());
        $this->assertSame(1, Jornada::count());
        $this->assertEquals(35.5, (float) $entrega->fresh()->litros);
    }
}
