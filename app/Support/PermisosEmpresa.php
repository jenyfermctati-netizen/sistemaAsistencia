<?php

namespace App\Support;

use App\Models\User;

final class PermisosEmpresa
{
    public const RRHH = [
        'personal.gestionar', 'horarios.gestionar', 'feriados.gestionar',
        'asistencias.gestionar', 'marcaciones.registrar', 'solicitudes.gestionar',
        'vacaciones.gestionar', 'campo.revisar', 'reportes.consultar', 'reportes.revisar',
    ];

    public static function permite(User $user, string $permiso): bool
    {
        if (! $user->empresa_id || ! $user->estado || ! $user->rol?->estado
            || $user->rol?->ambito !== 'EMPRESA') {
            return false;
        }

        return $user->hasRole('ADMINISTRADOR')
            || ($user->hasRole('RECURSOS_HUMANOS') && in_array($permiso, self::RRHH, true));
    }
}
