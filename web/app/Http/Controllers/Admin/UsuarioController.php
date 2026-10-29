<?php

namespace App\Http\Controllers\Admin;

use App\Application\Proveedores\ConsultarDniUseCase;
use App\Application\Usuarios\ActualizarUsuarioUseCase;
use App\Application\Usuarios\BloquearUsuarioUseCase;
use App\Application\Usuarios\CambiarEstadoUsuarioUseCase;
use App\Application\Usuarios\CamposIdentidadFormulario;
use App\Application\Usuarios\CrearUsuarioUseCase;
use App\Application\Usuarios\DesbloquearUsuarioUseCase;
use App\Application\Usuarios\GestionarAccesoUsuarioUseCase;
use App\Application\Usuarios\ListarDirectorioUsuarios;
use App\Application\Usuarios\RestablecerCredencialesUsuarioUseCase;
use App\Application\Usuarios\ResultadoAccesoUsuario;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class UsuarioController extends Controller
{
    /** Funciones de la app móvil que un administrador puede asignar a una cuenta con rol personalizado sin base. */
    private const FUNCIONES_MOVILES_DE_ROLES_PERSONALIZADOS = ['acopiador', 'calidad'];

    public function consultarDni(Request $request, ConsultarDniUseCase $consultar, ProveedorRepositoryInterface $proveedores): JsonResponse
    {
        $datos = $request->validate(['dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'], 'excluir' => ['nullable', 'integer']]);
        $existente = Usuario::query()->where('dni', $datos['dni'])->first();
        if ($existente && $existente->id !== (int) ($datos['excluir'] ?? 0)) {
            $operador = auth('operador')->user();
            $puedeEditar = $operador->puede('usuarios', 'editar') && ($this->esAdministradorOficial() || ($existente->perfil_rol_id !== null && (int) $existente->perfil_rol_id === (int) $operador->perfil_rol_id && ! $existente->tieneRol('admin')));

            return response()->json(['estado' => 'registrado', 'mensaje' => 'Este DNI ya tiene una cuenta. Revisa el usuario existente antes de crear otra.', 'url' => $puedeEditar ? route('admin.usuarios.edit', $existente->id) : route('admin.usuarios.index', ['buscar' => $datos['dni']])])->header('Cache-Control', 'no-store');
        }
        $ficha = auth('operador')->user()->puede('proveedores') ? $proveedores->buscarPorDni($datos['dni']) : null;
        if ($ficha !== null) {
            return response()->json(['estado' => 'ficha_existente', 'nombre' => $ficha->nombres, 'url' => route('admin.proveedores.show', $ficha->id),
                'mensaje' => "Este DNI tiene una ficha de proveedor ({$ficha->codigo}) sin cuenta en este formulario; no se consultó RENIEC."])->header('Cache-Control', 'no-store');
        }

        return response()->json($consultar->ejecutar($datos['dni']))->header('Cache-Control', 'no-store');
    }

    public function index(Request $request, ListarDirectorioUsuarios $listar): View
    {
        $perfiles = DB::table('perfiles_roles')->orderBy('nombre')->pluck('nombre', 'id');
        $opcionesRol = collect(Rol::cases())->mapWithKeys(fn (Rol $rol) => [$rol->value => $rol->etiqueta()])->all();
        foreach ($perfiles as $id => $nombre) {
            $opcionesRol['perfil:'.$id] = $nombre;
        }
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'rol' => ['nullable', Rule::in(array_keys($opcionesRol))],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo', 'suspendido', 'bloqueado'])],
            'bloqueo' => ['nullable', Rule::in(['temporal'])],
            'activo' => ['nullable', 'boolean'],
            'orden' => ['nullable', Rule::in(['recientes', 'nombre_asc', 'nombre_desc'])],
            'por_pagina' => ['nullable', Rule::in([10, 20, 50])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        if (! $request->filled('estado') && $request->filled('activo')) {
            $filtros['estado'] = $request->boolean('activo') ? 'activo' : 'inactivo';
        }
        if (($filtros['estado'] ?? '') === 'bloqueado') {
            // Enlaces anteriores a la pestaña «Bloqueos temporales»: ahora es un filtro avanzado.
            $filtros['estado'] = '';
            $filtros['bloqueo'] = 'temporal';
        }
        $filas = $listar->ejecutar($filtros);
        $cuentasPagina = Usuario::query()->with('fichaProveedor')->whereIn('id', collect($filas->items())->pluck('usuario_id')->filter())->get()->keyBy('id');

        return view('admin.usuarios.index', compact('filas', 'cuentasPagina', 'opcionesRol', 'filtros'));
    }

    public function create(Request $request, ZonaRepositoryInterface $zonas): View|RedirectResponse
    {
        $puedeAsignarRol = $this->esAdministradorOficial();
        $perfiles = DB::table('perfiles_roles')->where('activo', true)
            ->when(! $puedeAsignarRol, fn ($query) => $query->where('id', auth('operador')->user()->perfil_rol_id))
            ->orderBy('nombre')->get();
        if (! $request->filled('base') && ! $request->filled('perfil_rol_id')) {
            return view('admin.roles.seleccionar', ['roles' => $puedeAsignarRol ? Rol::cases() : [], 'perfiles' => $perfiles, 'puedeAsignarRol' => $puedeAsignarRol]);
        }
        abort_if(! $puedeAsignarRol && (! $request->filled('perfil_rol_id') || $request->integer('perfil_rol_id') !== (int) auth('operador')->user()->perfil_rol_id), 403);
        $perfil = $request->filled('perfil_rol_id') ? DB::table('perfiles_roles')->where('activo', true)->find($request->integer('perfil_rol_id')) : null;
        abort_if($request->filled('perfil_rol_id') && ! $perfil, 422);
        // Un rol personalizado nuevo no tiene base: sus funciones móviles se asignan explícitamente en el formulario.
        $base = $perfil !== null ? $perfil->base : $request->string('base')->toString();
        abort_unless($perfil !== null || Rol::tryFrom($base), 422);
        abort_if($base === 'admin' && (auth('operador')->user()->perfil_rol_id !== null || ! auth('operador')->user()->tieneRol('admin')), 403);
        if ($base === Rol::Proveedor->value) {
            // Un único registro de proveedores: ficha productiva y cuenta móvil en el mismo formulario.
            return redirect()->route('admin.proveedores.create', ['cuenta' => 1, 'origen' => 'usuarios']);
        }

        return view('admin.usuarios.form', ['perfil' => $perfil, 'base' => $base, 'registro' => null, 'usuario' => null, 'roles' => Rol::cases(), 'zonas' => $zonas->activas(), 'puedeAsignarRol' => $puedeAsignarRol]);
    }

    public function store(Request $request, CrearUsuarioUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request, exigirPin: true);
        if ($datos['roles'] === [Rol::Proveedor]) {
            return redirect()->route('admin.proveedores.create', ['cuenta' => 1, 'origen' => 'usuarios'])
                ->withErrors(['dni' => 'Las cuentas de proveedor se registran junto con su ficha productiva en este formulario.']);
        }

        try {
            $nuevo = $crear->ejecutar(
                username: $datos['username'],
                nombres: $datos['nombres'],
                dni: $datos['dni'],
                pinPlano: $datos['pin'],
                roles: $datos['roles'],
                actorEsAdmin: auth('operador')->user()->perfil_rol_id === null && auth('operador')->user()->tieneRol('admin'),
                actorId: auth('operador')->id(),
                zonaId: $datos['zona_id'],
                rolFuncionalOpcional: $datos['rol_funcional_opcional'],
            );
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['username' => $this->mensajeSeguro($e)])->withInput($request->only('username', 'nombres', 'dni', 'roles', 'zona_id', 'perfil_rol_id', 'datos_formulario'));
        }

        $this->guardarFormulario($request, $nuevo->id, $datos['datos_formulario']);

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario creado correctamente.');
    }

    public function edit(int $usuario, Request $request, UsuarioRepositoryInterface $usuarios, ZonaRepositoryInterface $zonas, ProveedorRepositoryInterface $proveedores): View
    {
        $registro = $this->cuentaGestionable($usuario);
        $entidad = $usuarios->buscarPorId($usuario);
        abort_if($entidad === null, 404);

        $zonasDisponibles = $zonas->activas();
        if ($entidad->zonaId && ! collect($zonasDisponibles)->contains('id', $entidad->zonaId)) {
            $zonaActual = $zonas->buscarPorId($entidad->zonaId);
            if ($zonaActual) {
                $zonasDisponibles[] = $zonaActual;
            }
        }

        $puedeAsignarRol = $this->esAdministradorOficial();
        $opcionesRol = [];
        if ($puedeAsignarRol) {
            foreach (Rol::cases() as $rol) {
                $opcionesRol[$rol->value] = $rol->etiqueta();
            }
        }
        $perfiles = DB::table('perfiles_roles')
            ->when($puedeAsignarRol, fn ($query) => $query->where('activo', true)->orWhere('id', $registro->perfil_rol_id), fn ($query) => $query->where('id', $registro->perfil_rol_id))
            ->orderBy('nombre')->get()->keyBy('id');
        foreach ($perfiles as $perfil) {
            if ($puedeAsignarRol) {
                $opcionesRol['perfil:'.$perfil->id] = $perfil->nombre.($perfil->activo ? '' : ' (inactivo)');
            }
        }
        $seleccionActual = $registro->perfil_rol_id ? 'perfil:'.$registro->perfil_rol_id : (count($registro->roles ?? []) === 1 ? $registro->roles[0] : 'actual');
        abort_if(! $puedeAsignarRol && $request->filled('rol') && $request->query('rol') !== $seleccionActual, 403);
        if ($puedeAsignarRol && ! isset($opcionesRol[$seleccionActual])) {
            $opcionesRol[$seleccionActual] = 'Conservar rol actual';
        }
        $request->validate(['rol' => ['nullable', Rule::in($puedeAsignarRol ? array_keys($opcionesRol) : [$seleccionActual])]]);
        $seleccionRol = $request->query('rol') ?: $seleccionActual;
        $perfil = str_starts_with($seleccionRol, 'perfil:') ? $perfiles->get((int) substr($seleccionRol, 7)) : null;
        $base = Rol::tryFrom($seleccionRol)?->value;

        return view('admin.usuarios.form', ['registro' => $registro, 'perfil' => $perfil, 'base' => $base, 'usuario' => $entidad, 'roles' => Rol::cases(), 'zonas' => $zonasDisponibles, 'opcionesRol' => $opcionesRol, 'seleccionRol' => $seleccionRol, 'puedeAsignarRol' => $puedeAsignarRol, 'fichaProveedor' => $proveedores->buscarPorUsuarioId($entidad->id), 'fichaMismoDni' => $proveedores->buscarPorDni($entidad->dni)]);
    }

    public function update(int $usuario, Request $request, ActualizarUsuarioUseCase $actualizar): RedirectResponse
    {
        $this->cuentaGestionable($usuario);
        $datos = $this->validarDatos($request, exigirPin: false);

        try {
            $actualizar->ejecutar(
                id: $usuario,
                nombres: $datos['nombres'],
                dni: $datos['dni'],
                roles: $datos['roles'],
                actorEsAdmin: auth('operador')->user()->perfil_rol_id === null && auth('operador')->user()->tieneRol('admin'),
                actorId: auth('operador')->id(),
                zonaId: $datos['zona_id'],
                actualizarZona: $request->exists('zona_id'),
                rolFuncionalOpcional: $datos['rol_funcional_opcional'],
            );
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['roles' => $this->mensajeSeguro($e)])->withInput($request->only('nombres', 'dni', 'roles', 'zona_id', 'perfil_rol_id', 'datos_formulario'));
        }

        $this->guardarFormulario($request, $usuario, $datos['datos_formulario']);

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(int $usuario, Request $request, CambiarEstadoUsuarioUseCase $cambiarEstado): RedirectResponse
    {
        $this->cuentaGestionable($usuario);
        $request->validate(['activo' => ['required', 'boolean']]);

        try {
            $cambiarEstado->ejecutar($usuario, $request->boolean('activo'), auth('operador')->id());
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['activo' => $this->mensajeSeguro($e)]);
        }

        return redirect()->route('admin.usuarios.index')->with('estado', 'Estado del usuario actualizado.');
    }

    public function gestionarAcceso(int $usuario, Request $request, GestionarAccesoUsuarioUseCase $gestionar): RedirectResponse
    {
        $this->cuentaGestionable($usuario);
        $request->validate([
            'suspender' => ['required', 'boolean'],
            'motivo' => ['required_if:suspender,1', 'nullable', 'string', 'max:255'],
        ]);
        try {
            $resultado = $gestionar->ejecutar($usuario, $request->boolean('suspender'), $request->input('motivo'), auth('operador')->id());
        } catch (UsuarioInvalidoException|RuntimeException $e) {
            return back()->withErrors(['motivo' => $this->mensajeSeguro($e)]);
        }
        $mensaje = $request->boolean('suspender') ? 'Acceso suspendido. Se cerraron las sesiones web y móvil.'
            : 'Acceso restaurado. Se requiere una nueva autenticación; se conservan los bloqueos temporales.';
        $mensaje .= match ($resultado->ficha) {
            ResultadoAccesoUsuario::FICHA_SUSPENDIDA => ' Su ficha de proveedor también quedó Suspendida.',
            ResultadoAccesoUsuario::FICHA_RESTAURADA => ' Su ficha de proveedor volvió a estar Activa.',
            ResultadoAccesoUsuario::SIN_FICHA => ' La cuenta no tiene una ficha de proveedor vinculada; solo se cambió la cuenta.',
            default => '',
        };
        $aviso = match ($resultado->ficha) {
            ResultadoAccesoUsuario::FICHA_RETIRADA => 'La ficha de proveedor vinculada está Retirada y conserva ese estado.',
            ResultadoAccesoUsuario::FICHA_YA_SUSPENDIDA => 'La ficha de proveedor ya estaba Suspendida por otra causa; se conserva esa suspensión.',
            ResultadoAccesoUsuario::FICHA_SUSPENSION_INDEPENDIENTE => 'La ficha de proveedor sigue Suspendida: esa suspensión no la originó «Suspender acceso» (sanción independiente o registro anterior sin origen conocido). Revísala en Proveedores si corresponde reactivarla.',
            default => null,
        };

        return back()->with('estado', $mensaje)->with('aviso', $aviso);
    }

    public function bloquear(int $usuario, Request $request, BloquearUsuarioUseCase $bloquear): RedirectResponse
    {
        $this->cuentaGestionable($usuario);
        $request->validate(['motivo' => ['required', 'string', 'max:255']]);

        try {
            $bloquear->ejecutar($usuario, $request->string('motivo')->toString(), auth('operador')->id());
        } catch (UsuarioInvalidoException|RuntimeException $e) {
            return back()->withErrors(['motivo' => $this->mensajeSeguro($e)]);
        }

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario bloqueado.');
    }

    public function desbloquear(int $usuario, DesbloquearUsuarioUseCase $desbloquear): RedirectResponse
    {
        $this->cuentaGestionable($usuario);
        $desbloquear->ejecutar($usuario, auth('operador')->id());

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario desbloqueado.');
    }

    public function restablecerCredenciales(int $usuario, Request $request, RestablecerCredencialesUsuarioUseCase $restablecer): RedirectResponse
    {
        $this->cuentaGestionable($usuario);
        $request->validate(['pin' => ['required', 'string', 'regex:/\A[0-9]{4}\z/'], 'pin_confirmation' => ['required', 'same:pin']]);

        $exceptoSessionId = $usuario === auth('operador')->id() ? $request->session()->getId() : null;

        try {
            $restablecer->ejecutar($usuario, $request->string('pin')->toString(), auth('operador')->id(), $exceptoSessionId);
        } catch (UsuarioInvalidoException|RuntimeException $e) {
            return back()->withErrors(['pin' => $this->mensajeSeguro($e)]);
        }

        return redirect()->route('admin.usuarios.index')->with('estado', 'Credenciales restablecidas. El usuario deberá iniciar sesión de nuevo con el PIN nuevo.');
    }

    private function validarFormulario(Request $request): array
    {
        $request->validate(['perfil_rol_id' => ['nullable', 'integer', 'exists:perfiles_roles,id']]);
        if (! $request->filled('perfil_rol_id')) {
            return [];
        }
        $perfil = DB::table('perfiles_roles')->find($request->integer('perfil_rol_id'));
        $anterior = Usuario::find($request->route('usuario'));
        if (! $perfil || (! $perfil->activo && $anterior?->perfil_rol_id != $perfil->id)) {
            throw ValidationException::withMessages(['perfil_rol_id' => 'Selecciona un rol activo para registrar al usuario.']);
        }
        $request->merge(['roles' => $this->rolesDelPerfil($request, $perfil)]);
        $request->attributes->set('rol_funcional_opcional', $perfil->base === null);
        $campos = json_decode($perfil->campos, true);
        $identidad = CamposIdentidadFormulario::reconocer($campos);
        $adicionales = $request->input('datos_formulario', []);
        if (! is_array($adicionales)) {
            throw ValidationException::withMessages(['datos_formulario' => 'Revisa los datos del formulario.']);
        }
        foreach ($identidad as $clave => $destino) {
            if (! $request->exists($destino) && array_key_exists($clave, $adicionales)) {
                $request->merge([$destino => $adicionales[$clave]]);
            }
            $adicionales[$clave] = $request->input($destino);
        }
        $request->merge(['datos_formulario' => $adicionales]);
        $reglas = [];
        foreach (json_decode($perfil->campos, true) as $clave => $campo) {
            $reglas['datos_formulario.'.$clave] = [$campo['obligatorio'] ? 'required' : 'nullable', match ($campo['tipo']) {
                'numero' => 'numeric', 'fecha' => 'date', default => 'string'
            }];
            if (isset($identidad[$clave])) {
                $reglas['datos_formulario.'.$clave] = ['required', 'string', 'max:'.($identidad[$clave] === 'dni' ? '20' : '150')];
            }
            if ($campo['tipo'] === 'texto') {
                $reglas['datos_formulario.'.$clave][] = 'max:1000';
            }
        }

        $etiquetas = [];
        foreach (json_decode($perfil->campos, true) as $clave => $campo) {
            $etiquetas['datos_formulario.'.$clave] = $campo['etiqueta'];
        }
        $validados = $request->validate($reglas, [], $etiquetas)['datos_formulario'] ?? [];

        return array_replace(array_fill_keys(array_keys(json_decode($perfil->campos, true)), null), $validados);
    }

    /**
     * Rol personalizado anterior (con base): conserva las funciones móviles de esa base, como hasta ahora.
     * Rol personalizado nuevo (sin base): sin funciones móviles salvo asignación explícita de un administrador oficial.
     *
     * @return list<string>
     */
    private function rolesDelPerfil(Request $request, object $perfil): array
    {
        if ($perfil->base !== null) {
            return [$perfil->base];
        }
        if (! $this->esAdministradorOficial()) {
            $anterior = Usuario::find($request->route('usuario'));

            return $anterior !== null && (int) $anterior->perfil_rol_id === (int) $perfil->id ? ($anterior->roles ?? []) : [];
        }
        $funcion = $request->validate(
            ['funcion_movil' => ['nullable', 'string', Rule::in(self::FUNCIONES_MOVILES_DE_ROLES_PERSONALIZADOS)]],
            ['funcion_movil.in' => 'Elige una función móvil válida: Acopiador, Calidad o ninguna.'],
        )['funcion_movil'] ?? null;

        return $funcion !== null ? [$funcion] : [];
    }

    private function guardarFormulario(Request $request, int $id, array $datos): void
    {
        $registro = Usuario::findOrFail($id);
        $perfilAnterior = $registro->perfil_rol_id;
        $registro->datos_formulario = array_replace($registro->datos_formulario ?? [], $datos);
        $registro->perfil_rol_id = $request->filled('perfil_rol_id') ? $request->integer('perfil_rol_id') : null;
        $registro->save();
        if ($perfilAnterior !== $registro->perfil_rol_id) {
            app(UsuarioRepositoryInterface::class)->invalidarSesiones($id);
        }
    }

    /** @return array{username: string, nombres: string, dni: string, roles: list<Rol>, pin: ?string, zona_id: ?int, rol_funcional_opcional: bool, datos_formulario: array<string, mixed>} */
    private function validarDatos(Request $request, bool $exigirPin): array
    {
        $anterior = $exigirPin ? null : Usuario::findOrFail($request->route('usuario'));
        if (! $this->esAdministradorOficial()) {
            $perfilEsperado = $exigirPin ? auth('operador')->user()->perfil_rol_id : $anterior->perfil_rol_id;
            $perfilSolicitado = $request->filled('perfil_rol_id') ? $request->integer('perfil_rol_id') : null;
            if ($perfilSolicitado !== $perfilEsperado) {
                throw ValidationException::withMessages(['perfil_rol_id' => 'Solo un administrador puede asignar o cambiar el rol de una cuenta.']);
            }
        }
        foreach (['nombres', 'dni'] as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => trim($request->input($campo))]);
            }
        }
        if ($exigirPin && is_string($request->input('username'))) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }
        $datosFormulario = $this->validarFormulario($request);
        $reglas = [
            'nombres' => ['required', 'string', 'max:150'],
            'dni' => ['required', 'string', 'max:20'],
            'roles' => $request->attributes->get('rol_funcional_opcional') ? ['present', 'array'] : ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:'.implode(',', array_map(fn (Rol $r) => $r->value, Rol::cases()))],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
        ];

        if ($exigirPin || $request->input('dni') !== $anterior?->dni) {
            $reglas['dni'] = ['required', 'string', 'regex:/^[0-9]{8}$/', Rule::unique('usuarios', 'dni')->ignore($anterior?->id)];
        }
        if ($exigirPin) {
            $reglas['username'] = ['required', 'string', 'regex:/^[a-z0-9._]{3,30}$/', Rule::unique('usuarios', 'username')];
            $reglas['pin'] = ['required', 'string', 'regex:/\A[0-9]{4}\z/'];
            if ($request->exists('pin_confirmation')) {
                $reglas['pin_confirmation'] = ['required', 'same:pin'];
            }
        }

        $validados = $request->validate($reglas, [
            'dni.unique' => 'Este DNI ya tiene una cuenta. Edita el usuario existente.',
            'dni.regex' => 'Escribe un DNI de 8 dígitos.',
            'username.unique' => 'Este nombre de usuario ya está ocupado. Elige otro.',
            'username.regex' => 'Usa entre 3 y 30 caracteres: minúsculas, números, punto o guion bajo.',
            'pin_confirmation.same' => 'Los PIN no coinciden. Escríbelos de nuevo.',
            'pin_confirmation.required' => 'Repite el PIN para confirmar.',
            'pin.regex' => 'El PIN debe tener exactamente 4 dígitos numéricos.',
        ]);

        if (! $this->esAdministradorOficial() && $anterior) {
            $rolesAnteriores = $anterior->roles ?? [];
            $rolesSolicitados = $validados['roles'] ?? [];
            sort($rolesAnteriores);
            sort($rolesSolicitados);
            if ($rolesAnteriores !== $rolesSolicitados) {
                throw ValidationException::withMessages(['roles' => 'Solo un administrador puede asignar o cambiar el rol de una cuenta.']);
            }
        }

        return [
            'username' => isset($validados['username']) ? strtolower(trim($validados['username'])) : '',
            'datos_formulario' => $datosFormulario,
            'nombres' => $validados['nombres'],
            'dni' => $validados['dni'],
            'roles' => array_map(fn (string $r) => Rol::from($r), $validados['roles'] ?? []),
            'rol_funcional_opcional' => (bool) $request->attributes->get('rol_funcional_opcional', false),
            'pin' => $validados['pin'] ?? null,
            'zona_id' => isset($validados['zona_id']) ? (int) $validados['zona_id'] : null,
        ];
    }

    private function esAdministradorOficial(): bool
    {
        $operador = auth('operador')->user();

        return $operador->perfil_rol_id === null && $operador->tieneRol('admin');
    }

    private function cuentaGestionable(int $id): Usuario
    {
        $cuenta = Usuario::findOrFail($id);
        $operador = auth('operador')->user();
        abort_if(! $this->esAdministradorOficial() && ($cuenta->perfil_rol_id === null || (int) $cuenta->perfil_rol_id !== (int) $operador->perfil_rol_id || $cuenta->tieneRol('admin')), 403, 'Solo puedes gestionar cuentas de tu mismo rol.');

        return $cuenta;
    }
}
