<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermisosEmpresa
{
    public function handle(Request $request, Closure $next)
    {
        $rol = $request->user()->rol;
        abort_unless($rol && $rol->estado, 403, 'Rol deshabilitado.');
        $nombre = strtoupper(trim($rol->nombre));
        abort_unless(in_array($nombre, ['ADMINISTRADOR', 'GERENTE', 'TRABAJADOR'], true), 403);

        // Estas pantallas gestionan configuración y personal de la empresa.
        if ($request->is('areas', 'areas/*', 'trabajadores', 'trabajadores/*', 'feriados', 'feriados/*', 'biometrico', 'biometrico/*', 'usuarios', 'usuarios/*', 'roles', 'roles/*')
            || $request->routeIs('asistencias.procesar', 'marcaciones.store', 'marcaciones.anular', 'marcaciones.manual', 'marcaciones.marcarAhora')) {
            abort_unless($nombre === 'ADMINISTRADOR', 403);
        }

        return $next($request);
    }
}
