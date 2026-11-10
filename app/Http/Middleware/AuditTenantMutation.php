<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditTenantMutation
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $response->getStatusCode() >= 400) {
            return $response;
        }

        $parametros = collect($request->route()?->parameters() ?? [])
            ->map(fn ($valor) => is_object($valor) && method_exists($valor, 'getKey')
                ? $valor->getKey()
                : $valor)
            ->all();

        try {
            $this->auditoria->registrar(
                $request->method(),
                $request->route()?->getName() ?? 'ruta_sin_nombre',
                collect($parametros)->first(),
                null,
                [
                    'parametros_ruta' => $parametros,
                    'campos_recibidos' => collect($request->except([
                        'password', 'password_confirmation', 'token', '_token', '_method',
                    ]))->keys()->values()->all(),
                ]
            );
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
