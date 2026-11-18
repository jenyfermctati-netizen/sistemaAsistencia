<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHumanResourcesAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('RECURSOS_HUMANOS')) {
            return $next($request);
        }

        // Deny new routes by default. Controllers also enforce ownership and tenant scope.
        $route = $request->route()->getName();
        $allowed = [
            'dashboard', 'trabajadores.index', 'trabajadores.store', 'trabajadores.update',
            'trabajadores.estado', 'trabajadores.consultar-dni', 'trabajadores.horarios',
            'trabajadores.horarios.store', 'trabajadores.horarios.update',
            'marcaciones.index', 'marcaciones.manual', 'marcaciones.store', 'marcaciones.marcarAhora',
            'asistencias.index', 'asistencias.procesar', 'feriados.index', 'feriados.store',
            'feriados.update', 'feriados.estado', 'feriados.sincronizar',
            'solicitudes.index', 'solicitudes.store', 'solicitudes.update', 'solicitudes.cancelar',
            'solicitudes.revisar', 'solicitudes.archivo.ver', 'solicitudes.archivo.descargar',
            'vacaciones.index', 'vacaciones.store', 'vacaciones.update', 'vacaciones.revisar',
            'vacaciones.cancelar', 'vacaciones.reprogramar', 'asignaciones-campo.index',
            'marcaciones-campo.evidencia', 'marcaciones-campo.revisar', 'reportesAvance.index', 'reportesAvance.revisar',
            'reportesAvance.actividades.archivo', 'reportesAvance.actividades.descargar',
            'dashboard.reporteControl.pdf', 'dashboard.reporteControl.excel',
        ];
        abort_unless(in_array($route, $allowed, true), 403);

        return $next($request);
    }
}
