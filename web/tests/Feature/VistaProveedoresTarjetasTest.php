<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VistaProveedoresTarjetasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    public function test_la_vista_predeterminada_conserva_la_tabla_y_el_qr(): void
    {
        $proveedor = Proveedor::factory()->create(['nombres' => 'Productor tabla']);

        $this->get(route('admin.proveedores.index'))
            ->assertOk()
            ->assertSee('<table ', false)
            ->assertDontSee('data-proveedor-tarjeta', false)
            ->assertSee(route('admin.proveedores.qr', $proveedor->id), false)
            ->assertSee('data-table-pagination', false);
    }

    public function test_las_tarjetas_muestran_solo_datos_del_proveedor_sin_correo(): void
    {
        $zona = Zona::factory()->create(['nombre' => 'Zona de referencia']);
        $proveedor = Proveedor::factory()->create([
            'codigo' => 'PRV-TAR-001',
            'nombres' => 'Ana Productora',
            'dni' => '12345678',
            'telefono' => '999111222',
            'zona_id' => $zona->id,
            'estado' => 'activo',
        ]);

        $this->get(route('admin.proveedores.index', ['vista' => 'tarjetas']))
            ->assertOk()
            ->assertSee('data-proveedor-tarjeta', false)
            ->assertDontSee('<table ', false)
            ->assertSee('Ana Productora')
            ->assertSee('PRV-TAR-001')
            ->assertSee('12345678')
            ->assertSee('999111222')
            ->assertSee('Zona de referencia')
            ->assertSee('Activo')
            ->assertSee(route('admin.proveedores.show', $proveedor->id), false)
            ->assertDontSee('mailto:', false)
            ->assertDontSee('@gmail.com')
            ->assertDontSee('Correo electrónico');
    }

    public function test_la_paginacion_de_tarjetas_conserva_vista_busqueda_y_tamano(): void
    {
        Proveedor::factory()->count(23)->create(['nombres' => 'Grupo tarjeta']);

        $respuesta = $this->get(route('admin.proveedores.index', [
            'vista' => 'tarjetas', 'q' => 'Grupo', 'por_pagina' => 10,
        ]))->assertOk();

        $respuesta->assertViewHas('proveedores', function ($paginador): bool {
            parse_str(parse_url($paginador->url(2), PHP_URL_QUERY), $parametros);

            return $paginador->total() === 23
                && $paginador->count() === 10
                && $paginador->perPage() === 10
                && $parametros['vista'] === 'tarjetas'
                && $parametros['q'] === 'Grupo'
                && $parametros['por_pagina'] === '10'
                && $parametros['page'] === '2';
        });
        $this->assertSame(10, substr_count($respuesta->getContent(), 'data-proveedor-tarjeta'));

        $respuesta = $this->get(route('admin.proveedores.index', [
            'vista' => 'tarjetas', 'q' => 'Grupo', 'por_pagina' => 10, 'page' => 3,
        ]))->assertOk()->assertViewHas('proveedores', fn ($paginador) => $paginador->count() === 3);
        $this->assertSame(3, substr_count($respuesta->getContent(), 'data-proveedor-tarjeta'));
    }

    public function test_las_tarjetas_aplican_busqueda_zona_y_estado_conjuntamente(): void
    {
        $norte = Zona::factory()->create(['nombre' => 'Norte tarjetas']);
        $sur = Zona::factory()->create(['nombre' => 'Sur tarjetas']);
        Proveedor::factory()->create(['nombres' => 'Prueba Norte Activo', 'zona_id' => $norte->id, 'estado' => 'activo']);
        Proveedor::factory()->create(['nombres' => 'Prueba Norte Suspendido', 'zona_id' => $norte->id, 'estado' => 'suspendido']);
        Proveedor::factory()->create(['nombres' => 'Prueba Sur Activo', 'zona_id' => $sur->id, 'estado' => 'activo']);

        $this->get(route('admin.proveedores.index', [
            'vista' => 'tarjetas', 'q' => 'Prueba', 'zona_id' => $norte->id, 'estado' => 'activo',
        ]))->assertOk()
            ->assertSee('Prueba Norte Activo')
            ->assertDontSee('Prueba Norte Suspendido')
            ->assertDontSee('Prueba Sur Activo')
            ->assertViewHas('proveedores', fn ($paginador) => $paginador->total() === 1);
    }

    public function test_un_rol_de_lectura_ve_las_tarjetas_pero_no_las_acciones_de_edicion(): void
    {
        $perfilId = DB::table('perfiles_roles')->insertGetId([
            'nombre' => 'Consulta de tarjetas',
            'base' => 'acopiador',
            'activo' => true,
            'permisos' => json_encode(['proveedores' => ['ver']]),
            'campos' => '{}',
        ]);
        $this->actingAs(Usuario::factory()->create([
            'roles' => ['acopiador'], 'perfil_rol_id' => $perfilId,
        ]), 'operador');
        $proveedor = Proveedor::factory()->create(['nombres' => 'Proveedor de consulta']);

        $this->get(route('admin.proveedores.index', ['vista' => 'tarjetas']))
            ->assertOk()
            ->assertSee('Proveedor de consulta')
            ->assertSee(route('admin.proveedores.show', $proveedor->id), false)
            ->assertDontSee(route('admin.proveedores.edit', $proveedor->id), false)
            ->assertDontSee('Nuevo proveedor');
    }

    public function test_una_vista_no_admitida_se_rechaza(): void
    {
        $this->get(route('admin.proveedores.index', ['vista' => 'inexistente']))
            ->assertSessionHasErrors('vista');
    }

    public function test_tarjetas_vacias_conservan_filtros_y_paginador_sin_inventar_datos(): void
    {
        Proveedor::factory()->create(['nombres' => 'Productor existente', 'telefono' => null]);

        $respuesta = $this->get(route('admin.proveedores.index', ['vista' => 'tarjetas']))->assertOk();
        $respuesta->assertDontSee('Sin teléfono')->assertDontSee('mailto:', false);

        $this->get(route('admin.proveedores.index', ['vista' => 'tarjetas', 'q' => 'zzzinexistente']))
            ->assertOk()
            ->assertSee('No se encontraron proveedores')
            ->assertDontSee('Productor existente')
            ->assertSee('data-table-pagination', false)
            ->assertViewHas('proveedores', fn ($paginador) => $paginador->total() === 0);
    }
}
