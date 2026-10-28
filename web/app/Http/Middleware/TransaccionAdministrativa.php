<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransaccionAdministrativa
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $request->session()->forget('errors');
        DB::beginTransaction();
        try {
            // Mismo cerrojo para cuentas y fichas: serializa altas y vinculaciones concurrentes del mismo DNI.
            if ($request->routeIs('admin.usuarios.*', 'admin.proveedores.store', 'admin.proveedores.update', 'admin.proveedores.cuenta', 'admin.proveedores.vincular')) {
                DB::table('usuarios')->orderBy('id')->lockForUpdate()->get(['id']);
            }

            $response = $next($request);
            if ($response->getStatusCode() >= 400 || $request->session()->has('errors')) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
