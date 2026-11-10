<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\EmpresaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaContext
{
    public function __construct(private readonly EmpresaContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        abort_unless($usuario && $usuario->estado, 403);

        $usuario->loadMissing(['rol', 'empresa']);
        abort_unless($usuario->rol?->ambito === 'EMPRESA', 403);
        abort_unless($usuario->empresa_id && $usuario->empresa, 403);

        if (! $usuario->empresa->estaActiva()) {
            abort(403, $usuario->empresa->estado === Empresa::SUSPENDIDA
                ? 'La empresa se encuentra suspendida.'
                : 'La empresa se encuentra inactiva.');
        }

        return $this->context->run($usuario->empresa, fn () => $next($request));
    }
}
