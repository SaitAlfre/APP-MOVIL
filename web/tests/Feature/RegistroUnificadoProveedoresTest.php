<?php

namespace Tests\Feature;

use App\Application\Proveedores\ActorRegistroProveedor;
use App\Application\Proveedores\CredencialesCuentaMovil;
use App\Application\Proveedores\DatosProveedor;
use App\Application\Proveedores\RegistrarProveedorUseCase;
use App\Domain\Proveedores\ProveedorQr;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistroUnificadoProveedoresTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->create(['roles' => ['admin']]);
        $this->zona = Zona::factory()->create();
        $this->actingAs($this->admin, 'operador');
    }

    /** @return array<string, mixed> */
    private function ficha(array $extra = []): array
    {
        return [...['dni' => '45678912', 'nombres' => 'Rosa Quispe Mamani', 'telefono' => '987654321', 'zona_id' => $this->zona->id, 'crear_cuenta' => '0'], ...$extra];
    }

    /** @return array<string, mixed> */
    private function conCuenta(array $extra = []): array
    {
        return $this->ficha(['crear_cuenta' => '1', 'username' => 'rosa.quispe', 'pin' => '4826', 'pin_confirmation' => '4826', ...$extra]);
    }

    public function test_crea_proveedor_sin_cuenta_movil(): void
    {
        $this->post(route('admin.proveedores.store'), $this->ficha())
            ->assertRedirect(route('admin.proveedores.index'))
            ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, 'sin cuenta móvil'));

        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        $this->assertNull($ficha->usuario_id);
        $this->assertMatchesRegularExpression('/\APRV-[A-Z0-9]{8}\z/', $ficha->codigo);
        $this->assertDatabaseMissing('usuarios', ['dni' => '45678912']);
        $this->get(route('admin.proveedores.index'))->assertSee('Rosa Quispe Mamani');
    }

    public function test_crea_proveedor_con_cuenta_vinculada_visible_en_ambos_modulos(): void
    {
        $this->post(route('admin.proveedores.store'), $this->conCuenta(['origen' => 'usuarios']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.usuarios.index'));

        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        $cuenta = Usuario::query()->where('username', 'rosa.quispe')->sole();
        $this->assertSame($cuenta->id, $ficha->usuario_id);
        $this->assertSame(['proveedor'], $cuenta->roles);
        $this->assertSame($ficha->dni, $cuenta->dni);
        $this->assertSame($ficha->nombres, $cuenta->nombres);
        $this->assertTrue(Hash::check('4826', $cuenta->pin_hash));
        $this->assertTrue($cuenta->activo);

        $this->get(route('admin.proveedores.index'))->assertSee('Rosa Quispe Mamani');
        $this->get(route('admin.usuarios.index'))->assertSee('rosa.quispe');
        $this->assertDatabaseHas('auditorias', ['entidad' => 'usuario', 'entidad_id' => $cuenta->id, 'accion' => 'crear']);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'proveedor', 'entidad_id' => $ficha->id]);
        $this->assertDatabaseMissing('auditorias', ['valor_despues' => '4826']);
        $this->assertFalse(DB::table('auditorias')->where('valor_despues', 'like', '%4826%')->orWhere('valor_antes', 'like', '%4826%')->exists());
    }

    public function test_ambas_entradas_abren_el_mismo_formulario_con_cuenta_segun_el_origen(): void
    {
        $this->get(route('admin.proveedores.create'))->assertOk()->assertViewIs('admin.proveedores.form')
            ->assertViewHas('crearCuentaInicial', false)->assertSee('Habilitar acceso móvil')->assertSee('Registrar sin acceso móvil')
            ->assertSee('data-modo-acceso="opcional"', false)->assertSee('name="crear_cuenta" value="0"', false)
            ->assertDontSee('¿Crear cuenta para acceder a la aplicación móvil?')->assertDontSee('type="checkbox" name="crear_cuenta"', false);
        $this->get(route('admin.usuarios.create', ['base' => 'proveedor']))->assertRedirect(route('admin.proveedores.create', ['cuenta' => 1, 'origen' => 'usuarios']));
        $this->get(route('admin.proveedores.create', ['cuenta' => 1, 'origen' => 'usuarios']))->assertOk()->assertViewIs('admin.proveedores.form')
            ->assertViewHas('crearCuentaInicial', true)->assertSee('Usuarios y accesos')->assertSee('Sugerir usuario a partir del nombre')->assertSee('Mostrar PIN')
            ->assertSee('data-modo-acceso="obligatorio"', false)->assertSee('name="crear_cuenta" value="1"', false)
            ->assertDontSee('Habilitar acceso móvil')->assertDontSee('Registrar sin acceso móvil')->assertDontSee('¿Crear cuenta para acceder a la aplicación móvil?');
        $this->get(route('admin.proveedores.create', ['origen' => 'usuarios']))->assertViewHas('crearCuentaInicial', true);
        $this->get(route('admin.usuarios.create'))->assertOk()->assertSee(route('admin.proveedores.create', ['cuenta' => 1, 'origen' => 'usuarios']));
    }

    public function test_ficha_existente_sin_cuenta_no_se_duplica_y_se_vincula_tras_confirmar(): void
    {
        $existente = Proveedor::factory()->create(['dni' => '45678912', 'nombres' => 'Rosa Quispe', 'zona_id' => $this->zona->id]);
        $otraZona = Zona::factory()->create();

        $this->post(route('admin.proveedores.store'), $this->ficha())->assertSessionHasErrors('dni');
        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertSessionHasErrors(['dni' => 'Este DNI ya tiene una ficha productiva sin cuenta. Puedes crear su cuenta móvil y vincularla; se conservan su código, QR, zona y entregas. Confirma la acción sugerida para continuar.']);
        $this->assertDatabaseCount('usuarios', 1);

        $this->post(route('admin.proveedores.store'), $this->conCuenta(['accion_registro' => 'vincular_ficha', 'zona_id' => $otraZona->id, 'nombres' => 'Otro nombre']))
            ->assertSessionHasNoErrors()->assertSessionHas('estado', fn (string $m) => str_contains($m, $existente->codigo));

        $this->assertDatabaseCount('proveedores', 1);
        $ficha = $existente->fresh();
        $this->assertSame($this->zona->id, $ficha->zona_id);
        $this->assertSame('Rosa Quispe', $ficha->nombres);
        $this->assertSame($existente->codigo, $ficha->codigo);
        $this->assertSame(Usuario::query()->where('username', 'rosa.quispe')->value('id'), $ficha->usuario_id);
        $this->assertSame('Rosa Quispe', Usuario::query()->where('username', 'rosa.quispe')->value('nombres'));
    }

    public function test_cuenta_existente_sin_ficha_se_asocia_sin_crear_otra_cuenta(): void
    {
        $cuenta = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['proveedor']]);

        $this->post(route('admin.proveedores.store'), $this->ficha())->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('proveedores', 0);

        $this->post(route('admin.proveedores.store'), $this->conCuenta(['accion_registro' => 'asociar_cuenta', 'pin' => '', 'pin_confirmation' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('usuarios', 2);
        $this->assertDatabaseMissing('usuarios', ['username' => 'rosa.quispe']);
        $this->assertSame($cuenta->id, Proveedor::query()->where('dni', '45678912')->value('usuario_id'));
    }

    public function test_detecta_usuario_de_acceso_duplicado(): void
    {
        Usuario::factory()->create(['username' => 'rosa.quispe']);

        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertSessionHasErrors(['username' => 'Este nombre de usuario ya está ocupado. Elige otro.']);
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_vincula_una_ficha_y_una_cuenta_existentes(): void
    {
        $ficha = Proveedor::factory()->create(['dni' => '45678912']);
        $cuenta = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['proveedor']]);

        $this->getJson(route('admin.proveedores.duplicados', ['dni' => '45678912']))->assertOk()
            ->assertJsonPath('registro.situacion', 'ficha_y_cuenta_sin_vincular')->assertJsonPath('registro.accion', 'vincular_existentes')
            ->assertJsonPath('registro.cuenta.username', $cuenta->username)->assertJsonMissingPath('registro.cuenta.pin_hash');

        $this->post(route('admin.proveedores.store'), $this->ficha(['accion_registro' => 'vincular_existentes']))->assertSessionHasNoErrors();
        $this->assertSame($cuenta->id, $ficha->fresh()->usuario_id);
        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_rechaza_vinculacion_con_dni_incompatible(): void
    {
        $ficha = Proveedor::factory()->create(['dni' => '45678912']);
        $cuenta = Usuario::factory()->create(['dni' => '11112222', 'roles' => ['proveedor']]);

        $this->post(route('admin.proveedores.vincular', $ficha->id), ['usuario_id' => $cuenta->id])
            ->assertSessionHasErrors(['usuario_id' => 'El DNI de la cuenta no coincide con el de la ficha. Corrige el documento antes de vincularlas.']);
        $this->assertNull($ficha->fresh()->usuario_id);

        $vinculada = Proveedor::factory()->create(['dni' => '33334444', 'usuario_id' => $cuenta->id]);
        $this->getJson(route('admin.proveedores.duplicados', ['dni' => '33334444']))->assertJsonPath('registro.situacion', 'conflicto_dni')->assertJsonPath('registro.bloquea', true);
        $this->post(route('admin.proveedores.store'), $this->conCuenta(['dni' => '33334444']))->assertSessionHasErrors('dni');
        $this->assertSame($cuenta->id, $vinculada->fresh()->usuario_id);
    }

    public function test_rechaza_cuentas_ya_vinculadas_y_registros_completos(): void
    {
        $cuenta = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['proveedor']]);
        Proveedor::factory()->create(['dni' => '99990000', 'usuario_id' => $cuenta->id]);

        $this->getJson(route('admin.proveedores.duplicados', ['dni' => '45678912']))->assertJsonPath('registro.situacion', 'cuenta_vinculada_a_otra_ficha');
        $this->post(route('admin.proveedores.store'), $this->ficha(['accion_registro' => 'asociar_cuenta']))->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('proveedores', 1);

        $propia = Usuario::factory()->create(['dni' => '55556666', 'roles' => ['proveedor']]);
        $ficha = Proveedor::factory()->create(['dni' => '55556666', 'usuario_id' => $propia->id]);
        $this->getJson(route('admin.proveedores.duplicados', ['dni' => '55556666']))->assertJsonPath('registro.situacion', 'ya_vinculado')
            ->assertJsonPath('registro.ficha.url', route('admin.proveedores.show', $ficha->id));
        $this->post(route('admin.proveedores.store'), $this->conCuenta(['dni' => '55556666']))->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('usuarios', 3);
    }

    public function test_dni_de_otro_rol_no_cambia_su_rol_ni_crea_registros(): void
    {
        $acopiador = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['acopiador']]);

        $this->getJson(route('admin.proveedores.duplicados', ['dni' => '45678912']))->assertJsonPath('registro.situacion', 'cuenta_de_otro_rol');
        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertSessionHasErrors('dni');
        $this->assertSame(['acopiador'], $acopiador->fresh()->roles);
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_rechaza_una_confirmacion_que_ya_no_corresponde_a_la_situacion(): void
    {
        $this->post(route('admin.proveedores.store'), $this->conCuenta(['accion_registro' => 'vincular_ficha']))->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('proveedores', 0);
        $this->assertDatabaseMissing('usuarios', ['username' => 'rosa.quispe']);
    }

    public function test_si_falla_la_cuenta_no_queda_una_ficha_incompleta(): void
    {
        DB::statement("CREATE TRIGGER fallo_alta_cuenta BEFORE INSERT ON usuarios BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");

        $this->post(route('admin.proveedores.store'), $this->conCuenta())
            ->assertSessionHasErrors(['dni' => 'No se pudo guardar la operación. Inténtalo nuevamente o contacta al administrador.']);

        $this->assertDatabaseCount('proveedores', 0);
        $this->assertDatabaseMissing('auditorias', ['entidad' => 'proveedor']);
    }

    public function test_el_caso_de_uso_revierte_la_ficha_fuera_de_la_peticion_http(): void
    {
        DB::statement("CREATE TRIGGER fallo_alta_cuenta BEFORE INSERT ON usuarios BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        $datos = new DatosProveedor('PRV-ROLLBACK', 'Rosa Quispe Mamani', '45678912', null, null, $this->zona->id, 1, 40);

        try {
            app(RegistrarProveedorUseCase::class)->ejecutar($datos, new CredencialesCuentaMovil('rosa.quispe', '4826'), null, new ActorRegistroProveedor($this->admin->id, true, true, true));
            $this->fail('Se esperaba el fallo de la cuenta.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('proveedores', ['codigo' => 'PRV-ROLLBACK']);
        }
    }

    public function test_usuarios_no_autorizados_no_registran_ni_crean_cuentas(): void
    {
        $this->actingAs(Usuario::factory()->create(['roles' => ['acopiador']]), 'operador');
        $this->get(route('admin.proveedores.create'))->assertForbidden();
        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertForbidden();

        $perfil = DB::table('perfiles_roles')->insertGetId(['nombre' => 'Registro de campo', 'base' => 'acopiador', 'activo' => true, 'permisos' => json_encode(['proveedores' => ['ver', 'crear']]), 'campos' => '{}']);
        $this->actingAs(Usuario::factory()->create(['roles' => ['acopiador'], 'perfil_rol_id' => $perfil]), 'operador');
        $this->get(route('admin.proveedores.create', ['cuenta' => 1]))->assertOk()->assertViewHas('puedeCrearCuentas', false)->assertDontSee('PIN inicial');
        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertSessionHasErrors(['dni' => 'Solo un administrador puede crear cuentas de acceso para proveedores.']);
        $this->assertDatabaseCount('proveedores', 0);
        $this->post(route('admin.proveedores.store'), $this->ficha())->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('usuarios', ['username' => 'rosa.quispe']);
        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_pin_debe_tener_exactamente_cuatro_digitos_y_coincidir(): void
    {
        foreach (['123', '12345', 'abcd', '12 4'] as $pin) {
            $this->post(route('admin.proveedores.store'), $this->conCuenta(['pin' => $pin, 'pin_confirmation' => $pin]))->assertSessionHasErrors('pin');
        }
        $this->post(route('admin.proveedores.store'), $this->conCuenta(['pin_confirmation' => '1111']))->assertSessionHasErrors(['pin_confirmation' => 'Los PIN no coinciden. Escríbelos de nuevo.']);
        $this->post(route('admin.proveedores.store'), $this->conCuenta(['dni' => '1234567']))->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('proveedores', 0);
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_editar_proveedor_con_cuenta_vinculada_conserva_identidad_y_codigo(): void
    {
        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertSessionHasNoErrors();
        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        $cuenta = Usuario::query()->where('username', 'rosa.quispe')->sole();

        $this->get(route('admin.proveedores.edit', $ficha->id))->assertOk()->assertSee('rosa.quispe')
            ->assertSee(route('admin.usuarios.edit', $cuenta->id), false)->assertDontSee('Crear cuenta móvil');
        $this->put(route('admin.proveedores.update', $ficha->id), $this->ficha(['nombres' => 'Rosa Quispe M.', 'telefono' => '900000000']))->assertSessionHasNoErrors();
        $this->put(route('admin.proveedores.update', $ficha->id), $this->ficha(['dni' => '45678913']))->assertSessionHasErrors('codigo');

        $ficha->refresh();
        $this->assertSame('45678912', $ficha->dni);
        $this->assertSame('Rosa Quispe M.', $ficha->nombres);
        $this->assertSame($cuenta->id, $ficha->usuario_id);
        $this->get(route('admin.proveedores.edit', $ficha->id))->assertSee('El nombre de la cuenta difiere del de la ficha');

        $this->get(route('admin.usuarios.edit', $cuenta->id))->assertOk()->assertSee('Proveedor asociado')->assertSee(route('admin.proveedores.show', $ficha->id), false);
        $this->put(route('admin.usuarios.update', $cuenta->id), ['nombres' => $cuenta->nombres, 'dni' => '45678913', 'roles' => ['proveedor']])->assertSessionHasErrors('roles');
        $this->put(route('admin.usuarios.update', $cuenta->id), ['nombres' => $cuenta->nombres, 'dni' => '45678912', 'roles' => ['acopiador']])->assertSessionHasErrors('roles');
        $this->assertSame(['proveedor'], $cuenta->fresh()->roles);
        $this->assertSame('45678912', $cuenta->fresh()->dni);
    }

    public function test_editar_proveedor_sin_cuenta_permite_crear_su_acceso_sin_cambiar_codigo(): void
    {
        $ficha = Proveedor::factory()->create(['dni' => '45678912', 'codigo' => 'PRV-HIST0001']);

        $this->get(route('admin.proveedores.edit', $ficha->id))->assertOk()->assertSee('Crear cuenta móvil')->assertSee(route('admin.proveedores.cuenta', $ficha->id), false);
        $this->post(route('admin.proveedores.cuenta', $ficha->id), ['username' => 'rosa.quispe', 'pin' => '12', 'pin_confirmation' => '12'])->assertSessionHasErrors('pin');
        $this->post(route('admin.proveedores.cuenta', $ficha->id), ['username' => 'Rosa.Quispe', 'pin' => '4826', 'pin_confirmation' => '4826'])
            ->assertRedirect(route('admin.proveedores.edit', $ficha->id))->assertSessionHasNoErrors();

        $cuenta = Usuario::query()->where('username', 'rosa.quispe')->sole();
        $this->assertSame($cuenta->id, $ficha->fresh()->usuario_id);
        $this->assertSame('PRV-HIST0001', $ficha->fresh()->codigo);
        $this->post(route('admin.proveedores.cuenta', $ficha->id), ['username' => 'otra.cuenta', 'pin' => '4826', 'pin_confirmation' => '4826'])->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('usuarios', ['username' => 'otra.cuenta']);
    }

    public function test_cuenta_de_proveedor_sin_ficha_ofrece_registrarla_desde_usuarios(): void
    {
        $cuenta = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['proveedor']]);

        $this->get(route('admin.usuarios.edit', $cuenta->id))->assertOk()->assertSee('aún no tiene ficha productiva')
            ->assertSee(route('admin.proveedores.create', ['dni' => '45678912', 'origen' => 'usuarios']));
        $this->get(route('admin.proveedores.create', ['dni' => '45678912', 'origen' => 'usuarios']))->assertOk()->assertSee('value="45678912"', false);
    }

    public function test_el_qr_usa_el_codigo_unico_y_no_cambia_al_vincular(): void
    {
        $this->post(route('admin.proveedores.store'), $this->ficha())->assertSessionHasNoErrors();
        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        $codigo = $ficha->codigo;

        $this->post(route('admin.proveedores.cuenta', $ficha->id), ['username' => 'rosa.quispe', 'pin' => '4826', 'pin_confirmation' => '4826'])->assertSessionHasNoErrors();

        $this->assertSame($codigo, $ficha->fresh()->codigo);
        $this->assertSame('ECOLECTA:PROVEEDOR:CODIGO:'.$codigo, ProveedorQr::generar($ficha->fresh()->codigo));
        $this->get(route('admin.proveedores.qr', $ficha->id))->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_vincular_no_altera_entregas_ni_liquidaciones_existentes(): void
    {
        $ficha = Proveedor::factory()->create(['dni' => '45678912']);
        $entregas = Entrega::factory()->count(3)->create(['proveedor_id' => $ficha->id]);
        $liquidacion = Liquidacion::query()->create(['proveedor_id' => $ficha->id, 'periodo_inicio' => '2026-10-19', 'periodo_fin' => '2026-10-25', 'litros_totales' => 60, 'precio_litro' => 1.8, 'monto_total' => 108, 'estado' => 'pagada', 'generada_en' => now(), 'pagada_en' => now()]);
        $antes = DB::table('entregas')->orderBy('id')->get()->toArray();

        $this->post(route('admin.proveedores.store'), $this->conCuenta(['accion_registro' => 'vincular_ficha']))->assertSessionHasNoErrors();

        $this->assertEquals($antes, DB::table('entregas')->orderBy('id')->get()->toArray());
        $this->assertSame(3, Entrega::query()->where('proveedor_id', $ficha->id)->count());
        $this->assertSame($entregas->pluck('id')->all(), Entrega::query()->where('proveedor_id', $ficha->id)->orderBy('id')->pluck('id')->all());
        $this->assertSame('pagada', $liquidacion->fresh()->estado->value);
        $this->assertSame($ficha->id, $liquidacion->fresh()->proveedor_id);
    }

    public function test_la_cuenta_creada_inicia_sesion_en_la_app_y_ve_su_ficha(): void
    {
        $this->post(route('admin.proveedores.store'), $this->conCuenta())->assertSessionHasNoErrors();
        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        Liquidacion::query()->create(['proveedor_id' => $ficha->id, 'periodo_inicio' => '2026-10-19', 'periodo_fin' => '2026-10-25', 'litros_totales' => 60, 'precio_litro' => 1.8, 'monto_total' => 108, 'estado' => 'pendiente', 'generada_en' => now()]);

        $token = $this->postJson('/api/movil/sesion', ['username' => 'rosa.quispe', 'pin' => '4826'])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/movil/proveedor/liquidaciones')->assertOk()->assertJsonFragment(['desde' => '2026-10-19', 'total' => 108]);
    }

    public function test_los_demas_roles_se_siguen_creando_desde_usuarios(): void
    {
        $this->post(route('admin.usuarios.store'), ['username' => 'nuevo.acopiador', 'dni' => '70000001', 'nombres' => 'Luis Acopio', 'roles' => ['acopiador'], 'pin' => '4321', 'pin_confirmation' => '4321'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.usuarios.index'));
        $this->post(route('admin.usuarios.store'), ['username' => 'nueva.calidad', 'dni' => '70000002', 'nombres' => 'Ana Calidad', 'roles' => ['calidad'], 'zona_id' => $this->zona->id, 'pin' => '4321', 'pin_confirmation' => '4321'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuarios', ['username' => 'nuevo.acopiador']);
        $this->assertDatabaseHas('usuarios', ['username' => 'nueva.calidad']);
        $this->assertDatabaseCount('proveedores', 0);
    }

    public function test_formulario_es_responsivo_y_accesible(): void
    {
        $this->get(route('admin.proveedores.create'))->assertOk()
            ->assertSee('data-registro-proveedor', false)->assertSee('aria-controls="campos-cuenta"', false)->assertSee('aria-expanded="false"', false)
            ->assertSee('sm:grid-cols-2', false)->assertSee('aria-labelledby="seccion-acceso"', false)
            ->assertSee('data-registro-situacion', false)->assertSee('inputmode="numeric"', false);
    }

    public function test_desde_usuarios_el_acceso_es_obligatorio_sin_casilla_y_vincula_la_ficha(): void
    {
        $sinCredenciales = $this->ficha(['origen' => 'usuarios']);
        unset($sinCredenciales['crear_cuenta']);
        $this->post(route('admin.proveedores.store'), $sinCredenciales)->assertSessionHasErrors(['username', 'pin', 'pin_confirmation']);
        $this->post(route('admin.proveedores.store'), [...$sinCredenciales, 'crear_cuenta' => '0'])->assertSessionHasErrors(['username', 'pin']);
        $this->assertDatabaseCount('proveedores', 0);

        $this->post(route('admin.proveedores.store'), [...$sinCredenciales, 'username' => 'rosa.quispe', 'pin' => '4826', 'pin_confirmation' => '4826'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.usuarios.index'));

        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        $cuenta = Usuario::query()->where('username', 'rosa.quispe')->sole();
        $this->assertSame($cuenta->id, $ficha->usuario_id);
        $this->assertSame(['proveedor'], $cuenta->roles);
        $this->get(route('admin.usuarios.index'))->assertSee('rosa.quispe');
        $this->get(route('admin.proveedores.index'))->assertSee('Rosa Quispe Mamani')->assertSee($ficha->codigo);
    }

    public function test_desde_usuarios_una_cuenta_existente_se_asocia_sin_pedir_credenciales(): void
    {
        $cuenta = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['proveedor']]);

        $this->post(route('admin.proveedores.store'), $this->ficha(['origen' => 'usuarios', 'crear_cuenta' => '1', 'accion_registro' => 'asociar_cuenta']))->assertSessionHasNoErrors();

        $this->assertSame($cuenta->id, Proveedor::query()->where('dni', '45678912')->value('usuario_id'));
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_desde_proveedores_la_ficha_se_registra_siempre_y_el_acceso_se_habilita_a_demanda(): void
    {
        $this->post(route('admin.proveedores.store'), $this->ficha(['origen' => 'proveedores', 'username' => 'ignorado', 'pin' => '1', 'pin_confirmation' => '2']))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.proveedores.index'));
        $sinAcceso = Proveedor::query()->where('dni', '45678912')->sole();
        $this->assertNull($sinAcceso->usuario_id);
        $this->assertMatchesRegularExpression('/\APRV-[A-Z0-9]{8}\z/', $sinAcceso->codigo);
        $this->assertDatabaseMissing('usuarios', ['username' => 'ignorado']);
        $this->get(route('admin.proveedores.index'))->assertSee('Rosa Quispe Mamani');
        $this->get(route('admin.usuarios.index'))->assertDontSee('Rosa Quispe Mamani');

        $this->post(route('admin.proveedores.store'), $this->conCuenta(['origen' => 'proveedores', 'dni' => '45678913', 'nombres' => 'Juan Condori Apaza', 'username' => 'juan.condori']))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.proveedores.index'))
            ->assertSessionHas('estado', fn (string $m) => str_contains($m, 'con cuenta móvil «juan.condori»'));
        $conAcceso = Proveedor::query()->where('dni', '45678913')->sole();
        $this->assertSame(Usuario::query()->where('username', 'juan.condori')->value('id'), $conAcceso->usuario_id);
        $this->get(route('admin.proveedores.index'))->assertSee('Juan Condori Apaza');
        $this->get(route('admin.usuarios.index'))->assertSee('juan.condori');
    }

    public function test_la_sincronizacion_incluye_fichas_sin_cuenta_que_no_pueden_iniciar_sesion(): void
    {
        $this->post(route('admin.proveedores.store'), $this->ficha())->assertSessionHasNoErrors();
        $ficha = Proveedor::query()->where('dni', '45678912')->sole();
        Usuario::factory()->create(['username' => 'acop.campo', 'pin_hash' => '1357', 'roles' => ['acopiador']]);

        $token = $this->postJson('/api/movil/sesion', ['username' => 'acop.campo', 'pin' => '1357'])->assertOk()->json('token');
        $exportada = collect($this->withToken($token)->getJson('/api/movil/datos')->assertOk()->json('proveedores'))->firstWhere('id', $ficha->id);
        $this->assertNotNull($exportada);
        $this->assertSame($ficha->codigo, $exportada['codigo']);
        $this->assertSame($this->zona->id, $exportada['zonaId']);
        $this->assertNull($exportada['usuarioId']);

        $this->postJson('/api/movil/sesion', ['username' => '45678912', 'pin' => '0000'])->assertUnauthorized();
        $this->assertDatabaseMissing('usuarios', ['dni' => '45678912']);
    }

    public function test_conciliacion_solo_vincula_coincidencias_fiables_con_autorizacion(): void
    {
        $fiable = Proveedor::factory()->create(['dni' => '45678912']);
        $cuentaFiable = Usuario::factory()->create(['dni' => '45678912', 'roles' => ['proveedor']]);
        $conflicto = Proveedor::factory()->create(['dni' => '11112222']);
        Usuario::factory()->create(['dni' => '11112222', 'roles' => ['acopiador']]);

        $this->artisan('proveedores:conciliar-cuentas')->expectsOutputToContain('Coincidencias fiables por DNI: 1')->assertSuccessful();
        $this->assertNull($fiable->fresh()->usuario_id);

        $this->artisan('proveedores:conciliar-cuentas', ['--vincular' => true])->assertFailed();
        $this->artisan('proveedores:conciliar-cuentas', ['--vincular' => true, '--actor' => $this->admin->id])
            ->expectsConfirmation('¿Vincular 1 fichas con sus cuentas? Quedará registrado en auditoría.', 'yes')
            ->expectsOutputToContain('Vinculaciones realizadas: 1')->assertSuccessful();

        $this->assertSame($cuentaFiable->id, $fiable->fresh()->usuario_id);
        $this->assertNull($conflicto->fresh()->usuario_id);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'proveedor', 'entidad_id' => $fiable->id, 'usuario_id' => $this->admin->id]);
    }
}
