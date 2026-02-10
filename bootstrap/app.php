<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\AuthenticateStorage;
use App\Http\Middleware\ValidateWebSocketKey;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias 'auth' para usar nuestro Authenticate personalizado (redirección a plataforma Orchid)
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'ws.key' => ValidateWebSocketKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
