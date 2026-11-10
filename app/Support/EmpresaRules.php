<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class EmpresaRules
{
    public static function exists(string $tabla, string $columna = 'id')
    {
        return Rule::exists($tabla, $columna)
            ->where(fn ($query) => $query->where('empresa_id', app(EmpresaContext::class)->id()));
    }

    public static function unique(string $tabla, string $columna)
    {
        return Rule::unique($tabla, $columna)
            ->where(fn ($query) => $query->where('empresa_id', app(EmpresaContext::class)->id()));
    }
}
