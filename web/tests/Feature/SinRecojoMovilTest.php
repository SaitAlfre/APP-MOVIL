<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SinRecojoMovilTest extends TestCase
{
    use RefreshDatabase;

    private array $datos;

    protected function setUp(): void
    {
        parent::setUp();
        $zona = Zona::factory()->create(['nombre' => 'ZONA API']);
        Vehiculo::factory()->create(['placa' => 'API-001']);
        Usuario::factory()->create(['username' => 'acop_api', 'pin_hash' => '2468', 'roles' => ['acopiador']]);
        $cuenta = Usuario::factory()->create(['username' => 'prov_api', 'pin_hash' => '2468', 'roles' => ['proveedor']]);
        Proveedor::factory()->create(['codigo' => 'PRV-API', 'zona_id' => $zona->id, 'usuario_id' => $cuenta->id]);
        $ahora = now()->getTimestampMs();
        $this->datos = ['uuid' => 'marca-api', 'jornadaId' => 'jornada-api', 'proveedorCodigo' => 'PRV-API',
            'zonaNombre' => 'ZONA API', 'vehiculoPlaca' => 'API-001', 'abiertaEn' => $ahora - 1000,
            'cerradaEn' => null, 'fecha' => now('America/Lima')->toDateString(), 'motivo' => 'SIN_LECHE',
            'detalle' => 'No tenía leche', 'registradoEn' => $ahora, 'deshecha' => false,
            'deshechaEn' => null, 'actualizadoEn' => $ahora];
    }

    private function token(string $username): string
    {
        return $this->postJson('/api/movil/sesion', ['username' => $username, 'pin' => '2468', 'dispositivo' => 'Prueba'])
            ->assertOk()->json('token');
    }

    public function test_envio_reintento_descarga_y_deshacer_conservan_una_sola_marca(): void
    {
        $token = $this->token('acop_api');
        $this->withToken($token)->putJson('/api/movil/cambios/sin_recojo', $this->datos)->assertOk();
        $this->withToken($token)->putJson('/api/movil/cambios/sin_recojo', $this->datos)->assertOk();
        $this->assertDatabaseCount('sin_recojos', 1);
        $proveedor = $this->token('prov_api');
        $this->withToken($proveedor)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonPath('sinRecojos.0.id', 'marca-api')->assertJsonPath('sinRecojos.0.deshecha', false)
            ->assertJsonPath('sinRecojos.0.motivo', 'SIN_LECHE');
        $deshecha = [...$this->datos, 'deshecha' => true, 'deshechaEn' => $this->datos['actualizadoEn'] + 1000,
            'actualizadoEn' => $this->datos['actualizadoEn'] + 1000];
        $this->withToken($token)->putJson('/api/movil/cambios/sin_recojo', $deshecha)->assertOk();
        $this->withToken($token)->putJson('/api/movil/cambios/sin_recojo', $this->datos)->assertOk();
        $this->withToken($proveedor)->getJson('/api/movil/datos')->assertOk()->assertJsonPath('sinRecojos.0.deshecha', true);
        $this->assertDatabaseCount('sin_recojos', 1);
    }

    public function test_otro_acopiador_no_puede_modificar_la_marca_y_otro_proveedor_no_la_recibe(): void
    {
        $this->withToken($this->token('acop_api'))->putJson('/api/movil/cambios/sin_recojo', $this->datos)->assertOk();
        Usuario::factory()->create(['username' => 'otro_acop', 'pin_hash' => '2468', 'roles' => ['acopiador']]);
        $ajeno = Usuario::factory()->create(['username' => 'otro_prov', 'pin_hash' => '2468', 'roles' => ['proveedor']]);
        Proveedor::factory()->create(['usuario_id' => $ajeno->id]);
        $this->withToken($this->token('otro_acop'))->putJson('/api/movil/cambios/sin_recojo',
            [...$this->datos, 'deshecha' => true, 'actualizadoEn' => $this->datos['actualizadoEn'] + 2000])->assertForbidden();
        $this->withToken($this->token('otro_prov'))->getJson('/api/movil/datos')->assertOk()->assertJsonCount(0, 'sinRecojos');
        $this->withToken($this->token('prov_api'))->putJson('/api/movil/cambios/sin_recojo', $this->datos)->assertForbidden();
        $this->assertFalse(SinRecojo::query()->sole()->deshecha);
    }

    public function test_una_version_igual_con_otro_contenido_es_conflicto_y_no_sobrescribe(): void
    {
        $token = $this->token('acop_api');
        $this->withToken($token)->putJson('/api/movil/cambios/sin_recojo', $this->datos)->assertOk();
        $this->withToken($token)->putJson('/api/movil/cambios/sin_recojo', [...$this->datos, 'motivo' => 'OTRO'])->assertConflict();
        $this->assertSame('SIN_LECHE', SinRecojo::query()->sole()->motivo);
    }
}
