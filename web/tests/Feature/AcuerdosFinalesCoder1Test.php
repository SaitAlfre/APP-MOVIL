<?php

namespace Tests\Feature;

use App\Application\Movil\RevisionCatalogo;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcuerdosFinalesCoder1Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        $admin = Usuario::factory()->create(['roles' => ['admin'], 'pin_hash' => '2468']);
        $this->actingAs($admin, 'operador');

        return $admin;
    }

    private function datos(array $cambios = []): array
    {
        return [...['username' => 'qa_cierre', 'nombres' => 'Cuenta ficticia', 'dni' => '12345678', 'pin' => '1357', 'roles' => ['acopiador']], ...$cambios];
    }

    public function test_altas_web_y_api_solo_admiten_un_rol_oficial(): void
    {
        $admin = $this->admin();
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        foreach ([['admin', 'acopiador'], ['calidad', 'acopiador'], ['proveedor', 'calidad']] as $roles) {
            $this->post('/admin/usuarios', $this->datos(['roles' => $roles]))->assertSessionHasErrors();
            $this->withToken($token)->putJson('/api/movil/cambios/usuario', $this->datos(['roles' => $roles, 'activo' => true]))->assertUnprocessable();
        }
        $this->assertDatabaseMissing('usuarios', ['username' => 'qa_cierre']);
    }

    public function test_contrato_identidad_nueva_y_hash_se_conserva_en_alta_valida(): void
    {
        $this->admin();
        foreach (['ab', str_repeat('a', 31), 'usuario-con-guion', 'áusuario'] as $username) {
            $this->post('/admin/usuarios', $this->datos(['username' => $username]))->assertSessionHasErrors();
        }
        foreach (['1234567', '123456789', 'ABCDEFGH'] as $dni) {
            $this->post('/admin/usuarios', $this->datos(['dni' => $dni]))->assertSessionHasErrors();
        }
        $this->post('/admin/usuarios', $this->datos())->assertSessionHasNoErrors();
        $cuenta = Usuario::where('username', 'qa_cierre')->sole();
        $this->assertSame(['acopiador'], $cuenta->roles);
        $this->assertTrue(Hash::check('1357', $cuenta->pin_hash));
        $this->assertNotSame('1357', $cuenta->pin_hash);
    }

    public function test_calidad_nueva_y_cambio_de_rol_necesitan_zona_y_no_se_quita_silenciosamente(): void
    {
        $this->admin();
        $this->post('/admin/usuarios', $this->datos(['roles' => ['calidad']]))->assertSessionHasErrors();
        $zona = Zona::factory()->create();
        $this->post('/admin/usuarios', $this->datos(['roles' => ['calidad'], 'zona_id' => $zona->id]))->assertSessionHasNoErrors();
        $cuenta = Usuario::where('username', 'qa_cierre')->sole();
        $this->put(route('admin.usuarios.update', $cuenta->id), ['nombres' => $cuenta->nombres, 'dni' => $cuenta->dni, 'roles' => ['calidad'], 'zona_id' => null])->assertSessionHasErrors();
        $this->assertSame($zona->id, $cuenta->fresh()->zona_id);
        $otra = Usuario::factory()->create(['roles' => ['acopiador']]);
        $this->put(route('admin.usuarios.update', $otra->id), ['nombres' => $otra->nombres, 'dni' => $otra->dni, 'roles' => ['calidad']])->assertSessionHasErrors();
        $this->assertSame(['acopiador'], $otra->fresh()->roles);
    }

    public function test_cuenta_legada_conserva_documento_roles_y_zona_ausente_al_editar_otro_dato(): void
    {
        $this->admin();
        $legada = Usuario::factory()->create(['roles' => ['calidad', 'proveedor'], 'username' => 'USUARIO-LEGADO', 'dni' => 'DOC-ANTERIOR', 'zona_id' => null]);
        $ficha = Proveedor::factory()->create(['usuario_id' => $legada->id]);
        $hash = $legada->pin_hash;
        $this->put(route('admin.usuarios.update', $legada->id), ['nombres' => 'Nombre corregido', 'dni' => $legada->dni, 'roles' => ['proveedor', 'calidad'], 'zona_id' => null])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing(['calidad', 'proveedor'], $legada->fresh()->roles);
        $this->assertSame('DOC-ANTERIOR', $legada->fresh()->dni);
        $this->assertSame($hash, $legada->fresh()->pin_hash);
        $this->assertNull($legada->fresh()->zona_id);
        $this->assertSame($legada->id, $ficha->fresh()->usuario_id);
        $this->put(route('admin.usuarios.update', $legada->id), ['nombres' => 'Otro nombre', 'dni' => 'OTRO-LEGADO', 'roles' => $legada->roles])->assertSessionHasErrors();
        $this->assertSame('DOC-ANTERIOR', $legada->fresh()->dni);
        $this->put(route('admin.usuarios.update', $legada->id), ['nombres' => 'Otro nombre', 'dni' => $legada->dni, 'roles' => ['admin', 'calidad']])->assertSessionHasErrors();
        $this->assertEqualsCanonicalizing(['calidad', 'proveedor'], $legada->fresh()->roles);
    }

    public function test_api_aplica_identidad_y_zona_de_calidad_sin_crear_datos_parciales(): void
    {
        $admin = $this->admin();
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        foreach ([['username' => 'ab'], ['dni' => 'DOC'], ['roles' => ['CALIDAD']]] as $cambio) {
            $this->withToken($token)->putJson('/api/movil/cambios/usuario', $this->datos([...$cambio, 'activo' => true]))->assertUnprocessable();
        }
        $this->assertDatabaseMissing('usuarios', ['username' => 'qa_cierre']);
    }

    public function test_calidad_pierde_consulta_de_planta_y_finanzas_tambien_en_dashboard(): void
    {
        $cuenta = Usuario::factory()->create(['roles' => ['calidad']]);
        $this->actingAs($cuenta, 'operador');
        foreach (['/admin/produccion', '/admin/liquidaciones', '/admin/inventario', '/admin/ventas', '/admin/usuarios'] as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach (['/admin/calidad', '/admin/sanciones', '/admin/recepcion', '/admin/entregas', '/admin/reportes', '/admin/comunicados'] as $url) {
            $this->get($url)->assertOk();
        }
        $vista = $this->get('/admin/dashboard')->assertOk()->assertDontSee('Liquidaciones pendientes')->assertDontSee('Lotes abiertos')->assertDontSee('Jornadas abiertas');
        foreach (['liquidaciones_pendientes', 'consumido_produccion_litros', 'lotes_en_proceso', 'lotes_finalizados', 'disponible_litros'] as $clave) {
            $this->assertArrayNotHasKey($clave, $vista->viewData('indicadores'));
        }
        $this->assertSame([], $vista->viewData('panel')['jornadas_abiertas']);
        $this->assertArrayNotHasKey('lotes_abiertos', $vista->viewData('panel'));
        $this->assertArrayNotHasKey('liquidaciones_pendientes', $vista->viewData('alertas'));
    }

    public function test_admin_conserva_modulos_y_cuentas_heredadas_no_se_convierten(): void
    {
        $admin = $this->admin();
        foreach (['/admin/recepcion', '/admin/produccion', '/admin/liquidaciones', '/admin/inventario', '/admin/ventas'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/dashboard')->assertOk()->assertSee('Liquidaciones pendientes')->assertSee('Lotes abiertos');
        $this->assertSame(['admin'], $admin->fresh()->roles);
    }

    public function test_calidad_no_recibe_datos_privados_de_proveedor_por_un_vinculo_antiguo(): void
    {
        $cuenta = Usuario::factory()->create(['roles' => ['calidad'], 'pin_hash' => '1357']);
        $ficha = Proveedor::factory()->create(['usuario_id' => $cuenta->id]);
        Liquidacion::query()->create([
            'proveedor_id' => $ficha->id, 'periodo_inicio' => today(), 'periodo_fin' => today(),
            'litros_totales' => 100, 'precio_litro' => 1.5, 'monto_total' => 150,
            'estado' => 'pendiente', 'generada_en' => now(),
        ]);
        SolicitudTraslado::factory()->create(['proveedor_id' => $ficha->id]);
        $entrega = Entrega::factory()->create(['proveedor_id' => $ficha->id, 'zona_id' => $ficha->zona_id]);
        ReclamoProveedor::query()->create([
            'proveedor_id' => $ficha->id, 'entrega_id' => $entrega->id, 'litros_originales' => 40,
            'litros_solicitados' => 35, 'motivo' => 'Solicitud ficticia Coder 1', 'estado' => 'pendiente',
        ]);
        $token = $this->postJson('/api/movil/sesion', ['username' => $cuenta->username, 'pin' => '1357'])->assertOk()->json('token');
        $datos = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json();
        $this->assertSame([], $datos['pagos']);
        $this->assertSame([], $datos['solicitudes']);
        // Cambiar el fixture a Proveedor no cambia datos de negocio: acredita el ámbito propio.
        $cuenta->update(['roles' => ['proveedor']]);
        $datos = $this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json();
        $this->assertCount(1, $datos['pagos']);
        $this->assertCount(2, $datos['solicitudes']);
        $this->assertSame($ficha->id, $datos['pagos'][0]['proveedorId']);
    }

    public function test_ack_c1_devuelve_revision_para_editar_sin_descarga_y_conserva_conflictos_reales(): void
    {
        $admin = $this->admin();
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        $alta = $this->withToken($token)->putJson('/api/movil/cambios/zona', ['nombre' => 'QA ACK', 'activo' => true, 'controlVersion' => true])->assertOk()->json();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $alta['revision']);
        $edicion = $this->withToken($token)->putJson('/api/movil/cambios/zona', ['servidorId' => $alta['id'], 'nombre' => 'QA ACK EDITADA', 'activo' => true, 'controlVersion' => true, 'revisionBase' => $alta['revision']])->assertOk()->json();
        $this->assertNotSame($alta['revision'], $edicion['revision']);
        $this->assertSame($edicion['revision'], RevisionCatalogo::de(Zona::findOrFail($alta['id'])));
        Zona::findOrFail($alta['id'])->update(['nombre' => 'CAMBIO DE OTRO CLIENTE']);
        $this->withToken($token)->putJson('/api/movil/cambios/zona', ['servidorId' => $alta['id'], 'nombre' => 'OTRA EDICION LOCAL', 'activo' => true, 'controlVersion' => true, 'revisionBase' => $edicion['revision']])->assertConflict();
        $this->assertDatabaseHas('zonas', ['id' => $alta['id'], 'nombre' => 'CAMBIO DE OTRO CLIENTE']);
    }

    public function test_ficha_admite_limites_acordados_y_rechaza_excesos_en_web_y_api(): void
    {
        $admin = $this->admin();
        $zona = Zona::factory()->create();
        $web = ['codigo' => str_repeat('C', 50), 'nombres' => str_repeat('N', 150), 'dni' => '12345678', 'telefono' => str_repeat('T', 30), 'direccion' => str_repeat('A', 255), 'zona_id' => $zona->id, 'tachos' => 1000, 'capacidad_tacho_l' => 200.01];
        $this->post('/admin/proveedores', $web)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('proveedores', ['codigo' => $web['codigo'], 'dni' => $web['dni'], 'tachos' => 1000]);
        $this->post('/admin/proveedores', [...$web, 'codigo' => 'EXCESO', 'dni' => '87654321', 'tachos' => 1001])->assertSessionHasErrors('tachos');
        $token = $this->postJson('/api/movil/sesion', ['username' => $admin->username, 'pin' => '2468'])->assertOk()->json('token');
        $api = ['codigo' => 'QA API', 'nombres' => 'Ficha ficticia', 'dni' => 'FICHA-API', 'zonaId' => $zona->id, 'zonaNombre' => $zona->nombre, 'tachos' => 1000, 'capacidadTachoL' => 0.01, 'estado' => 'ACTIVO', 'usuarioUsername' => null];
        $this->withToken($token)->putJson('/api/movil/cambios/proveedor', $api)->assertOk();
        foreach ([['codigo' => str_repeat('C', 51)], ['tachos' => 1001], ['capacidadTachoL' => 0.009]] as $exceso) {
            $this->withToken($token)->putJson('/api/movil/cambios/proveedor', [...$api, ...$exceso])->assertUnprocessable();
        }
        $this->assertDatabaseCount('proveedores', 2);
    }
}
