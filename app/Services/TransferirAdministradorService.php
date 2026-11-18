<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferirAdministradorService
{
    public function transferir(Empresa $empresa, User $actor, int $destinoId): void
    {
        DB::transaction(function () use ($empresa, $actor, $destinoId) {
            Empresa::whereKey($empresa->id)->lockForUpdate()->firstOrFail();
            $actual = User::withoutGlobalScopes()->where('empresa_id', $empresa->id)
                ->whereHas('rol', fn ($q) => $q->where('codigo', 'ADMINISTRADOR'))->lockForUpdate()->firstOrFail();
            abort_unless($actor->isSuperAdmin() || $actor->id === $actual->id, 403);
            $destino = User::withoutGlobalScopes()->where('empresa_id', $empresa->id)
                ->where('estado', true)->whereKey($destinoId)->lockForUpdate()->firstOrFail();
            if ($actual->id === $destino->id || $destino->rol?->ambito !== 'EMPRESA'
                || ! $destino->rol?->estado || ($destino->trabajador && $destino->trabajador->estado !== 'ACTIVO')) {
                throw ValidationException::withMessages(['usuario_id' => 'Selecciona otra cuenta empresarial activa.']);
            }
            $rolBasico = Rol::where('codigo', 'TRABAJADOR')->where('estado', true)->firstOrFail();
            $rolAdmin = $actual->rol_id;
            // This is the only intentional bypass of the model's administrator guard.
            // Both changes and the audit event commit together, under the company lock.
            User::withoutGlobalScopes()->whereKey($actual->id)->update([
                'rol_id' => $rolBasico->id, 'administrador_empresa_id' => null,
                'estado' => $actual->trabajador_id !== null,
            ]);
            $destino->rol_id = $rolAdmin;
            $destino->save();
            app(AuditoriaService::class)->registrar('TRANSFERIR_ADMINISTRADOR', 'empresas', $empresa->id,
                ['administrador_id' => $actual->id], ['administrador_id' => $destino->id], $empresa->id);
        });
    }
}
