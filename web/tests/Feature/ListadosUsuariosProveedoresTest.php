<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListadosUsuariosProveedoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    public function test_usuarios_y_proveedores_tienen_listados_separados_sin_perder_vinculos(): void
    {
        $cuenta = Usuario::factory()->create(['nombres' => 'Cuenta del productor', 'roles' => ['proveedor']]);
        $ficha = Proveedor::factory()->create(['nombres' => 'Ficha del productor', 'usuario_id' => $cuenta->id]);
        Proveedor::factory()->create(['nombres' => 'Productor sin cuenta']);
        $respuesta = $this->get(route('admin.usuarios.index'))->assertOk()->assertSee('Usuarios y accesos')->assertSee('Cuenta del productor')->assertDontSee('Productor sin cuenta')->assertViewHas('filas', fn ($filas) => $filas->total() === 2);
        $this->assertSame(1, substr_count($respuesta->getContent(), '<table '));
        $respuesta = $this->get(route('admin.proveedores.index'))->assertOk()->assertSee('Ficha del productor')->assertSee('Productor sin cuenta')->assertDontSee('Cuenta del productor')->assertViewHas('proveedores', fn ($filas) => $filas->total() === 2);
        $this->assertSame(1, substr_count($respuesta->getContent(), '<table '));
        $this->assertSame($cuenta->id, $ficha->fresh()->usuario_id);
    }

    public function test_busqueda_y_rol_de_cuentas_no_incluyen_fichas_sin_cuenta(): void
    {
        Proveedor::factory()->create(['nombres' => 'Proveedor sin acceso', 'dni' => '00000001']);
        Usuario::factory()->create(['nombres' => 'Tecnico Calidad', 'roles' => ['calidad']]);
        $this->get(route('admin.usuarios.index', ['rol' => 'calidad']))->assertOk()->assertSee('Tecnico Calidad')->assertDontSee('Proveedor sin acceso');
        $this->get(route('admin.usuarios.index', ['buscar' => '00000001']))->assertOk()->assertViewHas('filas', fn ($filas) => $filas->total() === 0);
        $this->get(route('admin.proveedores.index', ['q' => '00000001']))->assertOk()->assertSee('Proveedor sin acceso')->assertDontSee('Tecnico Calidad');
    }

    public function test_paginacion_independiente_conserva_filtros_y_tamano(): void
    {
        Usuario::factory()->count(23)->create(['nombres' => 'Registro cuenta']);
        Proveedor::factory()->count(23)->create(['nombres' => 'Registro proveedor']);
        $this->get(route('admin.usuarios.index', ['buscar' => 'Registro', 'por_pagina' => 10]))->assertOk()->assertViewHas('filas', fn ($filas) => $filas->total() === 23 && $filas->count() === 10 && $filas->lastPage() === 3 && str_contains($filas->url(2), 'buscar=Registro'));
        $this->get(route('admin.usuarios.index', ['buscar' => 'Registro', 'por_pagina' => 10, 'page' => 3]))->assertOk()->assertViewHas('filas', fn ($filas) => $filas->count() === 3);
        $this->get(route('admin.proveedores.index', ['q' => 'Registro', 'por_pagina' => 10]))->assertOk()->assertViewHas('proveedores', fn ($filas) => $filas->total() === 23 && $filas->count() === 10 && str_contains($filas->url(2), 'q=Registro'));
        $this->get(route('admin.proveedores.index', ['q' => 'Registro', 'por_pagina' => 10, 'page' => 3]))->assertOk()->assertViewHas('proveedores', fn ($filas) => $filas->count() === 3);
    }

    public function test_paginador_y_selector_de_tamano_estan_despues_de_la_tabla(): void
    {
        Proveedor::factory()->create();
        foreach (['admin.usuarios.index', 'admin.proveedores.index'] as $ruta) {
            $html = $this->get(route($ruta))->assertOk()->getContent();
            $this->assertLessThan(strpos($html, 'data-table-pagination'), strpos($html, '</table>'));
            $this->assertGreaterThan(strpos($html, 'data-table-pagination'), strpos($html, 'name="por_pagina"'));
            $this->assertSame(1, substr_count($html, 'name="por_pagina"'));
        }
    }

    public function test_busquedas_literales_no_interpretan_comodines(): void
    {
        Usuario::factory()->create(['nombres' => 'Persona sin porcentaje']);
        Proveedor::factory()->create(['nombres' => 'Persona_con_guion']);
        $this->get(route('admin.usuarios.index', ['buscar' => '%']))->assertOk()->assertViewHas('filas', fn ($filas) => $filas->total() === 0);
        $this->get(route('admin.proveedores.index', ['q' => '%']))->assertOk()->assertViewHas('proveedores', fn ($filas) => $filas->total() === 0);
        $this->get(route('admin.proveedores.index', ['q' => '_']))->assertOk()->assertSee('Persona_con_guion');
    }

    public function test_proveedores_filtran_zona_estado_y_codigo(): void
    {
        $zona = Zona::factory()->create();
        Proveedor::factory()->create(['nombres' => 'Proveedor Norte', 'zona_id' => $zona->id, 'codigo' => 'PRV-9999', 'estado' => 'activo']);
        Proveedor::factory()->create(['nombres' => 'Proveedor Sur', 'estado' => 'suspendido']);
        $this->get(route('admin.proveedores.index', ['zona_id' => $zona->id, 'estado' => 'activo']))->assertOk()->assertSee('Proveedor Norte')->assertDontSee('Proveedor Sur');
        $this->get(route('admin.proveedores.index', ['q' => 'PRV-9999']))->assertOk()->assertSee('Proveedor Norte')->assertDontSee('Proveedor Sur');
    }

    public function test_roles_personalizados_se_distinguen_del_perfil_base(): void
    {
        $id = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Secretaría', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{}', 'campos' => '{}']);
        Usuario::factory()->create(['nombres' => 'Cuenta Secretaria', 'roles' => ['acopiador'], 'perfil_rol_id' => $id]);
        $this->get(route('admin.usuarios.index', ['rol' => 'acopiador']))->assertOk()->assertDontSee('Cuenta Secretaria');
        $this->get(route('admin.usuarios.index', ['rol' => 'perfil:'.$id]))->assertOk()->assertSee('Cuenta Secretaria')->assertViewHas('filas', fn ($filas) => $filas->total() === 1);
    }

    public function test_un_consultor_de_proveedores_no_gana_acceso_a_usuarios(): void
    {
        $id = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Consulta proveedores', 'base' => 'acopiador', 'activo' => true, 'permisos' => '{"proveedores":["ver"]}', 'campos' => '{}']);
        $this->actingAs(Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $id]), 'operador');
        $this->get(route('admin.proveedores.index'))->assertOk()->assertSee(route('admin.proveedores.index'), false)->assertDontSee(route('admin.usuarios.index'), false);
        $this->get(route('admin.usuarios.index'))->assertForbidden();
    }

    public function test_estados_productivos_no_alteran_el_estado_de_la_cuenta(): void
    {
        $cuenta = Usuario::factory()->create(['nombres' => 'Cuenta activa', 'roles' => ['proveedor'], 'activo' => true]);
        Proveedor::factory()->create(['nombres' => 'Ficha suspendida', 'usuario_id' => $cuenta->id, 'estado' => 'suspendido']);
        $this->get(route('admin.usuarios.index', ['estado' => 'activo']))->assertOk()->assertSee('Cuenta activa');
        $this->get(route('admin.proveedores.index', ['estado' => 'suspendido']))->assertOk()->assertSee('Ficha suspendida');
        $this->assertTrue($cuenta->fresh()->activo);
    }

    public function test_cuentas_bloqueadas_e_inactivas_se_filtran_sin_mezclar_fichas(): void
    {
        Usuario::factory()->create(['nombres' => 'Cuenta inactiva', 'activo' => false]);
        Usuario::factory()->create(['nombres' => 'Cuenta bloqueada', 'bloqueado_manualmente' => true]);
        Proveedor::factory()->create(['nombres' => 'Ficha retirada', 'estado' => 'retirado']);
        $this->get(route('admin.usuarios.index', ['estado' => 'inactivo']))->assertOk()->assertSee('Cuenta inactiva')->assertDontSee('Ficha retirada');
        $this->get(route('admin.usuarios.index', ['estado' => 'bloqueado']))->assertOk()->assertSee('Cuenta bloqueada')->assertDontSee('Cuenta inactiva');
    }

    public function test_parametros_invalidos_se_rechazan(): void
    {
        $this->get(route('admin.usuarios.index', ['por_pagina' => 10000]))->assertSessionHasErrors('por_pagina');
        $this->get(route('admin.proveedores.index', ['por_pagina' => 10000]))->assertSessionHasErrors('por_pagina');
        $this->get(route('admin.usuarios.index', ['rol' => 'perfil:999999']))->assertSessionHasErrors('rol');
    }
}
