<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$codigos): Response
    {
        $usuario = $request->user();
        abort_unless($usuario && in_array($usuario->roleCode(), $codigos, true), 403);

        return $next($request);
    }
}
