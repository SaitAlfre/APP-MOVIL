<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRol
{
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        abort_unless(auth('operador')->user()?->tieneRol($rol), 403);

        return $next($request);
    }
}
