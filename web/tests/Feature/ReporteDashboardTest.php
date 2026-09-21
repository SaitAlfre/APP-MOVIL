<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_dashboard_filtra_los_indicadores_y_el_detalle_por_zona(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        $zonaNorte = Zona::factory()->create(['nombre' => 'Zona Norte']);
        $zonaSur = Zona::factory()->create(['nombre' => 'Zona Sur']);
        $vehiculo = Vehiculo::factory()->create(['nombre' => 'Camión 1']);
        $proveedorNorte = Proveedor::factory()->create(['nombres' => 'Ana Norte', 'zona_id' => $zonaNorte->id]);
        $proveedorSur = Proveedor::factory()->create(['nombres' => 'Beto Sur', 'zona_id' => $zonaSur->id]);

        Entrega::factory()->create([
            'proveedor_id' => $proveedorNorte->id,
            'zona_id' => $zonaNorte->id,
            'vehiculo_id' => $vehiculo->id,
            'litros' => 40,
            'registrado_en' => now(),
        ]);
        Entrega::factory()->create([
            'proveedor_id' => $proveedorSur->id,
            'zona_id' => $zonaSur->id,
            'vehiculo_id' => $vehiculo->id,
            'litros' => 80,
            'registrado_en' => now(),
        ]);

        $response = $this->get(route('admin.reportes.index', [
            'desde' => now()->toDateString(),
            'hasta' => now()->toDateString(),
            'zona_id' => $zonaNorte->id,
        ]));

        $response->assertOk();
        $response->assertSee('Centro de análisis del acopio', false);
        $response->assertSee('40.0');
        $response->assertSee('Ana Norte');
        $response->assertDontSee('Beto Sur');
    }

    public function test_exporta_un_csv_compatible_con_excel_respetando_los_filtros(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        $zona = Zona::factory()->create(['nombre' => 'Zona Centro']);
        $proveedor = Proveedor::factory()->create([
            'codigo' => 'PRV-900',
            'nombres' => 'María Quispe',
            'zona_id' => $zona->id,
        ]);
        Entrega::factory()->create([
            'proveedor_id' => $proveedor->id,
            'zona_id' => $zona->id,
            'litros' => 32.5,
            'registrado_en' => now(),
        ]);

        $response = $this->get(route('admin.reportes.exportar', [
            'desde' => now()->toDateString(),
            'hasta' => now()->toDateString(),
            'zona_id' => $zona->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $contenido = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('PRV-900', $contenido);
        $this->assertStringContainsString('María Quispe', $contenido);
        $this->assertStringContainsString('32,50', $contenido);
    }
}
