<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $portalUser = $request->session()->get('portal_user');

        if (!$portalUser || ($portalUser['role'] ?? null) !== $role) {
            return redirect()->route('portal.login')
                ->with('auth_error', 'No tienes permisos para entrar a esa pantalla.');
        }

        return $next($request);
    }
}
