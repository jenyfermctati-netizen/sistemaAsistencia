<?php

namespace App\Models\Scopes;

use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $empresaId = app(EmpresaContext::class)->id();

        if ($empresaId !== null) {
            $builder->where($model->qualifyColumn('empresa_id'), $empresaId);
        }
    }
}
