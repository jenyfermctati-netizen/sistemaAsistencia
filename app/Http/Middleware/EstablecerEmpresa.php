<?php

namespace App\Http\Middleware;

use App\Models\EmpresaUsuario;
use App\Tenancy\EmpresaActual;
use Closure;
use Illuminate\Http\Request;

class EstablecerEmpresa
{
    public function handle(Request $request, Closure $next)
    {
        $contexto = app(EmpresaActual::class);
        $anterior = $contexto->empresa();
        $contexto->establecer(null);

        try {
            $usuario = $request->user();
            abort_unless($usuario && $usuario->estado, 403, 'Cuenta deshabilitada.');
            $id = $request->session()->get('empresa_id');
            $membresias = EmpresaUsuario::where('user_id', $usuario->id)
                ->where('estado', true)->whereHas('empresa', fn ($q) => $q->where('estado', true));

            // Una selección revocada no debe convertirse en acceso a otra empresa.
            if ($id !== null) {
                $membresia = (clone $membresias)->where('empresa_id', $id)->first();
                abort_unless($membresia, 403, 'Ya no tienes acceso a la empresa seleccionada.');
            } else {
                $membresia = $membresias->orderBy('empresa_id')->first();
                abort_unless($membresia, 403, 'No tienes empresas activas asignadas.');
                $request->session()->put('empresa_id', $membresia->empresa_id);
            }

            $contexto->establecer($membresia->empresa);
            $usuario->unsetRelation('rol')->unsetRelation('trabajador');

            return $next($request);
        } finally {
            $contexto->establecer($anterior);
            $request->user()?->unsetRelation('rol')->unsetRelation('trabajador');
        }
    }
}
