<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent as M;
use Database\Seeders\EscenarioPeruDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EscenarioPeruDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_escenario_completo_conserva_admin_y_no_duplica_operaciones(): void
    {
        Storage::fake('local');
        $admin = M\Usuario::factory()->create(['username' => 'admin', 'roles' => ['admin']]);
        $pin = $admin->pin_hash;
        $this->seed(EscenarioPeruDemoSeeder::class);
        $this->assertDatabaseCount('zonas', 4);
        $this->assertDatabaseCount('proveedores', 12);
        $this->assertDatabaseCount('entregas', 84);
        $this->assertDatabaseCount('liquidaciones', 12);
        $this->assertDatabaseCount('receta_ingredientes', 9);
        $this->assertDatabaseCount('lotes_produccion', 3);
        $this->assertEquals(27, M\Producto::sum('existencia'));
        $this->assertEquals(M\Producto::sum('existencia'), M\MovimientoProducto::sum('cantidad'));
        $this->assertSame($pin, $admin->fresh()->pin_hash);
        $auditorias = M\Auditoria::count();
        $this->seed(EscenarioPeruDemoSeeder::class);
        $this->assertDatabaseCount('entregas', 84);
        $this->assertEquals(27, M\Producto::sum('existencia'));
        $this->assertSame($auditorias, M\Auditoria::count());
        foreach (M\Material::all() as $material) {
            $this->assertEquals($material->existencia, DB::table('movimientos_material')->where('material_id', $material->id)->sum('cantidad'));
        }
        $this->actingAs($admin, 'operador');
        foreach (['dashboard', 'usuarios', 'proveedores', 'acopiadores', 'zonas-vehiculos', 'recepcion', 'calidad', 'entregas', 'sanciones', 'produccion', 'produccion/producir', 'produccion/historial', 'produccion/productos', 'inventario', 'ventas', 'liquidaciones', 'comunicados', 'auditoria', 'reportes', 'importaciones', 'configuracion'] as $ruta) {
            $this->get('/admin/'.$ruta)->assertOk();
        }
    }
}
