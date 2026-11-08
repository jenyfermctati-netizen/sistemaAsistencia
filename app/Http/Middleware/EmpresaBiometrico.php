<?php

namespace App\Http\Middleware;

use App\Models\DispositivoBiometrico;
use App\Models\Empresa;
use App\Tenancy\EmpresaActual;
use Closure;
use Illuminate\Http\Request;

class EmpresaBiometrico
{
    public function handle(Request $request, Closure $next)
    {
        $contexto = app(EmpresaActual::class);
        $contexto->establecer(null);
        try {
            $serial = trim((string) $request->query('SN'));
            $dispositivo = DispositivoBiometrico::withoutGlobalScope('empresa')->where('serial', $serial)->first();
            abort_unless($dispositivo, 403, 'Dispositivo no registrado.');
            $token = (string) ($request->header('X-Device-Token') ?: $request->query('token', ''));
            $autorizado = $dispositivo->token_hash && $token !== ''
                && hash_equals($dispositivo->token_hash, hash('sha256', $token));
            abort_unless($autorizado, 403, 'Dispositivo no autorizado.');
            $empresa = Empresa::where('estado', true)->findOrFail($dispositivo->empresa_id);

            return $contexto->ejecutar($empresa, fn () => $next($request));
        } finally {
            $contexto->establecer(null);
        }
    }
}
