<?php

namespace Tests\Feature;

use App\Application\Movil\RevisionCatalogo;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrenciaCatalogosCoder1Test extends TestCase
{
    use RefreshDatabase;

    private function token(string $rol = 'admin'): string
    {
        $usuario = Usuario::factory()->create(['roles' => [$rol], 'pin_hash' => '2468']);

        return $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468'])->assertOk()->json('token');
    }

    private function ejemplos(): array
    {
        $zona = Zona::factory()->create(['nombre' => 'ZONA BASE']);
        $vehiculo = Vehiculo::factory()->create();
        $cuenta = Usuario::factory()->create(['roles' => ['calidad']]);
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id]);

        return [
            ['zona', $zona, ['nombre' => 'ZONA MOVIL', 'activo' => true]],
            ['vehiculo', $vehiculo, ['nombre' => 'Vehículo móvil', 'placa' => $vehiculo->placa, 'activo' => true]],
            ['usuario', $cuenta, ['username' => $cuenta->username, 'nombres' => 'Nombre móvil', 'dni' => $cuenta->dni, 'roles' => ['CALIDAD'], 'activo' => true]],
            ['proveedor', $proveedor, ['codigo' => $proveedor->codigo, 'nombres' => 'Proveedor móvil', 'dni' => $proveedor->dni,
                'telefono' => $proveedor->telefono, 'direccion' => $proveedor->direccion, 'zonaId' => $zona->id, 'zonaNombre' => $zona->nombre,
                'tachos' => $proveedor->tachos, 'capacidadTachoL' => (float) $proveedor->capacidad_tacho_l, 'estado' => 'ACTIVO', 'usuarioUsername' => null]],
        ];
    }

    public function test_los_cuatro_catalogos_rechazan_base_antigua_sin_sobrescribir(): void
    {
        $token = $this->token();
        foreach ($this->ejemplos() as [$entidad, $modelo, $datos]) {
            $revision = RevisionCatalogo::de($modelo->refresh());
            $campo = $entidad === 'zona' ? 'nombre' : 'nombres';
            if ($entidad === 'vehiculo') {
                $campo = 'nombre';
            }
            $modelo->update([$campo => 'Cambio simultáneo web']);
            $this->withToken($token)->putJson('/api/movil/cambios/'.$entidad, [...$datos,
                'servidorId' => $modelo->id, 'controlVersion' => true, 'revisionBase' => $revision,
            ])->assertConflict()->assertJsonPath('codigo', 'catalogo_conflicto')->assertJsonPath('definitivo', true);
            $this->assertSame('Cambio simultáneo web', $modelo->fresh()->getAttribute($campo));
        }
    }

    public function test_base_vigente_permite_actualizar_y_reintento_tras_respuesta_perdida_no_duplica(): void
    {
        $token = $this->token();
        foreach ($this->ejemplos() as [$entidad, $modelo, $datos]) {
            $payload = [...$datos, 'servidorId' => $modelo->id, 'controlVersion' => true, 'revisionBase' => RevisionCatalogo::de($modelo->refresh())];
            $this->withToken($token)->putJson('/api/movil/cambios/'.$entidad, $payload)->assertOk()->assertJsonPath('id', $modelo->id);
            $revisionConfirmada = RevisionCatalogo::de($modelo->fresh());
            $this->withToken($token)->putJson('/api/movil/cambios/'.$entidad, $payload)->assertOk()->assertJsonPath('id', $modelo->id);
            $this->assertSame($revisionConfirmada, RevisionCatalogo::de($modelo->fresh()));
        }
    }

    public function test_una_alta_no_puede_pisar_otra_zona_del_mismo_nombre_sin_base(): void
    {
        $token = $this->token();
        $zona = Zona::factory()->create(['nombre' => 'ZONA EXISTENTE']);
        $this->withToken($token)->putJson('/api/movil/cambios/zona', ['nombre' => $zona->nombre, 'activo' => false, 'controlVersion' => true])
            ->assertConflict();
        $this->assertTrue($zona->fresh()->activo);
        $this->withToken($token)->putJson('/api/movil/cambios/zona', ['nombre' => 'NUEVA SEGURA', 'activo' => true, 'controlVersion' => true])
            ->assertOk();
        $this->assertDatabaseCount('zonas', 2);
    }

    public function test_cliente_anterior_conserva_contrato_y_uno_actual_necesita_descargar_su_base(): void
    {
        $token = $this->token();
        $zona = Zona::factory()->create(['nombre' => 'ZONA BASE']);
        $datos = ['servidorId' => $zona->id, 'nombre' => 'CAMBIO', 'activo' => true];
        $this->withToken($token)->putJson('/api/movil/cambios/zona', [...$datos, 'controlVersion' => true])->assertConflict();
        $this->withToken($token)->putJson('/api/movil/cambios/zona', $datos)->assertOk();
        $this->assertSame('CAMBIO', $zona->fresh()->nombre);
        $zonas = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json('zonas');
        $this->assertSame(RevisionCatalogo::de($zona->fresh()), collect($zonas)->firstWhere('id', $zona->id)['revision']);
    }

    public function test_una_revision_valida_no_otorga_permiso_de_edicion(): void
    {
        $token = $this->token('acopiador');
        $zona = Zona::factory()->create();
        $this->withToken($token)->putJson('/api/movil/cambios/zona', ['servidorId' => $zona->id,
            'nombre' => 'INTRUSION', 'activo' => true, 'controlVersion' => true, 'revisionBase' => RevisionCatalogo::de($zona),
        ])->assertForbidden();
        $this->assertNotSame('INTRUSION', $zona->fresh()->nombre);
    }
}
