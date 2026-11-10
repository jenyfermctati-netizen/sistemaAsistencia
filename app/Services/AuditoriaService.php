<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    public function registrar(
        string $accion,
        string $tabla,
        int|string|null $registroId = null,
        ?array $anteriores = null,
        ?array $nuevos = null,
        ?int $empresaId = null
    ): Auditoria {
        $request = request();

        return Auditoria::withoutGlobalScopes()->create([
            'empresa_id' => $empresaId ?? app(EmpresaContext::class)->id(),
            'user_id' => auth()->id(),
            'accion' => $accion,
            'tabla' => $tabla,
            'registro_id' => $registroId !== null ? (string) $registroId : null,
            'valores_anteriores' => $anteriores,
            'valores_nuevos' => $nuevos,
            'ip' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
        ]);
    }

    public function modelo(string $accion, Model $model, ?array $anteriores = null): Auditoria
    {
        return $this->registrar(
            $accion,
            $model->getTable(),
            $model->getKey(),
            $anteriores,
            $model->getAttributes(),
            $model instanceof Empresa
                ? (int) $model->getKey()
                : $model->getAttribute('empresa_id')
        );
    }
}
