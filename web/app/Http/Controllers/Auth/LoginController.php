<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\AutenticarOperadorUseCase;
use App\Application\Auth\CerrarSesionOperadorUseCase;
use App\Domain\Auth\Exceptions\CuentaBloqueadaException;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** El panel web es exclusivo para roles con acceso web (ver Rol::accesoWeb()): acopiadores y proveedores solo operan desde la app móvil. */
class LoginController extends Controller
{
    public function mostrar(): View
    {
        return view('auth.login');
    }

    public function iniciarSesion(LoginRequest $request, AutenticarOperadorUseCase $autenticar, CerrarSesionOperadorUseCase $cerrarSesion): RedirectResponse
    {
        try {
            $autenticado = $autenticar->ejecutar(
                $request->string('username')->toString(),
                $request->string('pin')->toString(),
            );
        } catch (CuentaInactivaException|CuentaBloqueadaException $e) {
            $this->registrarAcceso($request, 'acceso_fallido');

            return back()->withErrors(['username' => 'No se pudo iniciar sesión. Revisa tus credenciales o contacta al administrador.'])->onlyInput('username');
        }

        if (! $autenticado) {
            $this->registrarAcceso($request, 'acceso_fallido');

            return back()->withErrors(['username' => 'No se pudo iniciar sesión. Revisa tus credenciales o contacta al administrador.'])->onlyInput('username');
        }

        if (! auth('operador')->user()->accesoWeb()) {
            $this->registrarAcceso($request, 'acceso_fallido');
            $cerrarSesion->ejecutar();

            return back()->withErrors(['username' => 'Tu perfil no tiene acceso al panel administrativo.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('version_sesion.'.auth('operador')->id(), (int) auth('operador')->user()->version_sesion);
        $this->registrarAcceso($request, 'iniciar_sesion');

        return redirect()->intended(route('admin.dashboard.index'));
    }

    public function cerrarSesion(Request $request, CerrarSesionOperadorUseCase $cerrarSesion): RedirectResponse
    {
        $this->registrarAcceso($request, 'cerrar_sesion');
        $cerrarSesion->ejecutar();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function registrarAcceso(Request $request, string $accion): void
    {
        $id = auth('operador')->id() ?? Usuario::query()->where('username', $request->string('username')->toString())->value('id');
        if ($id !== null) {
            Auditoria::query()->create([
                'entidad' => 'sesion', 'entidad_id' => $id, 'usuario_id' => $id,
                'accion' => $accion, 'ocurrido_en' => now(),
                'motivo' => $accion === 'acceso_fallido' ? 'Intento de acceso a la cuenta; identidad no verificada.' : null,
            ]);
        }
    }
}
