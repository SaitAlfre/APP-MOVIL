<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrganizacionCalidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_cinco_destinos_del_menu_se_renderizan(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        foreach (['resumen', 'personal', 'planes', 'index', 'casos'] as $pagina) {
            $this->get(route('admin.calidad.'.$pagina))->assertOk()->assertSee('Personal de calidad')->assertSee('Análisis recibidos');
        }
    }

    public function test_programar_control_valida_zona_y_evita_duplicados(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $tecnico = Usuario::factory()->create(['roles' => ['calidad']]);
        $proveedor = Proveedor::factory()->create();
        $datos = ['fecha' => now('America/Lima')->toDateString(), 'usuario_id' => $tecnico->id, 'zona_id' => $proveedor->zona_id, 'proveedor_id' => $proveedor->id, 'lugar' => 'campo', 'motivo' => 'sorpresa'];
        $this->post(route('admin.calidad.planes.store'), $datos)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('planes_control_calidad', ['usuario_id' => $tecnico->id, 'estado' => 'programado']);
        $this->assertDatabaseCount('comunicados', 0);
        $this->post(route('admin.calidad.planes.store'), $datos)->assertSessionHasErrors('proveedor_id');
        $otro = Proveedor::factory()->create();
        $this->post(route('admin.calidad.planes.store'), [...$datos, 'proveedor_id' => $otro->id])->assertSessionHasErrors('proveedor_id');
    }

    public function test_plan_reservado_solo_muestra_las_asignaciones_del_tecnico(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $uno = Usuario::factory()->create(['roles' => ['calidad']]);
        $dos = Usuario::factory()->create(['roles' => ['calidad']]);
        $proveedor = Proveedor::factory()->create(['nombres' => 'Proveedor secreto de prueba']);
        $this->actingAs($admin, 'operador')->post(route('admin.calidad.planes.store'), ['fecha' => now('America/Lima')->toDateString(), 'usuario_id' => $uno->id, 'zona_id' => $proveedor->zona_id, 'proveedor_id' => $proveedor->id, 'lugar' => 'campo', 'motivo' => 'sorpresa'])->assertSessionHasNoErrors();
        $this->actingAs($uno, 'operador')->get(route('admin.calidad.planes'))->assertOk()->assertSee('Proveedor secreto de prueba');
        $this->actingAs($dos, 'operador')->get(route('admin.calidad.planes'))->assertOk()->assertDontSee('Proveedor secreto de prueba');
        $this->post(route('admin.calidad.planes.store'), [])->assertForbidden();
        $mixto = Usuario::factory()->create(['roles' => ['calidad', 'acopiador']]);
        $this->actingAs($mixto, 'operador')->get(route('admin.calidad.planes'))->assertForbidden();
        $this->get(route('admin.calidad.index'))->assertDontSee('title="Plan de controles"', false);
    }

    public function test_cancelacion_requiere_motivo_y_conserva_responsable(): void
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $tecnico = Usuario::factory()->create(['roles' => ['calidad']]);
        $proveedor = Proveedor::factory()->create();
        $this->actingAs($admin, 'operador')->post(route('admin.calidad.planes.store'), ['fecha' => now('America/Lima')->toDateString(), 'usuario_id' => $tecnico->id, 'zona_id' => $proveedor->zona_id, 'proveedor_id' => $proveedor->id, 'lugar' => 'campo', 'motivo' => 'sorpresa'])->assertSessionHasNoErrors();
        $id = DB::table('planes_control_calidad')->value('id');
        $this->post(route('admin.calidad.planes.update', $id), ['estado' => 'cancelado'])->assertSessionHasErrors('observacion');
        $this->post(route('admin.calidad.planes.update', $id), ['estado' => 'cancelado', 'observacion' => 'Acceso cerrado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('planes_control_calidad', ['id' => $id, 'estado' => 'cancelado', 'actualizado_por' => $admin->id]);
    }
}
