<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        abort_unless($usuario && $usuario->estado, 403);

        $usuario->loadMissing('rol');
        abort_unless(
            $usuario->empresa_id === null
                && $usuario->rol?->ambito === 'GLOBAL'
                && $usuario->rol?->codigo === 'SUPERADMIN',
            403
        );

        return $next($request);
    }
}
