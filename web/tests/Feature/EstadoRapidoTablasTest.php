<?php

namespace Tests\Feature;

use App\Domain\Proveedores\EstadoProveedor;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** La columna Estado de Usuarios y Proveedores permite cambiar el estado con un menú y una confirmación. */
class EstadoRapidoTablasTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    private function xpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new \DOMXPath($dom);
    }

    private function comoRol(array $permisos): void
    {
        $rol = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Rol '.uniqid(), 'base' => null, 'activo' => true, 'permisos' => json_encode($permisos), 'campos' => '{}']);
        $this->actingAs(Usuario::factory()->create(['roles' => [], 'perfil_rol_id' => $rol]), 'operador');
    }

    public function test_usuarios_muestra_menu_de_estado_segun_la_situacion_y_conserva_el_menu_de_acciones(): void
    {
        $this->comoAdmin();
        $activa = Usuario::factory()->create(['nombres' => 'Cuenta activa QA']);
        $suspendida = Usuario::factory()->create(['nombres' => 'Cuenta suspendida QA', 'bloqueado_manualmente' => true]);
        $bloqueada = Usuario::factory()->create(['nombres' => 'Cuenta con intentos QA', 'bloqueado_hasta' => now()->addMinutes(10)]);

        $html = $this->get(route('admin.usuarios.index'))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Estado de Cuenta activa QA: Activo. Cambiar estado"', $html);
        $this->assertStringContainsString('aria-label="Estado de Cuenta suspendida QA: Suspendido. Cambiar estado"', $html);
        $this->assertStringNotContainsString('Estado de Cuenta con intentos QA', $html);
        // Cada opción del menú de estado y del menú ⋮ abre el mismo modal de acceso.
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-modal-open="acceso-'.$activa->id.'"'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-modal-open="acceso-'.$suspendida->id.'"'));
        $this->assertStringContainsString('id="acceso-'.$bloqueada->id.'"', $html);
        $this->assertStringContainsString('Cierra sus sesiones web y móvil.', $html);
        $this->assertStringContainsString('Permite volver a iniciar sesión.', $html);
    }

    public function test_usuarios_sin_permiso_de_gestion_ven_solo_la_etiqueta(): void
    {
        Usuario::factory()->create(['nombres' => 'Cuenta ajena QA']);
        $this->comoRol(['usuarios' => ['ver']]);
        $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Cuenta ajena QA')->assertDontSee('Cambiar estado');
    }

    public function test_proveedores_ofrece_solo_transiciones_validas_con_su_confirmacion(): void
    {
        $this->comoAdmin();
        $activo = Proveedor::factory()->create(['nombres' => 'Activo QA', 'estado' => 'activo']);
        $suspendido = Proveedor::factory()->create(['nombres' => 'Suspendido QA', 'estado' => 'suspendido']);
        $retirado = Proveedor::factory()->create(['nombres' => 'Retirado QA', 'estado' => 'retirado']);
        $html = $this->get(route('admin.proveedores.index'))->assertOk()->getContent();

        $xpath = $this->xpath($html);
        foreach ([[$activo, ['suspendido', 'retirado']], [$suspendido, ['activo', 'retirado']], [$retirado, ['activo']]] as [$proveedor, $permitidos]) {
            $opciones = $xpath->query('//*[@data-proveedores-resultados]//button[@data-url="'.route('admin.proveedores.estado', $proveedor->id).'"]/@data-estado-rapido');
            $this->assertEqualsCanonicalizing($permitidos, array_map(fn ($nodo) => $nodo->value, iterator_to_array($opciones)));
        }
        // Un solo modal compartido, fuera de los resultados que se reemplazan al filtrar.
        $this->assertSame(0, $xpath->query('//*[@data-proveedores-resultados]//dialog')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="proveedor-estado-modal"]')->length);
        $this->assertStringContainsString('¿Confirmar suspensión?', $html);
        $this->assertStringContainsString('Su información histórica se conservará. ¿Confirmar retiro?', $html);
    }

    public function test_proveedores_respeta_editar_y_eliminar(): void
    {
        $proveedor = Proveedor::factory()->create(['nombres' => 'Permisos QA']);
        $this->comoRol(['proveedores' => ['ver', 'editar']]);
        $this->get(route('admin.proveedores.index'))->assertOk()->assertSee('data-estado-rapido="suspendido"', false)->assertDontSee('data-estado-rapido="retirado"', false);
        $this->comoRol(['proveedores' => ['ver']]);
        $this->get(route('admin.proveedores.index'))->assertOk()->assertSee('Permisos QA')->assertDontSee('data-estado-rapido=', false)->assertDontSee('Cambiar estado');
    }

    public function test_confirmar_desde_la_tabla_vuelve_a_los_mismos_filtros_y_enlaza_la_cuenta(): void
    {
        $this->comoAdmin();
        $cuenta = Usuario::factory()->create(['roles' => ['proveedor']]);
        $proveedor = Proveedor::factory()->create(['usuario_id' => $cuenta->id, 'dni' => $cuenta->dni]);
        $this->get(route('admin.proveedores.index'))->assertSee('data-cuenta="'.$cuenta->username.'"', false);

        $origen = route('admin.proveedores.index', ['estado' => 'activo', 'q' => 'QA']);
        $this->from($origen)->patch(route('admin.proveedores.estado', $proveedor->id), ['estado' => 'suspendido', 'motivo_estado' => 'Desde la tabla'])
            ->assertRedirect($origen)->assertSessionHas('estado', fn (string $m) => str_contains($m, 'también quedó Suspendida'));
        $this->assertSame(EstadoProveedor::Suspendido, $proveedor->fresh()->estado);
        $this->assertTrue($cuenta->fresh()->bloqueado_manualmente);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'proveedor', 'entidad_id' => $proveedor->id, 'motivo' => 'Desde la tabla']);
    }
}
