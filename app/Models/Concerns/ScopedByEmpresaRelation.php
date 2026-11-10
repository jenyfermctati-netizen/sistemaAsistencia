<?php

namespace App\Models\Concerns;

use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;

trait ScopedByEmpresaRelation
{
    public static function bootScopedByEmpresaRelation(): void
    {
        static::addGlobalScope('empresa_relacion', function (Builder $builder): void {
            if (app(EmpresaContext::class)->id() !== null) {
                $builder->whereHas(static::$empresaRelation);
            }
        });
    }
}
