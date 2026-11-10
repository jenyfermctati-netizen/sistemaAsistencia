<?php

namespace App\Http\Middleware;

use App\Models\DispositivoBiometrico;
use App\Support\EmpresaContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IdentifyBiometricDevice
{
    public function __construct(private readonly EmpresaContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $serial = trim((string) $request->query('SN'));
        $dispositivo = $serial !== ''
            ? DispositivoBiometrico::withoutGlobalScopes()->with('empresa')
                ->where('serial', $serial)->where('estado', true)->first()
            : null;

        $secreto = $request->header('X-Device-Token') ?: $request->query('AUTH');

        if (! $dispositivo || ! $dispositivo->empresa?->estaActiva() || ! $dispositivo->verificarSecreto($secreto)) {
            Log::warning('SENSEFACE dispositivo no autorizado', [
                'sn' => $serial ?: null,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Dispositivo no autorizado.');
        }

        $request->attributes->set('dispositivo_biometrico', $dispositivo);

        return $this->context->run($dispositivo->empresa, fn () => $next($request));
    }
}
