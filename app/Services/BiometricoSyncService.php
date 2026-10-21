<?php

namespace App\Services;

use App\Models\BiometricoComando;
use App\Models\BiometricoUsuario;
use App\Models\DispositivoBiometrico;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;

class BiometricoSyncService
{
    public function solicitarUsuarios(DispositivoBiometrico $dispositivo): BiometricoComando
    {
        $existente = BiometricoComando::where('dispositivo_id',$dispositivo->id)
            ->where('tipo','CONSULTAR_USUARIOS')
            ->whereIn('estado',['PENDIENTE','ENVIADO'])
            ->first();

        if ($existente) return $existente;

        return BiometricoComando::create([
            'dispositivo_id' => $dispositivo->id,
            'tipo' => 'CONSULTAR_USUARIOS',
            'comando' => 'DATA QUERY USERINFO',
            'estado' => 'PENDIENTE',
        ]);
    }

    public function siguienteComando(DispositivoBiometrico $dispositivo): ?BiometricoComando
    {
        return DB::transaction(function () use ($dispositivo) {
            $comando = BiometricoComando::where('dispositivo_id',$dispositivo->id)
                ->where('estado','PENDIENTE')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$comando) return null;

            $comando->update([
                'estado' => 'ENVIADO',
                'enviado_en' => now(),
                'intentos' => $comando->intentos + 1,
            ]);

            return $comando;
        });
    }

    public function registrarRespuesta(
        DispositivoBiometrico $dispositivo,
        int $id,
        int $codigo,
        string $respuesta
    ): void {
        $comando = BiometricoComando::where('id',$id)
            ->where('dispositivo_id',$dispositivo->id)
            ->first();

        if (!$comando) return;

        $comando->update([
            'estado' => $codigo === 0 ? 'COMPLETADO' : 'ERROR',
            'codigo_respuesta' => $codigo,
            'respuesta' => $respuesta,
            'respondido_en' => now(),
        ]);
    }

    public function registrarUsuario(
        DispositivoBiometrico $dispositivo,
        string $pin,
        ?string $nombre = null,
        ?string $privilegio = null,
        ?string $tarjeta = null
    ): void {
        $trabajador = Trabajador::where('codigo_biometrico',$pin)->first();

        BiometricoUsuario::updateOrCreate(
            [
                'dispositivo_id' => $dispositivo->id,
                'pin' => $pin,
            ],
            [
                'trabajador_id' => $trabajador?->id,
                'nombre_dispositivo' => $nombre,
                'privilegio' => $privilegio,
                'tarjeta' => $tarjeta,
                'estado' => $trabajador ? 'SINCRONIZADO' : 'SOLO_DISPOSITIVO',
                'ultima_verificacion' => now(),
            ]
        );
    }

    public function reconciliar(DispositivoBiometrico $dispositivo): array
    {
        $usuariosDispositivo = BiometricoUsuario::where('dispositivo_id',$dispositivo->id)->get();
        $porPin = $usuariosDispositivo->keyBy(fn($u) => (string)$u->pin);

        foreach ($usuariosDispositivo as $usuario) {
            $trabajador = Trabajador::where('codigo_biometrico',$usuario->pin)->first();

            $usuario->update([
                'trabajador_id' => $trabajador?->id,
                'estado' => $trabajador ? 'SINCRONIZADO' : 'SOLO_DISPOSITIVO',
            ]);
        }

        $trabajadores = Trabajador::whereNotNull('codigo_biometrico')
            ->where('codigo_biometrico','!=','')
            ->get();

        foreach ($trabajadores as $trabajador) {
            $pin = (string)$trabajador->codigo_biometrico;

            if ($porPin->has($pin)) continue;

            BiometricoUsuario::updateOrCreate(
                [
                    'dispositivo_id' => $dispositivo->id,
                    'pin' => $pin,
                ],
                [
                    'trabajador_id' => $trabajador->id,
                    'nombre_dispositivo' => null,
                    'estado' => 'PENDIENTE',
                ]
            );
        }

        return BiometricoUsuario::where('dispositivo_id',$dispositivo->id)
            ->selectRaw('estado, COUNT(*) total')
            ->groupBy('estado')
            ->pluck('total','estado')
            ->toArray();
    }
}