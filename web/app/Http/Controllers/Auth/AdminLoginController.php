<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\AutenticarAdminUseCase;
use App\Application\Auth\CerrarSesionAdminUseCase;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginAdminRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function mostrar(): View
    {
        return view('auth.login');
    }

    public function iniciarSesion(LoginAdminRequest $request, AutenticarAdminUseCase $autenticar): RedirectResponse
    {
        try {
            $autenticado = $autenticar->ejecutar(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
                $request->boolean('recordar'),
            );
        } catch (CuentaInactivaException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->onlyInput('email');
        }

        if (! $autenticado) {
            return back()->withErrors(['email' => 'Las credenciales no coinciden con nuestros registros.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.proveedores.index'));
    }

    public function cerrarSesion(Request $request, CerrarSesionAdminUseCase $cerrarSesion): RedirectResponse
    {
        $cerrarSesion->ejecutar();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
