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

class LoginController extends Controller
{
    public function mostrar(): View
    {
        return view('auth.login');
    }

    public function iniciarSesion(LoginRequest $request, AutenticarOperadorUseCase $autenticar): RedirectResponse
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

        $request->session()->regenerate();

        $usuario = auth('operador')->user();
        $destino = match (true) {
            $usuario->tieneRol('admin') => route('admin.proveedores.index'),
            $usuario->tieneRol('acopiador') => route('acopiador.home'),
            $usuario->tieneRol('proveedor') => route('proveedor.panel'),
            default => route('login'),
        };

        return redirect()->intended($destino);
    }

    public function cerrarSesion(Request $request, CerrarSesionOperadorUseCase $cerrarSesion): RedirectResponse
    {
        $cerrarSesion->ejecutar();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
