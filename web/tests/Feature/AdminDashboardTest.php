<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    public function test_el_dashboard_calcula_los_indicadores_principales(): void
    {
        $this->comoAdmin();
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create();

        // Entrega aprobada de 100 L con recepción registrada (95 L medidos -> 5 L de merma).
        $jornada1 = Jornada::factory()->create(['vehiculo_id' => $vehiculo->id, 'fecha' => $hoy]);
        $entrega1 = Entrega::factory()->create([
            'jornada_id' => $jornada1->id, 'vehiculo_id' => $vehiculo->id,
            'litros' => 100, 'registrado_en' => $hoy.' 08:00:00', 'anulada' => false,
        ]);
        ControlCalidad::query()->create([
            'entrega_id' => $entrega1->id, 'usuario_id' => $entrega1->usuario_id, 'resultado' => 'aprobado', 'evaluado_en' => now(),
        ]);
        $this->post(route('admin.recepcion.llegada.store', $jornada1->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 95, 'motivo_diferencia' => 'Derrame durante el traslado',
        ])->assertSessionHasNoErrors();

        // Entrega de 20 L todavía sin control de Calidad.
        $jornada2 = Jornada::factory()->create(['vehiculo_id' => $vehiculo->id, 'fecha' => $hoy]);
        Entrega::factory()->create([
            'jornada_id' => $jornada2->id, 'vehiculo_id' => $vehiculo->id,
            'litros' => 20, 'registrado_en' => $hoy.' 09:00:00', 'anulada' => false,
        ]);

        // Lote en proceso: asigna 40 L de los 95 disponibles tras la merma.
        $producto = Producto::factory()->create(['litros_por_unidad' => 10]);
        $this->post('/admin/produccion/producir', ['fecha' => $hoy, 'producto_id' => $producto->id, 'litros_asignados' => 40])
            ->assertSessionHasNoErrors();
        $lote = LoteProduccion::query()->firstOrFail();
        $this->patch(route('admin.produccion.lotes.iniciar', $lote->id))->assertSessionHasNoErrors();

        // Liquidación pendiente.
        $proveedor = Proveedor::factory()->create();
        Liquidacion::query()->create([
            'proveedor_id' => $proveedor->id, 'periodo_inicio' => $hoy, 'periodo_fin' => $hoy,
            'litros_totales' => 100, 'precio_litro' => 1.5, 'monto_total' => 150,
            'estado' => 'pendiente', 'generada_en' => now(),
        ]);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('indicadores', function (array $indicadores) {
            return $indicadores['litros_recolectados'] === 120.0
                && $indicadores['litros_recibidos'] === 95.0
                && $indicadores['merma_litros'] === 5.0
                && $indicadores['merma_porcentaje'] === 5.0
                && $indicadores['pendiente_calidad_litros'] === 20.0
                && $indicadores['pendiente_calidad_entregas'] === 1
                && $indicadores['habilitado_litros'] === 95.0
                && $indicadores['disponible_litros'] === 55.0
                && $indicadores['consumido_produccion_litros'] === 0.0
                && $indicadores['lotes_en_proceso'] === 1
                && $indicadores['lotes_finalizados'] === 0
                && $indicadores['liquidaciones_pendientes'] === 1;
        });
        $response->assertViewHas('alertas', fn (array $alertas) => $alertas['calidad_pendientes'] === 1 && $alertas['liquidaciones_pendientes'] === 1);
        $response->assertSee('Alertas recientes');
        $response->assertSee('sin registrar la recepción', false);
        $response->assertSee('Liquidaciones pendientes');
    }

    public function test_el_dashboard_no_falla_con_denominadores_en_cero_ni_sin_datos(): void
    {
        $this->comoAdmin();

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Sin pendientes:', false);
        $response->assertViewHas('indicadores', function (array $indicadores) {
            return $indicadores['merma_porcentaje'] === null
                && $indicadores['litros_recolectados_variacion'] === null
                && $indicadores['litros_recolectados'] === 0.0
                && $indicadores['disponible_litros'] === 0.0;
        });
    }

    public function test_una_merma_sobre_el_umbral_configurado_aparece_como_alerta(): void
    {
        config(['ecolecta.umbral_merma_porcentaje' => 5]);
        $this->comoAdmin();
        $hoy = now()->toDateString();
        $vehiculo = Vehiculo::factory()->create(['placa' => 'MER-001']);

        $jornada = Jornada::factory()->create(['vehiculo_id' => $vehiculo->id, 'fecha' => $hoy]);
        $entrega = Entrega::factory()->create([
            'jornada_id' => $jornada->id, 'vehiculo_id' => $vehiculo->id,
            'litros' => 100, 'registrado_en' => $hoy.' 08:00:00', 'anulada' => false,
        ]);
        ControlCalidad::query()->create([
            'entrega_id' => $entrega->id, 'usuario_id' => $entrega->usuario_id, 'resultado' => 'aprobado', 'evaluado_en' => now(),
        ]);
        // 20 % de merma, por encima del umbral configurado (5 %).
        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 80, 'motivo_diferencia' => 'Derrame grande',
        ])->assertSessionHasNoErrors();

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('MER-001');
        $response->assertViewHas('alertas', fn (array $alertas) => count($alertas['mermas_sobre_umbral']) === 1);
    }

    public function test_un_proveedor_no_puede_acceder_al_dashboard(): void
    {
        $proveedor = Usuario::factory()->create(['roles' => ['proveedor']]);
        $this->actingAs($proveedor, 'operador');

        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_iniciar_sesion_redirige_al_dashboard(): void
    {
        $admin = Usuario::factory()->create(['pin_hash' => '1234', 'roles' => ['admin']]);

        $this->post('/login', ['username' => $admin->username, 'pin' => '1234'])
            ->assertRedirect(route('admin.dashboard.index'));
    }
}
