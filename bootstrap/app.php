<?php

use App\Http\Middleware\AuditTenantMutation;
use App\Http\Middleware\EnsureEmpresaContext;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyBiometricDevice;
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
            'empresa' => EnsureEmpresaContext::class,
            'role' => EnsureRole::class,
            'superadmin' => EnsureSuperAdmin::class,
            'biometrico.dispositivo' => IdentifyBiometricDevice::class,
            'auditoria.empresa' => AuditTenantMutation::class,
        ]);
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureEmpresaContext::class
        );

        $middleware->validateCsrfTokens(except: [
            'iclock',
            'iclock/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
