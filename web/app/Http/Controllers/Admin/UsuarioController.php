<?php

namespace App\Http\Controllers\Admin;

use App\Application\Proveedores\ConsultarDniUseCase;
use App\Application\Usuarios\ActualizarUsuarioUseCase;
use App\Application\Usuarios\BloquearUsuarioUseCase;
use App\Application\Usuarios\CambiarEstadoUsuarioUseCase;
use App\Application\Usuarios\CamposIdentidadFormulario;
use App\Application\Usuarios\CrearUsuarioUseCase;
use App\Application\Usuarios\DesbloquearUsuarioUseCase;
use App\Application\Usuarios\ListarDirectorioUsuarios;
use App\Application\Usuarios\RestablecerCredencialesUsuarioUseCase;
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
    public function consultarDni(Request $request, ConsultarDniUseCase $consultar): JsonResponse
    {
        $datos = $request->validate(['dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'], 'excluir' => ['nullable', 'integer']]);
        $existente = Usuario::query()->where('dni', $datos['dni'])->first();
        if ($existente && $existente->id !== (int) ($datos['excluir'] ?? 0)) {
            return response()->json(['estado' => 'registrado', 'mensaje' => 'Este DNI ya tiene una cuenta. Revisa el usuario existente antes de crear otra.', 'url' => route('admin.usuarios.edit', $existente->id)])->header('Cache-Control', 'no-store');
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
            'estado' => ['nullable', Rule::in(['activo', 'inactivo', 'bloqueado'])],
            'activo' => ['nullable', 'boolean'],
            'orden' => ['nullable', Rule::in(['recientes', 'nombre_asc', 'nombre_desc'])],
            'por_pagina' => ['nullable', Rule::in([10, 20, 50])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        if (! $request->filled('estado') && $request->filled('activo')) {
            $filtros['estado'] = $request->boolean('activo') ? 'activo' : 'inactivo';
        }
        $filas = $listar->ejecutar($filtros);
        $cuentasPagina = Usuario::query()->whereIn('id', collect($filas->items())->pluck('usuario_id')->filter())->get()->keyBy('id');

        return view('admin.usuarios.index', compact('filas', 'cuentasPagina', 'opcionesRol', 'filtros'));
    }

    public function create(Request $request, ZonaRepositoryInterface $zonas): View|RedirectResponse
    {
        if (! $request->filled('base') && ! $request->filled('perfil_rol_id')) {
            return view('admin.roles.seleccionar', ['roles' => array_values(array_filter(Rol::cases(), fn (Rol $rol) => ($rol !== Rol::Admin || (auth('operador')->user()->perfil_rol_id === null && auth('operador')->user()->tieneRol('admin'))))), 'perfiles' => DB::table('perfiles_roles')->where('activo', true)->orderBy('nombre')->get()]);
        }
        $perfil = $request->filled('perfil_rol_id') ? DB::table('perfiles_roles')->where('activo', true)->find($request->integer('perfil_rol_id')) : null;
        abort_if($request->filled('perfil_rol_id') && ! $perfil, 422);
        $base = $perfil?->base ?? $request->string('base')->toString();
        abort_unless(Rol::tryFrom($base), 422);

        return view('admin.usuarios.form', ['perfil' => $perfil, 'base' => $base, 'registro' => null, 'usuario' => null, 'roles' => Rol::cases(), 'zonas' => $zonas->activas()]);
    }

    public function store(Request $request, CrearUsuarioUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request, exigirPin: true);

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
            );
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['username' => $this->mensajeSeguro($e)])->withInput($request->only('username', 'nombres', 'dni', 'roles', 'zona_id', 'perfil_rol_id', 'datos_formulario'));
        }

        $this->guardarFormulario($request, $nuevo->id, $datos['datos_formulario']);

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario creado correctamente.');
    }

    public function edit(int $usuario, UsuarioRepositoryInterface $usuarios, ZonaRepositoryInterface $zonas): View
    {
        $entidad = $usuarios->buscarPorId($usuario);
        abort_if($entidad === null, 404);

        $zonasDisponibles = $zonas->activas();
        if ($entidad->zonaId && ! collect($zonasDisponibles)->contains('id', $entidad->zonaId)) {
            $zonaActual = $zonas->buscarPorId($entidad->zonaId);
            if ($zonaActual) {
                $zonasDisponibles[] = $zonaActual;
            }
        }

        return view('admin.usuarios.form', ['registro' => Usuario::findOrFail($usuario), 'perfil' => DB::table('perfiles_roles')->find(Usuario::findOrFail($usuario)->perfil_rol_id), 'base' => null, 'usuario' => $entidad, 'roles' => Rol::cases(), 'zonas' => $zonasDisponibles]);
    }

    public function update(int $usuario, Request $request, ActualizarUsuarioUseCase $actualizar): RedirectResponse
    {
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
            );
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['roles' => $this->mensajeSeguro($e)])->withInput($request->only('nombres', 'dni', 'roles', 'zona_id', 'perfil_rol_id', 'datos_formulario'));
        }

        $this->guardarFormulario($request, $usuario, $datos['datos_formulario']);

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(int $usuario, Request $request, CambiarEstadoUsuarioUseCase $cambiarEstado): RedirectResponse
    {
        $request->validate(['activo' => ['required', 'boolean']]);

        try {
            $cambiarEstado->ejecutar($usuario, $request->boolean('activo'), auth('operador')->id());
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['activo' => $this->mensajeSeguro($e)]);
        }

        return redirect()->route('admin.usuarios.index')->with('estado', 'Estado del usuario actualizado.');
    }

    public function bloquear(int $usuario, Request $request, BloquearUsuarioUseCase $bloquear): RedirectResponse
    {
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
        $desbloquear->ejecutar($usuario, auth('operador')->id());

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario desbloqueado.');
    }

    public function restablecerCredenciales(int $usuario, Request $request, RestablecerCredencialesUsuarioUseCase $restablecer): RedirectResponse
    {
        $request->validate(['pin' => ['required', 'string']]);

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
        $request->merge(['roles' => [$perfil->base]]);
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

    /** @return array{username: string, nombres: string, dni: string, roles: list<Rol>, pin: ?string, zona_id: ?int} */
    private function validarDatos(Request $request, bool $exigirPin): array
    {
        foreach (['nombres', 'dni'] as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => trim($request->input($campo))]);
            }
        }
        if ($exigirPin && is_string($request->input('username'))) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }
        $datosFormulario = $this->validarFormulario($request);
        $anterior = $exigirPin ? null : Usuario::findOrFail($request->route('usuario'));
        $reglas = [
            'nombres' => ['required', 'string', 'max:150'],
            'dni' => ['required', 'string', 'max:20'],
            'roles' => ['required', 'array', 'min:1'],
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

        return [
            'username' => isset($validados['username']) ? strtolower(trim($validados['username'])) : '',
            'datos_formulario' => $datosFormulario,
            'nombres' => $validados['nombres'],
            'dni' => $validados['dni'],
            'roles' => array_map(fn (string $r) => Rol::from($r), $validados['roles']),
            'pin' => $validados['pin'] ?? null,
            'zona_id' => isset($validados['zona_id']) ? (int) $validados['zona_id'] : null,
        ];
    }
}
