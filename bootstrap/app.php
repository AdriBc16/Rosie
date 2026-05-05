<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
            'auth.portal' => \App\Http\Middleware\EnsurePortalAuthenticated::class,
            'guest.portal' => \App\Http\Middleware\RedirectIfPortalAuthenticated::class,
            'portal.role' => \App\Http\Middleware\EnsurePortalRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
