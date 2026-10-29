<?php

namespace Tests\Feature;

use App\Domain\Proveedores\EstadoProveedor;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** El permiso «Eliminar» de un rol personalizado autoriza solo bajas lógicas reales y se aplica en el servidor. */
class PermisoEliminarRolesTest extends TestCase
{
    use RefreshDatabase;

    private function comoRol(array $permisos): Usuario
    {
        $rol = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Rol QA '.uniqid(), 'base' => null, 'activo' => true, 'permisos' => json_encode($permisos), 'campos' => '{}']);
        $cuenta = Usuario::factory()->create(['roles' => [], 'perfil_rol_id' => $rol]);
        $this->actingAs($cuenta, 'operador');

        return $cuenta;
    }

    private function datosProveedor(Proveedor $proveedor, string $estado): array
    {
        return ['nombres' => $proveedor->nombres, 'dni' => $proveedor->dni, 'zona_id' => $proveedor->zona_id, 'estado' => $estado];
    }

    public function test_retirar_un_proveedor_requiere_eliminar_y_conserva_su_historial(): void
    {
        $proveedor = Proveedor::factory()->create();
        $entrega = Entrega::factory()->create(['proveedor_id' => $proveedor->id]);
        $otro = Proveedor::factory()->create();

        $this->comoRol(['proveedores' => ['ver', 'editar']]);
        $this->get(route('admin.proveedores.edit', $proveedor->id))->assertOk()->assertDontSee('value="retirado"', false)->assertSee('requiere el permiso Eliminar');
        $this->put(route('admin.proveedores.update', $proveedor->id), $this->datosProveedor($proveedor, 'retirado'))->assertForbidden();
        $this->patch(route('admin.proveedores.estado', $proveedor->id), ['estado' => 'retirado'])->assertForbidden();
        $this->put(route('admin.proveedores.update', $proveedor->id), $this->datosProveedor($proveedor, 'suspendido'))->assertSessionHasNoErrors();
        $this->assertSame(EstadoProveedor::Suspendido, $proveedor->fresh()->estado);

        $this->comoRol(['proveedores' => ['ver', 'editar', 'eliminar']]);
        $this->get(route('admin.proveedores.edit', $proveedor->id))->assertOk()->assertSee('value="retirado"', false);
        $this->put(route('admin.proveedores.update', $proveedor->id), $this->datosProveedor($proveedor, 'retirado'))->assertSessionHasNoErrors();
        $this->assertSame(EstadoProveedor::Retirado, $proveedor->fresh()->estado);
        $this->assertModelExists($entrega);
        $this->assertModelExists($proveedor);
        $this->assertSame(EstadoProveedor::Activo, $otro->fresh()->estado);

        // Editar los datos de una ficha ya retirada no es una nueva baja.
        $this->comoRol(['proveedores' => ['ver', 'editar']]);
        $this->put(route('admin.proveedores.update', $proveedor->id), [...$this->datosProveedor($proveedor, 'retirado'), 'nombres' => 'Nombre corregido'])->assertSessionHasNoErrors();
        $this->assertSame('Nombre corregido', $proveedor->fresh()->nombres);
    }

