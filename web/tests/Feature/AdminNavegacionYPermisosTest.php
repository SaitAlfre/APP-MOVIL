<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El menú lateral y la barra superior se arman según los permisos del rol autenticado.
 * Ocultar un enlace nunca reemplaza la validación del servidor: ambas cosas se comprueban.
 */
class AdminNavegacionYPermisosTest extends TestCase
{
    use RefreshDatabase;

    /** @param list<string> $roles */
    private function como(array $roles): Usuario
    {
        $usuario = Usuario::factory()->create(['roles' => $roles]);
        $this->actingAs($usuario, 'operador');

        return $usuario;
    }

    public function test_el_admin_ve_todos_los_grupos_del_menu(): void
    {
        $this->como(['admin']);

        $respuesta = $this->get('/admin/dashboard');

        foreach (['Acopio', 'Proveedores', 'Calidad', 'Liquidaciones y pagos', 'Planta', 'Organización del acopio', 'Administración'] as $grupo) {
            $respuesta->assertSee($grupo, false);
        }
        foreach (['Centro operativo', 'Usuarios y accesos', 'Proveedores', 'Recepción en planta', 'Configuración', 'Rutas de acopio'] as $enlace) {
            $respuesta->assertSee($enlace, false);
        }

        $respuesta->assertDontSee(route('admin.design-system.index'), false);
    }

    public function test_produccion_retirado_no_accede_a_planta(): void
    {
        $cuenta = $this->como(['produccion']);
        foreach (['/admin/dashboard', '/admin/produccion', '/admin/inventario'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->assertSame(['produccion'], $cuenta->fresh()->roles);
    }

    public function test_liquidaciones_retirado_no_accede_a_finanzas(): void
    {
        $this->como(['liquidaciones']);
        $this->get('/admin/liquidaciones')->assertForbidden();
        $this->get('/admin/inventario')->assertForbidden();
    }

    public function test_consulta_retirado_no_puede_consultar_ni_modificar_catalogos(): void
    {
        $this->como(['consulta']);
        $this->get('/admin/proveedores')->assertForbidden();
        $this->get('/admin/proveedores/nuevo')->assertForbidden();
        $this->post('/admin/proveedores', [])->assertForbidden();
    }

    public function test_calidad_y_acopiador_acceden_solo_a_sus_pantallas(): void
    {
        foreach (['calidad' => '/admin/calidad/nuevo', 'acopiador' => '/admin/mapa-rutas'] as $rol => $permitida) {
            $this->como([$rol]);
            $this->get($permitida)->assertOk();
            $this->get('/admin/usuarios')->assertForbidden();
            $this->get('/admin/produccion/productos/nuevo')->assertForbidden();
            $this->get('/admin/liquidaciones/nueva')->assertForbidden();
        }
    }

    public function test_dos_roles_oficiales_internos_suman_solo_sus_permisos(): void
    {
        $this->como(['calidad', 'acopiador']);
        $this->get('/admin/calidad/nuevo')->assertOk();
        $this->get('/admin/mapa-rutas')->assertOk();
        $this->get('/admin/liquidaciones/nueva')->assertForbidden();
        $this->get('/admin/usuarios')->assertForbidden();
    }

    public function test_la_campana_de_comunicados_cuenta_solo_los_publicados_de_la_ultima_semana(): void
    {
        $admin = $this->como(['admin']);

        Comunicado::query()->create([
            'codigo' => 'COM-001', 'titulo' => 'Reciente', 'contenido' => 'Texto',
            'audiencia' => 'todos', 'estado' => 'publicado', 'publicado_en' => now()->subDay(),
            'autor_id' => $admin->id,
        ]);
        Comunicado::query()->create([
            'codigo' => 'COM-002', 'titulo' => 'Antiguo', 'contenido' => 'Texto',
            'audiencia' => 'todos', 'estado' => 'publicado', 'publicado_en' => now()->subMonth(),
            'autor_id' => $admin->id,
        ]);
        Comunicado::query()->create([
            'codigo' => 'COM-003', 'titulo' => 'Borrador', 'contenido' => 'Texto',
            'audiencia' => 'todos', 'estado' => 'borrador', 'publicado_en' => null,
            'autor_id' => $admin->id,
        ]);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('1 publicados esta semana', false);
    }

    public function test_sin_comunicados_recientes_la_campana_no_muestra_contador(): void
    {
        $this->como(['admin']);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('aria-label="Comunicados"', false);
        $respuesta->assertDontSee('publicados esta semana');
    }

    public function test_el_panel_ofrece_saltar_al_contenido_principal(): void
    {
        $this->como(['admin']);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertSee('Saltar al contenido principal');
        $respuesta->assertSee('id="contenido-principal"', false);
    }

    public function test_una_cuenta_desactivada_pierde_el_acceso_aunque_tenga_sesion(): void
    {
        $usuario = $this->como(['admin']);
        $this->get('/admin/dashboard')->assertOk();

        $usuario->update(['activo' => false]);

        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }
}
