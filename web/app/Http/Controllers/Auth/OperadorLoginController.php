<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\AutenticarOperadorUseCase;
use App\Application\Auth\CerrarSesionOperadorUseCase;
use App\Domain\Auth\Exceptions\CuentaBloqueadaException;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginOperadorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperadorLoginController extends Controller
{
    public function mostrar(): View
    {
        return view('auth.operador-login');
    }

    public function iniciarSesion(LoginOperadorRequest $request, AutenticarOperadorUseCase $autenticar): RedirectResponse
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

        return redirect()->intended(route('acopiador.home'));
    }

    public function cerrarSesion(Request $request, CerrarSesionOperadorUseCase $cerrarSesion): RedirectResponse
    {
        $cerrarSesion->ejecutar();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('acopiador.login');
    }
}
