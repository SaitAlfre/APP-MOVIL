<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Organización del acopio: camino por carretera con OpenRouteService desde el servidor, ubicación
 * de proveedores (web y celular), resumen por zona y traslados con 3 días de aviso.
 */
class OrganizacionAcopioTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mapas.ors.clave' => 'clave-de-prueba']);
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($this->admin, 'operador');
    }

    /** @return list<array{latitud: float, longitud: float}> */
    private function paradas(): array
    {
        return [['latitud' => -15.604, 'longitud' => -69.972], ['latitud' => -15.6034, 'longitud' => -69.971]];
    }

    private function fingirOrs(): void
    {
        Http::fake(['api.openrouteservice.org/*' => Http::response(['type' => 'FeatureCollection', 'features' => [[
            'geometry' => ['type' => 'LineString', 'coordinates' => [[-69.972, -15.604], [-69.9715, -15.6037], [-69.971, -15.6034]]],
            'properties' => ['summary' => ['distance' => 1520.4, 'duration' => 310.2]],
        ]]])]);
    }

    public function test_el_camino_se_calcula_en_el_servidor_segun_el_vehiculo(): void
    {
        $this->fingirOrs();

        $this->postJson(route('admin.mapa-rutas.trazar'), ['tipo_vehiculo' => 'camion', 'paradas' => $this->paradas()])->assertOk()
            ->assertJsonPath('code', 'Ok')->assertJsonPath('routes.0.distance', 1520.4)
            ->assertJsonPath('routes.0.geometry.coordinates.0', [-69.972, -15.604]);
        $this->postJson(route('admin.mapa-rutas.trazar'), ['tipo_vehiculo' => 'motocarga', 'paradas' => $this->paradas()])->assertOk();

        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), '/v2/directions/driving-hgv/geojson')
            && $request->hasHeader('Authorization', 'clave-de-prueba'));
        Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), '/v2/directions/driving-car/geojson'));
    }

    public function test_el_camino_sale_y_vuelve_a_la_planta_y_devuelve_cada_tramo(): void
    {
        config(['mapas.planta' => ['nombre' => 'Planta', 'latitud' => -15.6137, 'longitud' => -69.9762]]);
        Http::fake(['api.openrouteservice.org/*' => Http::response(['features' => [[
            'geometry' => ['coordinates' => [[-69.9762, -15.6137], [-69.974, -15.61], [-69.972, -15.604], [-69.971, -15.6034], [-69.973, -15.609], [-69.9762, -15.6137]]],
            'properties' => ['summary' => ['distance' => 3000, 'duration' => 600], 'way_points' => [0, 2, 3, 5]],
        ]]])]);

        $this->postJson(route('admin.mapa-rutas.trazar'), ['tipo_vehiculo' => 'camion', 'paradas' => $this->paradas()])->assertOk()
            ->assertJsonCount(3, 'routes.0.legs')
            ->assertJsonPath('routes.0.legs.0.coordinates.0', [-69.9762, -15.6137])
            ->assertJsonPath('routes.0.distance', 3000);
        Http::assertSent(fn (ClientRequest $request) => count($request['coordinates']) === 4
            && $request['coordinates'][0] === [-69.9762, -15.6137] && $request['coordinates'][3] === [-69.9762, -15.6137]);
    }

    public function test_los_tramos_dibujados_a_mano_se_guardan_con_la_ruta(): void
    {
        $zona = Zona::factory()->create(['activo' => true]);
        $paradas = [['nombre' => 'A', 'proveedor_id' => null, 'latitud' => -15.6041, 'longitud' => -69.9722, 'referencia' => ''],
            ['nombre' => 'B', 'proveedor_id' => null, 'latitud' => -15.6050, 'longitud' => -69.9700, 'referencia' => '']];
        $firma = '-15.604100,-69.972200;-15.605000,-69.970000';
        $trocha = [['latitud' => -15.6041, 'longitud' => -69.9722], ['latitud' => -15.6046, 'longitud' => -69.9715], ['latitud' => -15.6050, 'longitud' => -69.9700]];

        $this->post(route('admin.mapa-rutas.store'), [
            'nombre' => 'Ruta con trocha', 'zona_id' => $zona->id, 'activo' => 1, 'tipo_vehiculo' => 'motocarga',
            'paradas_json' => json_encode($paradas), 'trazado_json' => json_encode($trocha),
            'resumen_recorrido_json' => json_encode(['fuente' => 'manual', 'distancia_m' => 260, 'duracion_s' => 62, 'firma_paradas' => $firma,
                'tramos_manuales' => [['clave' => '-15.604100,-69.972200>-15.605000,-69.970000', 'puntos' => $trocha]]]),
        ])->assertSessionHasNoErrors();

        $resumen = Ruta::query()->sole()->resumen_recorrido;
        $this->assertSame('manual', $resumen['fuente']);
        $this->assertCount(3, $resumen['tramos_manuales'][0]['puntos']);
    }

    public function test_sin_clave_o_sin_camino_responde_un_mensaje_claro(): void
    {
        config(['mapas.ors.clave' => null]);
        $this->postJson(route('admin.mapa-rutas.trazar'), ['tipo_vehiculo' => 'camion', 'paradas' => $this->paradas()])
            ->assertUnprocessable()->assertJsonPath('message', 'Falta configurar la clave de OpenRouteService. Puedes dibujar el camino manualmente.');

        config(['mapas.ors.clave' => 'clave-de-prueba']);
        Http::fake(['api.openrouteservice.org/*' => Http::response(['error' => ['code' => 2010]], 404)]);
        $this->postJson(route('admin.mapa-rutas.trazar'), ['tipo_vehiculo' => 'camion', 'paradas' => $this->paradas()])
            ->assertUnprocessable()->assertJsonPath('code', 'Error');
        $this->postJson(route('admin.mapa-rutas.trazar'), ['tipo_vehiculo' => 'avion', 'paradas' => $this->paradas()])->assertUnprocessable();
    }

    public function test_guardar_la_ruta_fija_la_ubicacion_del_proveedor_y_el_vehiculo(): void
    {
        $zona = Zona::factory()->create(['activo' => true]);
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id]);

        $this->post(route('admin.mapa-rutas.store'), [
            'nombre' => 'Ruta Markapajo', 'zona_id' => $zona->id, 'activo' => 1, 'tipo_vehiculo' => 'motocarga',
            'paradas_json' => json_encode([['nombre' => $proveedor->nombres, 'proveedor_id' => $proveedor->id, 'latitud' => -15.6041, 'longitud' => -69.9722, 'referencia' => '']]),
            'trazado_json' => '[]', 'resumen_recorrido_json' => 'null',
        ])->assertSessionHasNoErrors();

        $this->assertSame('motocarga', Ruta::query()->sole()->tipo_vehiculo);
        $proveedor->refresh();
        $this->assertEqualsWithDelta(-15.6041, $proveedor->latitud, 0.000001);
        $this->assertSame('web', $proveedor->ubicacion_origen);
        $this->assertSame($this->admin->id, $proveedor->ubicacion_por);
    }

    public function test_el_acopiador_de_la_zona_guarda_la_ubicacion_desde_el_celular(): void
    {
        $zona = Zona::factory()->create(['activo' => true]);
        $proveedor = Proveedor::factory()->create(['zona_id' => $zona->id]);
        $ruta = Ruta::query()->create(['codigo' => 'R-PRUEBA', 'nombre' => 'Ruta', 'zona_id' => $zona->id, 'activo' => true, 'mapa_version' => 1,
            'paradas' => [['nombre' => $proveedor->nombres, 'proveedor_id' => $proveedor->id, 'latitud' => -15.6, 'longitud' => -69.9, 'referencia' => '']]]);
        $acopiador = Usuario::factory()->create(['username' => 'acop_prueba', 'pin_hash' => '2468', 'roles' => ['acopiador'], 'zona_id' => $zona->id]);
        $ajeno = Usuario::factory()->create(['username' => 'acop_ajeno', 'pin_hash' => '2468', 'roles' => ['acopiador'], 'zona_id' => Zona::factory()->create()->id]);
        $token = fn (Usuario $usuario) => $this->postJson('/api/movil/sesion', ['username' => $usuario->username, 'pin' => '2468'])->assertOk()->json('token');
        $url = '/api/movil/proveedores/'.$proveedor->codigo.'/ubicacion';

        $this->withToken($token($ajeno))->putJson($url, ['latitud' => -15.61, 'longitud' => -69.97, 'precisionM' => 8])->assertForbidden();
        $tokenAcopiador = $token($acopiador);
        $this->withToken($tokenAcopiador)->putJson($url, ['latitud' => -15.61, 'longitud' => -69.97, 'precisionM' => 250])->assertUnprocessable();
        $this->withToken($tokenAcopiador)->putJson($url, ['latitud' => -15.61, 'longitud' => -69.97, 'precisionM' => 8])->assertOk();

        $proveedor->refresh();
        $this->assertSame('celular', $proveedor->ubicacion_origen);
        $this->assertSame($acopiador->id, $proveedor->ubicacion_por);
        $this->assertEqualsWithDelta(-15.61, $ruta->fresh()->paradas[0]['latitud'], 0.000001);
        $this->assertSame(2, $ruta->fresh()->mapa_version);
    }

    public function test_el_resumen_muestra_lo_pendiente_por_zona(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'FAON-MARKAPAJO', 'activo' => true]);
        Proveedor::factory()->create(['zona_id' => $zona->id, 'nombres' => 'Juana Mamani Ccama', 'latitud' => null]);
        Ruta::query()->create(['codigo' => 'R-SIN', 'nombre' => 'Ruta sin acopiador', 'zona_id' => $zona->id, 'activo' => true, 'mapa_version' => 1, 'paradas' => []]);

        $this->get(route('admin.organizacion.index'))->assertOk()
            ->assertSee('FAON-MARKAPAJO')->assertSee('Juana Mamani Ccama')->assertSee('Ruta sin acopiador')
            ->assertViewHas('pendientes', fn (array $pendientes) => $pendientes['sinUbicacion']->count() === 1
                && $pendientes['sinRuta']->count() === 1 && $pendientes['rutasSinAcopiador']->count() === 1);
    }

    public function test_el_traslado_exige_3_dias_de_aviso_y_se_aplica_en_su_fecha(): void
    {
        $origen = Zona::factory()->create(['activo' => true]);
        $destino = Zona::factory()->create(['activo' => true]);
        $proveedor = Proveedor::factory()->create(['zona_id' => $origen->id, 'latitud' => -15.6, 'longitud' => -69.9]);
        $rutaOrigen = Ruta::query()->create(['codigo' => 'R-ORI', 'nombre' => 'Origen', 'zona_id' => $origen->id, 'activo' => true, 'mapa_version' => 1,
            'paradas' => [['nombre' => 'P', 'proveedor_id' => $proveedor->id, 'latitud' => -15.6, 'longitud' => -69.9, 'referencia' => '']]]);
        $rutaDestino = Ruta::query()->create(['codigo' => 'R-DES', 'nombre' => 'Destino', 'zona_id' => $destino->id, 'activo' => true, 'mapa_version' => 1, 'paradas' => []]);
        $solicitud = SolicitudTraslado::query()->create(['uuid_movil' => 'tras-1', 'proveedor_id' => $proveedor->id,
            'zona_destino_id' => $destino->id, 'motivo' => 'Me mudé a la otra comunidad', 'estado' => 'PENDIENTE']);
        $resolver = route('admin.organizacion.traslados.resolver', $solicitud);

        $this->patch($resolver, ['estado' => 'APROBADA', 'respuesta' => 'Autorizado', 'fecha_efectiva' => now('America/Lima')->addDay()->toDateString()])
            ->assertSessionHasErrors('fecha_efectiva');
        $this->patch($resolver, ['estado' => 'APROBADA', 'respuesta' => 'Autorizado', 'fecha_efectiva' => now('America/Lima')->addDays(5)->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertSame($origen->id, $proveedor->fresh()->zona_id);
        $this->get(route('admin.organizacion.traslados', ['estado' => 'programado']))->assertOk()->assertSee('Me mudé a la otra comunidad');

        $this->travel(5)->days();
        $this->get(route('admin.organizacion.index'))->assertOk();
        $this->assertSame($destino->id, $proveedor->fresh()->zona_id);
        $this->assertSame([], $rutaOrigen->fresh()->paradas);
        $this->assertSame($proveedor->id, $rutaDestino->fresh()->paradas[0]['proveedor_id']);
    }

    public function test_el_celular_recibe_el_orden_de_la_ruta_de_su_acopiador(): void
    {
        $zona = Zona::factory()->create(['activo' => true]);
        $otraZona = Zona::factory()->create(['activo' => true]);
        $primero = Proveedor::factory()->create(['zona_id' => $zona->id, 'nombres' => 'Zenon Quispe']);
        $segundo = Proveedor::factory()->create(['zona_id' => $zona->id, 'nombres' => 'Ana Ccama']);
        $sinRuta = Proveedor::factory()->create(['zona_id' => $zona->id]);
        $trasladado = Proveedor::factory()->create(['zona_id' => $otraZona->id]);
        $acopiador = Usuario::factory()->create(['username' => 'acop_orden', 'pin_hash' => '2468', 'roles' => ['acopiador'], 'zona_id' => $zona->id]);
        $parada = fn (Proveedor $proveedor) => ['nombre' => $proveedor->nombres, 'proveedor_id' => $proveedor->id, 'latitud' => -15.6, 'longitud' => -69.9, 'referencia' => ''];
        Ruta::query()->create(['codigo' => 'R-OTRA', 'nombre' => 'Ruta de otro acopiador', 'zona_id' => $zona->id, 'activo' => true, 'mapa_version' => 1,
            'paradas' => [$parada($segundo), $parada($primero)]]);
        Ruta::query()->create(['codigo' => 'R-MIA', 'nombre' => 'Mi ruta', 'zona_id' => $zona->id, 'activo' => true, 'mapa_version' => 1, 'acopiador_id' => $acopiador->id,
            'paradas' => [$parada($primero), $parada($trasladado), $parada($segundo)]]);
        $token = $this->postJson('/api/movil/sesion', ['username' => 'acop_orden', 'pin' => '2468'])->assertOk()->json('token');

        $proveedores = collect($this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json('proveedores'))->keyBy('id');

        $this->assertSame(1, $proveedores[$primero->id]['ordenRuta']);
        $this->assertSame(3, $proveedores[$segundo->id]['ordenRuta']);
        $this->assertNull($proveedores[$sinRuta->id]['ordenRuta']);
        $this->assertNull($proveedores[$trasladado->id]['ordenRuta']);
    }

    public function test_la_copia_del_celular_aplica_el_traslado_vencido_y_muestra_su_fecha(): void
    {
        $origen = Zona::factory()->create(['activo' => true]);
        $destino = Zona::factory()->create(['activo' => true]);
        $cuenta = Usuario::factory()->create(['username' => 'prov_traslado', 'pin_hash' => '2468', 'roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['zona_id' => $origen->id, 'usuario_id' => $cuenta->id]);
        $solicitud = SolicitudTraslado::query()->create(['uuid_movil' => 'tras-copia', 'proveedor_id' => $proveedor->id,
            'zona_destino_id' => $destino->id, 'motivo' => 'Cambio de comunidad', 'estado' => 'PENDIENTE']);
        $fecha = now('America/Lima')->addDays(4)->toDateString();
        $this->patch(route('admin.organizacion.traslados.resolver', $solicitud), ['estado' => 'APROBADA', 'respuesta' => 'Autorizado', 'fecha_efectiva' => $fecha])
            ->assertSessionHasNoErrors();
        $token = $this->postJson('/api/movil/sesion', ['username' => 'prov_traslado', 'pin' => '2468'])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonPath('proveedores.0.zonaId', $origen->id)
            ->assertJsonPath('solicitudes.0.fechaEfectiva', $fecha)
            ->assertJsonPath('solicitudes.0.aplicadoEn', null);

        $this->travel(4)->days();
        $token = $this->postJson('/api/movil/sesion', ['username' => 'prov_traslado', 'pin' => '2468'])->assertOk()->json('token');
        $respuesta = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()
            ->assertJsonPath('proveedores.0.zonaId', $destino->id);
        $this->assertNotNull($respuesta->json('solicitudes.0.aplicadoEn'));
    }
}
