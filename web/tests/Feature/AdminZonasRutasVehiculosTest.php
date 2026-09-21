<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pantalla unificada de zonas, rutas y vehículos: cada catálogo vive en su pestaña
 * para no mezclar datos distintos en una misma tabla.
 */
class AdminZonasRutasVehiculosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    public function test_cada_pestana_muestra_solo_su_catalogo(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'Zona Este']);
        Vehiculo::factory()->create(['nombre' => 'Camioneta 3', 'placa' => 'ZZZ-999']);
        Ruta::query()->create(['codigo' => 'R-07', 'nombre' => 'Ruta 07', 'zona_id' => $zona->id, 'activo' => true]);

        $zonas = $this->get('/admin/zonas-vehiculos');
        $zonas->assertOk();
        $zonas->assertViewHas('pestana', 'zonas');
        $zonas->assertSee('Zona Este');
        $zonas->assertDontSee('ZZZ-999');

        $rutas = $this->get('/admin/zonas-vehiculos?tab=rutas');
        $rutas->assertViewHas('pestana', 'rutas');
        $rutas->assertSee('Ruta 07');
        $rutas->assertDontSee('ZZZ-999');

        $vehiculos = $this->get('/admin/zonas-vehiculos?tab=vehiculos');
        $vehiculos->assertViewHas('pestana', 'vehiculos');
        $vehiculos->assertSee('ZZZ-999');
    }

    public function test_una_pestana_desconocida_cae_en_zonas(): void
    {
        $this->get('/admin/zonas-vehiculos?tab=inventada')->assertOk()->assertViewHas('pestana', 'zonas');
    }

    public function test_el_listado_de_zonas_cuenta_proveedores_activos_y_rutas(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'Zona Norte']);
        Proveedor::factory()->create(['zona_id' => $zona->id, 'estado' => 'activo']);
        Proveedor::factory()->create(['zona_id' => $zona->id, 'estado' => 'activo']);
        Proveedor::factory()->create(['zona_id' => $zona->id, 'estado' => 'retirado']);
        Ruta::query()->create(['codigo' => 'R-01', 'nombre' => 'Ruta 01', 'zona_id' => $zona->id, 'activo' => true]);

        $respuesta = $this->get('/admin/zonas-vehiculos');

        $respuesta->assertOk();
        $respuesta->assertViewHas('filasZonas', function ($filas) {
            $fila = collect($filas)->first();

            return $fila['proveedores'] === 2 && $fila['rutas'] === 1;
        });
    }

    public function test_un_admin_puede_crear_una_ruta(): void
    {
        $zona = Zona::factory()->create();

        $respuesta = $this->post('/admin/zonas-vehiculos/rutas', [
            'codigo' => 'R-06',
            'nombre' => 'Ruta 06',
            'zona_id' => $zona->id,
        ]);

        $respuesta->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rutas', ['codigo' => 'R-06', 'nombre' => 'Ruta 06', 'zona_id' => $zona->id, 'activo' => true]);
    }

    public function test_no_se_puede_repetir_el_codigo_de_una_ruta(): void
    {
        $zona = Zona::factory()->create();
        Ruta::query()->create(['codigo' => 'R-06', 'nombre' => 'Ruta 06', 'zona_id' => $zona->id, 'activo' => true]);

        $respuesta = $this->post('/admin/zonas-vehiculos/rutas', [
            'codigo' => 'R-06',
            'nombre' => 'Otra ruta',
            'zona_id' => $zona->id,
        ]);

        $respuesta->assertSessionHasErrors('codigo');
        $this->assertSame(1, Ruta::query()->count());
    }

    public function test_una_ruta_necesita_una_zona_existente(): void
    {
        $respuesta = $this->post('/admin/zonas-vehiculos/rutas', [
            'codigo' => 'R-08',
            'nombre' => 'Ruta 08',
            'zona_id' => 999999,
        ]);

        $respuesta->assertSessionHasErrors('zona_id');
        $this->assertDatabaseCount('rutas', 0);
    }

    public function test_un_admin_puede_editar_y_desactivar_una_ruta(): void
    {
        $zona = Zona::factory()->create();
        $otraZona = Zona::factory()->create();
        $ruta = Ruta::query()->create(['codigo' => 'R-09', 'nombre' => 'Ruta 09', 'zona_id' => $zona->id, 'activo' => true]);

        $respuesta = $this->put("/admin/zonas-vehiculos/rutas/{$ruta->id}", [
            'codigo' => 'R-09',
            'nombre' => 'Ruta 09 renombrada',
            'zona_id' => $otraZona->id,
            'activo' => '0',
        ]);

        $respuesta->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rutas', [
            'id' => $ruta->id,
            'nombre' => 'Ruta 09 renombrada',
            'zona_id' => $otraZona->id,
            'activo' => false,
        ]);
    }

    public function test_editar_una_ruta_puede_conservar_su_propio_codigo(): void
    {
        $zona = Zona::factory()->create();
        $ruta = Ruta::query()->create(['codigo' => 'R-10', 'nombre' => 'Ruta 10', 'zona_id' => $zona->id, 'activo' => true]);

        $respuesta = $this->put("/admin/zonas-vehiculos/rutas/{$ruta->id}", [
            'codigo' => 'R-10',
            'nombre' => 'Ruta 10',
            'zona_id' => $zona->id,
            'activo' => '1',
        ]);

        $respuesta->assertSessionHasNoErrors();
    }

    public function test_un_rol_de_consulta_ve_los_catalogos_pero_no_puede_crear_rutas(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['consulta']]), 'operador');
        $zona = Zona::factory()->create(['nombre' => 'Zona Este']);

        $this->get('/admin/zonas-vehiculos')->assertOk()->assertSee('Zona Este')->assertDontSee('Nueva zona');
        $this->post('/admin/zonas-vehiculos/rutas', ['codigo' => 'R-11', 'nombre' => 'Ruta 11', 'zona_id' => $zona->id])
            ->assertForbidden();
    }

    public function test_los_catalogos_vacios_explican_para_que_sirve_cada_uno(): void
    {
        $this->get('/admin/zonas-vehiculos')->assertSee('Aún no hay zonas registradas');
        $this->get('/admin/zonas-vehiculos?tab=rutas')->assertSee('Aún no hay rutas registradas');
        $this->get('/admin/zonas-vehiculos?tab=vehiculos')->assertSee('Aún no hay vehículos registrados');
    }
}
