<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Tenancy\EmpresaActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait PerteneceAEmpresa
{
    public static function bootPerteneceAEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $query): void {
            $id = app(EmpresaActual::class)->id();
            $id === null
                ? $query->whereRaw('1 = 0')
                : $query->where($query->getModel()->qualifyColumn('empresa_id'), $id);
        });

        static::saving(function (Model $model): void {
            $id = app(EmpresaActual::class)->requerirId();
            if (($model->exists && (int) $model->getRawOriginal('empresa_id') !== $id)
                || ($model->empresa_id !== null && (int) $model->empresa_id !== $id)) {
                throw new LogicException('No se puede cambiar la empresa de un registro.');
            }
            $model->empresa_id = $id;
        });

        static::deleting(function (Model $model): void {
            if ((int) $model->empresa_id !== app(EmpresaActual::class)->requerirId()) {
                throw new LogicException('El registro pertenece a otra empresa.');
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
