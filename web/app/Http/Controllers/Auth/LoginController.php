<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\AutenticarOperadorUseCase;
use App\Application\Auth\CerrarSesionOperadorUseCase;
use App\Domain\Auth\Exceptions\CuentaBloqueadaException;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** El panel web es exclusivo para administradores: acopiadores y proveedores solo operan desde la app móvil. */
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
            return back()->withErrors(['username' => $e->getMessage()])->onlyInput('username');
        }

        if (! $autenticado) {
            return back()->withErrors(['username' => 'El usuario o el PIN no son correctos.'])->onlyInput('username');
        }

        if (! auth('operador')->user()->tieneRol('admin')) {
            $cerrarSesion->ejecutar();

            return back()->withErrors(['username' => 'Este panel es solo para administradores.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.proveedores.index'));
    }

    public function cerrarSesion(Request $request, CerrarSesionOperadorUseCase $cerrarSesion): RedirectResponse
    {
        $cerrarSesion->ejecutar();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
