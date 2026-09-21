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

        foreach (['Principal', 'Administración', 'Operaciones', 'Finanzas', 'Planta', 'Comunicación', 'Análisis', 'Sistema'] as $grupo) {
            $respuesta->assertSee($grupo, false);
        }
        foreach (['Centro operativo', 'Usuarios y roles', 'Proveedores', 'Recepción en planta', 'Design System'] as $enlace) {
            $respuesta->assertSee($enlace, false);
        }
    }

    public function test_el_rol_produccion_ve_planta_pero_no_administracion(): void
    {
        $this->como(['produccion']);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('Producción', false);
        $respuesta->assertSee('Inventario');
        $respuesta->assertDontSee('Usuarios y roles');
        $respuesta->assertDontSee('Zonas, rutas y vehículos', false);
        $respuesta->assertDontSee('Auditoría', false);
    }

    public function test_el_rol_liquidaciones_ve_finanzas_pero_no_inventario(): void
    {
        $this->como(['liquidaciones']);

        $respuesta = $this->get('/admin/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('Liquidaciones y pagos');
        $respuesta->assertDontSee('Inventario');
        $respuesta->assertDontSee('Importaciones');
    }

    public function test_el_rol_consulta_ve_casi_todo_el_menu_pero_no_gestiona(): void
    {
        $this->como(['consulta']);

        $respuesta = $this->get('/admin/proveedores');

        $respuesta->assertOk();
        $respuesta->assertSee('Proveedores');
        $respuesta->assertSee('Auditoría', false);
        $respuesta->assertDontSee('Usuarios y roles');

        // El servidor bloquea la gestión aunque el botón no se muestre.
        $this->get('/admin/proveedores/nuevo')->assertForbidden();
        $this->post('/admin/proveedores', [])->assertForbidden();
    }

    public function test_cada_rol_solo_puede_gestionar_su_propio_modulo(): void
    {
        $intentos = [
            // rol => [ruta permitida de gestión, ruta prohibida de gestión]
            'recepcion' => ['/admin/entregas', '/admin/calidad/nuevo'],
            'calidad' => ['/admin/calidad/nuevo', '/admin/produccion/productos/nuevo'],
            'produccion' => ['/admin/produccion/productos/nuevo', '/admin/liquidaciones/nueva'],
            'liquidaciones' => ['/admin/liquidaciones/nueva', '/admin/produccion/productos/nuevo'],
        ];

        foreach ($intentos as $rol => [$permitida, $prohibida]) {
            $this->como([$rol]);

            $this->get($permitida)->assertOk();
            $this->get($prohibida)->assertForbidden();
        }
    }

    public function test_un_usuario_con_dos_roles_suma_los_permisos_de_ambos(): void
    {
        $this->como(['calidad', 'liquidaciones']);

        $this->get('/admin/calidad/nuevo')->assertOk();
        $this->get('/admin/liquidaciones/nueva')->assertOk();
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
