<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPortalAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $portalUser = $request->session()->get('portal_user');

        if (!$portalUser) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Ya estas autenticado.',
            'role' => $portalUser['role']
        ]);
    }
}
