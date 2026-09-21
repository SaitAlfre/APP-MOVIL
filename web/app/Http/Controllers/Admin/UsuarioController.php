<?php

namespace App\Http\Controllers\Admin;

use App\Application\Usuarios\ActualizarUsuarioUseCase;
use App\Application\Usuarios\BloquearUsuarioUseCase;
use App\Application\Usuarios\CambiarEstadoUsuarioUseCase;
use App\Application\Usuarios\CrearUsuarioUseCase;
use App\Application\Usuarios\DesbloquearUsuarioUseCase;
use App\Application\Usuarios\ListarUsuariosUseCase;
use App\Application\Usuarios\RestablecerCredencialesUsuarioUseCase;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UsuarioController extends Controller
{
    public function index(Request $request, ListarUsuariosUseCase $listar): View
    {
        $busqueda = $request->string('buscar')->toString() ?: null;
        $rolFiltro = $request->filled('rol') ? Rol::tryFrom($request->string('rol')->toString()) : null;
        $activoFiltro = $request->filled('activo') ? $request->boolean('activo') : null;

        return view('admin.usuarios.index', [
            'usuarios' => $listar->ejecutar($busqueda, $rolFiltro, $activoFiltro),
            'busqueda' => $busqueda,
            'rolFiltro' => $rolFiltro,
            'activoFiltro' => $activoFiltro,
            'roles' => Rol::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => null, 'roles' => Rol::cases()]);
    }

    public function store(Request $request, CrearUsuarioUseCase $crear): RedirectResponse
    {
        $datos = $this->validarDatos($request, exigirPin: true);

        try {
            $crear->ejecutar(
                username: $datos['username'],
                nombres: $datos['nombres'],
                dni: $datos['dni'],
                pinPlano: $datos['pin'],
                roles: $datos['roles'],
                actorEsAdmin: auth('operador')->user()->tieneRol('admin'),
                actorId: auth('operador')->id(),
            );
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['username' => $this->mensajeSeguro($e)])->withInput($request->only('username', 'nombres', 'dni', 'roles'));
        }

        return redirect()->route('admin.usuarios.index')->with('estado', 'Usuario creado correctamente.');
    }

    public function edit(int $usuario, UsuarioRepositoryInterface $usuarios): View
    {
        $entidad = $usuarios->buscarPorId($usuario);
        abort_if($entidad === null, 404);

        return view('admin.usuarios.form', ['usuario' => $entidad, 'roles' => Rol::cases()]);
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
                actorEsAdmin: auth('operador')->user()->tieneRol('admin'),
                actorId: auth('operador')->id(),
            );
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['roles' => $this->mensajeSeguro($e)])->withInput($request->only('nombres', 'dni', 'roles'));
        }

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

    /** @return array{username: string, nombres: string, dni: string, roles: list<Rol>, pin: ?string} */
    private function validarDatos(Request $request, bool $exigirPin): array
    {
        $reglas = [
            'nombres' => ['required', 'string', 'max:150'],
            'dni' => ['required', 'string', 'max:20'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:'.implode(',', array_map(fn (Rol $r) => $r->value, Rol::cases()))],
        ];

        if ($exigirPin) {
            $reglas['username'] = ['required', 'string', 'max:50'];
            $reglas['pin'] = ['required', 'string', 'regex:/^\d{4,8}$/'];
        }

        $validados = $request->validate($reglas, [
            'pin.regex' => 'El PIN debe tener entre 4 y 8 dígitos numéricos.',
        ]);

        return [
            'username' => $validados['username'] ?? '',
            'nombres' => $validados['nombres'],
            'dni' => $validados['dni'],
            'roles' => array_map(fn (string $r) => Rol::from($r), $validados['roles']),
            'pin' => $validados['pin'] ?? null,
        ];
    }
}