    public function test_desactivar_requiere_eliminar_y_reactivar_solo_editar(): void
    {
        $zona = Zona::factory()->create(['activo' => true]);
        $vehiculo = Vehiculo::factory()->create(['activo' => true]);
        $producto = Producto::factory()->create(['activo' => true]);

        $this->comoRol(['zonas_vehiculos' => ['ver', 'editar'], 'productos' => ['ver', 'editar']]);
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => 0])->assertForbidden();
        $this->patch(route('admin.vehiculos.estado', $vehiculo->id), ['activo' => 0])->assertForbidden();
        $this->patch(route('admin.produccion.productos.estado', $producto->id), ['activo' => 0])->assertForbidden();
        $this->get(route('admin.zonas-vehiculos.index'))->assertOk()->assertDontSee(route('admin.zonas.estado', $zona->id), false);
        $this->assertTrue((bool) $zona->fresh()->activo);

        $this->comoRol(['zonas_vehiculos' => ['ver', 'eliminar'], 'productos' => ['ver', 'eliminar']]);
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => 0])->assertSessionHasNoErrors();
        $this->patch(route('admin.vehiculos.estado', $vehiculo->id), ['activo' => 0])->assertSessionHasNoErrors();
        $this->patch(route('admin.produccion.productos.estado', $producto->id), ['activo' => 0])->assertSessionHasNoErrors();
        $this->assertFalse((bool) $zona->fresh()->activo);
        $this->assertFalse((bool) $vehiculo->fresh()->activo);
        $this->assertFalse((bool) $producto->fresh()->activo);
        $this->assertModelExists($zona);
        // Reactivar no es una baja: con solo Eliminar no se permite, con Editar sí.
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => 1])->assertForbidden();

        $this->comoRol(['zonas_vehiculos' => ['ver', 'editar']]);
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => 1])->assertSessionHasNoErrors();
        $this->assertTrue((bool) $zona->fresh()->activo);
    }

    public function test_anular_una_entrega_requiere_eliminar_de_jornadas_y_la_conserva_anulada(): void
    {
        $entrega = Entrega::factory()->create(['anulada' => false]);

        $this->comoRol(['acopiadores' => ['ver', 'crear', 'editar']]);
        $this->post(route('admin.acopiadores.entregas.anular', $entrega->id), ['motivo' => 'Prueba ficticia'])->assertForbidden();
        $this->assertFalse((bool) $entrega->fresh()->anulada);

        $this->comoRol(['acopiadores' => ['ver', 'eliminar']]);
        $this->post(route('admin.acopiadores.entregas.anular', $entrega->id), ['motivo' => 'Prueba ficticia'])->assertRedirect();
        $this->assertTrue((bool) $entrega->fresh()->anulada);
        $this->assertModelExists($entrega);
    }

    public function test_bajas_de_ventas_produccion_y_rutas_exigen_eliminar(): void
    {
        $venta = DB::table('ventas')->insertGetId(['codigo' => 'V-QA-1', 'usuario_id' => Usuario::factory()->create()->id, 'subtotal' => 10, 'total' => 10, 'vendida_en' => now(), 'estado' => 'pendiente']);
        $this->comoRol(['ventas' => ['ver', 'crear', 'editar'], 'produccion' => ['ver', 'crear', 'editar'], 'acopiadores' => ['ver', 'editar'], 'zonas_vehiculos' => ['ver', 'editar']]);
        $this->patch("/admin/ventas/{$venta}/estado", ['estado' => 'anulada'])->assertForbidden();
        $this->post('/admin/produccion/lotes/999999/cancelar')->assertForbidden();
        $this->patch('/admin/acopiadores/marcas/999999/deshacer', ['motivo' => 'Prueba ficticia'])->assertForbidden();

        $this->comoRol(['ventas' => ['ver', 'eliminar'], 'produccion' => ['ver', 'eliminar'], 'acopiadores' => ['ver', 'eliminar']]);
        $this->patch("/admin/ventas/{$venta}/estado", ['estado' => 'completada'])->assertForbidden();
        $this->patch("/admin/ventas/{$venta}/estado", ['estado' => 'anulada'])->assertSessionHasNoErrors();
        $this->assertSame('anulada', DB::table('ventas')->where('id', $venta)->value('estado'));
        $this->assertDatabaseHas('ventas', ['id' => $venta]);
        $this->post('/admin/produccion/lotes/999999/cancelar')->assertSessionHasErrors('motivo');
        $this->patch('/admin/acopiadores/marcas/999999/deshacer', ['motivo' => 'Prueba ficticia'])->assertNotFound();
    }

    public function test_modulos_protegidos_no_conceden_eliminar_y_los_roles_oficiales_conservan_sus_bajas(): void
    {
        $this->comoRol(['liquidaciones' => ['ver', 'eliminar'], 'usuarios' => ['ver', 'eliminar']]);
        $cuenta = auth('operador')->user();
        // Aunque el permiso se haya guardado fuera del formulario, un módulo protegido no lo concede.
        $this->assertFalse($cuenta->puede('usuarios', 'gestionar'));
        $this->assertFalse($cuenta->puede('liquidaciones', 'eliminar'));
        $this->assertTrue($cuenta->puede('liquidaciones', 'ver'));

        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
        $id = (int) DB::table('perfiles_roles')->insertGetId(['nombre' => 'Por formulario', 'base' => null, 'activo' => true, 'permisos' => '{}', 'campos' => '{}']);
        $this->put(route('admin.roles.update', $id), ['nombre' => 'Por formulario', 'activo' => 1, 'permisos_presentes' => 1, 'permisos' => ['liquidaciones' => ['ver', 'eliminar'], 'usuarios' => ['eliminar']]])->assertSessionHasNoErrors();
        $this->assertSame(['liquidaciones' => ['ver'], 'usuarios' => []], json_decode(DB::table('perfiles_roles')->where('id', $id)->value('permisos'), true));

        $zona = Zona::factory()->create(['activo' => true]);
        $this->patch(route('admin.zonas.estado', $zona->id), ['activo' => 0])->assertSessionHasNoErrors();
        $this->assertFalse((bool) $zona->fresh()->activo);
    }
}
