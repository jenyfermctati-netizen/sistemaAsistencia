<?php

namespace App\Tenancy;

use Illuminate\Validation\DatabasePresenceVerifier;

class VerificadorPresenciaEmpresa extends DatabasePresenceVerifier
{
    protected function table($table)
    {
        $query = parent::table($table);
        if (in_array($table, TablasEmpresa::NOMBRES, true)) {
            $id = app(EmpresaActual::class)->id();
            $id === null ? $query->whereRaw('1 = 0') : $query->where('empresa_id', $id);
        }

        return $query;
    }
}
