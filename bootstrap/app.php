<?php

use App\Http\Middleware\EstablecerEmpresa;
use App\Http\Middleware\PermisosEmpresa;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'empresa' => EstablecerEmpresa::class,
            'permisos.empresa' => PermisosEmpresa::class,
        ]);
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EstablecerEmpresa::class
        );
        $middleware->validateCsrfTokens(except: [
            'iclock',
            'iclock/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
