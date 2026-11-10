<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);

        static::saving(function (Model $model): void {
            $empresaId = app(EmpresaContext::class)->id();

            if ($empresaId === null) {
                return;
            }

            if ($model->getAttribute('empresa_id') === null) {
                $model->setAttribute('empresa_id', $empresaId);

                return;
            }

            if ((int) $model->getAttribute('empresa_id') !== $empresaId) {
                throw new LogicException('No se puede guardar un registro fuera de la empresa activa.');
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
