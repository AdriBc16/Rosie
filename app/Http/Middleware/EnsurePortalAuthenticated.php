<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('portal_user')) {
            return redirect()->route('portal.login')
                ->with('auth_error', 'Primero debes iniciar sesion.');
        }

        return $next($request);
    }
}
