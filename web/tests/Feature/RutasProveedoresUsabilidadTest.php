<?php

namespace Tests\Feature;

use App\Application\Jornadas\ConsultarHojaRutaQuery;
use App\Infrastructure\Persistence\Eloquent as M;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutasProveedoresUsabilidadTest extends TestCase
{
    use RefreshDatabase;

    private M\Usuario $admin;

    private M\Usuario $acopiador;

    private M\Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = M\Usuario::factory()->create(['roles' => ['admin']]);
        $this->acopiador = M\Usuario::factory()->create(['roles' => ['acopiador']]);
        $this->zona = M\Zona::factory()->create(['activo' => true]);
        $this->actingAs($this->admin, 'operador');
    }

    private function parada(?M\Proveedor $proveedor = null): array
    {
        return ['nombre' => $proveedor?->nombres ?? 'Planta de recepción', 'proveedor_id' => $proveedor?->id,
            'latitud' => -15.61, 'longitud' => -69.97, 'referencia' => 'Portón azul'];
    }

    private function datos(array $paradas): array
    {
        return ['nombre' => 'Ruta Norte', 'zona_id' => $this->zona->id, 'activo' => 1,
            'acopiador_id' => $this->acopiador->id, 'paradas_json' => json_encode($paradas),
            'trazado_json' => '[]', 'resumen_recorrido_json' => 'null'];
    }

    public function test_al_crear_ruta_los_proveedores_quedan_vinculados_y_en_orden_para_el_acopiador(): void
    {
        $ana = M\Proveedor::factory()->create(['zona_id' => $this->zona->id, 'nombres' => 'Ana', 'estado' => 'activo']);
        $zoe = M\Proveedor::factory()->create(['zona_id' => $this->zona->id, 'nombres' => 'Zoe', 'estado' => 'activo']);
        $paradas = [$this->parada($zoe), $this->parada(), $this->parada($ana)];
        $this->post(route('admin.mapa-rutas.store'), $this->datos($paradas))->assertSessionHasNoErrors();
        $ruta = M\Ruta::firstOrFail();
        $this->assertSame([$zoe->id, null, $ana->id], array_column($ruta->paradas, 'proveedor_id'));
        $jornada = M\Jornada::factory()->create(['usuario_id' => $this->acopiador->id, 'zona_id' => $this->zona->id]);
        $hoja = app(ConsultarHojaRutaQuery::class)->ejecutar($jornada);
        $this->assertSame([$zoe->id, $ana->id], $hoja['filas']->getCollection()->pluck('proveedor.id')->all());
        $this->get(route('admin.mapa-rutas.index'))->assertOk()->assertSee('2 proveedores vinculados');
        $this->get(route('admin.mapa-rutas.show', $ruta))->assertOk()
            ->assertViewHas('configMapa', fn ($config) => count($config['proveedores']) === 2);
    }

    public function test_se_pueden_reordenar_y_quitar_proveedores_sin_modificar_sus_fichas(): void
    {
        $proveedores = M\Proveedor::factory()->count(2)->create(['zona_id' => $this->zona->id, 'estado' => 'activo']);
        $this->post(route('admin.mapa-rutas.store'), $this->datos($proveedores->map(fn ($p) => $this->parada($p))->all()))->assertSessionHasNoErrors();
        $ruta = M\Ruta::firstOrFail();
        $this->put(route('admin.mapa-rutas.update', $ruta), $this->datos([$this->parada($proveedores[1]), $this->parada($proveedores[0])]) + ['mapa_version' => $ruta->mapa_version])
            ->assertSessionHasNoErrors();
        $ruta->refresh();
        $this->assertSame([$proveedores[1]->id, $proveedores[0]->id], array_column($ruta->paradas, 'proveedor_id'));
        $this->put(route('admin.mapa-rutas.update', $ruta), $this->datos([$this->parada($proveedores[1])]) + ['mapa_version' => $ruta->mapa_version])
            ->assertSessionHasNoErrors();
        $this->assertSame([$proveedores[1]->id], array_column($ruta->fresh()->paradas, 'proveedor_id'));
        $this->assertDatabaseCount('proveedores', 2);
        $this->assertEquals($this->zona->id, $proveedores[0]->fresh()->zona_id);
    }

    public function test_no_permite_repetir_un_proveedor_o_asociar_uno_inactivo_o_de_otro_sector(): void
    {
        $propio = M\Proveedor::factory()->create(['zona_id' => $this->zona->id, 'estado' => 'activo']);
        $ajeno = M\Proveedor::factory()->create(['estado' => 'activo']);
        $inactivo = M\Proveedor::factory()->create(['zona_id' => $this->zona->id, 'estado' => 'suspendido']);
        foreach ([[$this->parada($propio), $this->parada($propio)], [$this->parada($ajeno)], [$this->parada($inactivo)]] as $paradas) {
            $response = $this->post(route('admin.mapa-rutas.store'), $this->datos($paradas));
            $response->assertSessionHasErrors('paradas.0.proveedor_id');
            $this->assertDatabaseCount('rutas', 0);
        }
    }

    public function test_el_acopiador_consulta_su_ruta_pero_no_puede_cambiar_proveedores(): void
    {
        $proveedor = M\Proveedor::factory()->create(['zona_id' => $this->zona->id, 'estado' => 'activo']);
        $this->post(route('admin.mapa-rutas.store'), $this->datos([$this->parada($proveedor)]))->assertSessionHasNoErrors();
        $ruta = M\Ruta::firstOrFail();
        $this->actingAs($this->acopiador, 'operador');
        $this->get(route('admin.mapa-rutas.show', $ruta))->assertOk()->assertDontSee('data-provider-search', false);
        $this->put(route('admin.mapa-rutas.update', $ruta), $this->datos([$this->parada()]) + ['mapa_version' => $ruta->mapa_version])->assertForbidden();
        $this->assertSame($proveedor->id, $ruta->fresh()->paradas[0]['proveedor_id']);
    }

    public function test_la_ruta_guia_la_seleccion_de_proveedores_y_conserva_datos_al_fallar(): void
    {
        $proveedor = M\Proveedor::factory()->create(['zona_id' => $this->zona->id, 'estado' => 'activo']);
        $this->get(route('admin.mapa-rutas.create'))->assertOk()->assertSee('Proveedores de la zona')->assertSee('Orden de visita')
            ->assertSee('data-provider-search', false)->assertSee('data-agregar-ubicados', false)->assertDontSee('data-stop-dialog', false);
        $datos = $this->datos([$this->parada($proveedor)]);
        $datos['nombre'] = '';
        $this->from(route('admin.mapa-rutas.create'))->post(route('admin.mapa-rutas.store'), $datos)
            ->assertSessionHasErrors('nombre');
        $this->get(route('admin.mapa-rutas.create'))->assertOk()
            ->assertViewHas('configMapa', fn ($config) => $config['paradas'][0]['proveedor_id'] === $proveedor->id && $config['cambios_pendientes']);
    }

    public function test_una_edicion_antigua_no_sobrescribe_los_proveedores_que_otro_usuario_guardo(): void
    {
        $proveedores = M\Proveedor::factory()->count(2)->create(['zona_id' => $this->zona->id, 'estado' => 'activo']);
        $this->post(route('admin.mapa-rutas.store'), $this->datos([$this->parada($proveedores[0])]))->assertSessionHasNoErrors();
        $ruta = M\Ruta::firstOrFail();
        $version = $ruta->mapa_version;
        $this->put(route('admin.mapa-rutas.update', $ruta), $this->datos([$this->parada($proveedores[1])]) + ['mapa_version' => $version])
            ->assertSessionHasNoErrors();
        $this->put(route('admin.mapa-rutas.update', $ruta), $this->datos([$this->parada($proveedores[0])]) + ['mapa_version' => $version])
            ->assertSessionHasErrors('mapa_version');
        $this->assertSame($proveedores[1]->id, $ruta->fresh()->paradas[0]['proveedor_id']);
    }

    public function test_inicio_muestra_tareas_y_enlaces_sin_buscador_en_el_menu(): void
    {
        $response = $this->get(route('admin.dashboard.index'))->assertOk()->assertDontSee('¿Qué necesitas hacer?')
            ->assertSee('Preparar una ruta')->assertSee('Hacer una producción')->assertDontSee('data-menu-search', false);
        foreach (['usuarios', 'proveedores', 'mapa-rutas', 'jornadas', 'recepcion', 'entregas', 'calidad', 'sanciones', 'produccion', 'inventario', 'ventas', 'liquidaciones', 'comunicados', 'reportes', 'importaciones', 'auditoria', 'configuracion'] as $modulo) {
            $response->assertSee(route('admin.'.$modulo.'.index'), false);
        }
        $this->actingAs($this->acopiador, 'operador');
        $this->get(route('admin.mapa-rutas.index'))->assertOk()->assertDontSee(route('admin.usuarios.index'), false)
            ->assertDontSee(route('admin.liquidaciones.index'), false);
    }
}
