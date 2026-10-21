<?php

namespace App\Services;

use App\Models\BiometricoComando;
use App\Models\BiometricoUsuario;
use App\Models\DispositivoBiometrico;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BiometricoSyncService
{
    public function solicitarUsuarios(DispositivoBiometrico $dispositivo): BiometricoComando
    {
        $existente = BiometricoComando::where('dispositivo_id', $dispositivo->id)
            ->where('tipo', 'CONSULTAR_USUARIOS')
            ->whereIn('estado', ['PENDIENTE', 'ENVIADO'])
            ->first();

        if ($existente) {
            return $existente;
        }

        return BiometricoComando::create([
            'dispositivo_id' => $dispositivo->id,
            'tipo' => 'CONSULTAR_USUARIOS',
            'comando' => 'DATA QUERY USERINFO',
            'estado' => 'PENDIENTE',
        ]);
    }

    public function sincronizarUsuario(
        DispositivoBiometrico $dispositivo,
        BiometricoUsuario $usuario
    ): BiometricoComando {
        if ($usuario->dispositivo_id !== $dispositivo->id) {
            throw ValidationException::withMessages([
                'usuario' => 'El usuario no pertenece a este dispositivo.',
            ]);
        }

        if ($usuario->estado !== 'PENDIENTE') {
            throw ValidationException::withMessages([
                'usuario' => 'Solo se pueden sincronizar trabajadores pendientes.',
            ]);
        }

        $trabajador = $usuario->trabajador;

        if (!$trabajador) {
            throw ValidationException::withMessages([
                'usuario' => 'El registro pendiente no tiene un trabajador asociado.',
            ]);
        }

        $pin = trim((string) $trabajador->codigo_biometrico);

        if ($pin === '') {
            throw ValidationException::withMessages([
                'usuario' => 'El trabajador no tiene código biométrico.',
            ]);
        }

        if ((string) $usuario->pin !== $pin) {
            throw ValidationException::withMessages([
                'usuario' => 'El PIN del trabajador no coincide con el registro pendiente.',
            ]);
        }

        $existenteDispositivo = BiometricoUsuario::where(
            'dispositivo_id',
            $dispositivo->id
        )
            ->where('pin', $pin)
            ->whereIn('estado', ['SINCRONIZADO', 'SOLO_DISPOSITIVO'])
            ->exists();

        if ($existenteDispositivo) {
            throw ValidationException::withMessages([
                'usuario' => 'Este PIN ya existe en el biométrico. No se realizará ninguna modificación.',
            ]);
        }

        $comandoPendiente = BiometricoComando::where(
            'dispositivo_id',
            $dispositivo->id
        )
            ->where('tipo', 'SINCRONIZAR_USUARIO_' . $usuario->id)
            ->whereIn('estado', ['PENDIENTE', 'ENVIADO'])
            ->first();

        if ($comandoPendiente) {
            return $comandoPendiente;
        }

        $nombre = $this->nombreParaDispositivo($trabajador);

        $comando = implode("\t", [
            "DATA UPDATE USERINFO PIN={$pin}",
            "Name={$nombre}",
            'Pri=0',
            'Passwd=',
            'Card=',
            'Grp=1',
            'TZ=0000000100000000',
            'Verify=0',
        ]);

        return BiometricoComando::create([
            'dispositivo_id' => $dispositivo->id,
            'tipo' => 'SINCRONIZAR_USUARIO_' . $usuario->id,
            'comando' => $comando,
            'estado' => 'PENDIENTE',
        ]);
    }

    public function siguienteComando(
        DispositivoBiometrico $dispositivo
    ): ?BiometricoComando {
        return DB::transaction(function () use ($dispositivo) {
            $comando = BiometricoComando::where(
                'dispositivo_id',
                $dispositivo->id
            )
                ->where('estado', 'PENDIENTE')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$comando) {
                return null;
            }

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
        $comando = BiometricoComando::where('id', $id)
            ->where('dispositivo_id', $dispositivo->id)
            ->first();

        if (!$comando) {
            return;
        }

        $comando->update([
            'estado' => $codigo === 0 ? 'COMPLETADO' : 'ERROR',
            'codigo_respuesta' => $codigo,
            'respuesta' => $respuesta,
            'respondido_en' => now(),
        ]);

        if (
            $codigo === 0 &&
            preg_match('/^SINCRONIZAR_USUARIO_(\d+)$/', $comando->tipo, $m)
        ) {
            $usuario = BiometricoUsuario::where('id', $m[1])
                ->where('dispositivo_id', $dispositivo->id)
                ->first();

            if ($usuario) {
                $usuario->update([
                    'estado' => 'SINCRONIZADO',
                    'ultima_verificacion' => now(),
                ]);
            }
        }
    }

    public function registrarUsuario(
        DispositivoBiometrico $dispositivo,
        string $pin,
        ?string $nombre = null,
        ?string $privilegio = null,
        ?string $tarjeta = null
    ): void {
        $trabajador = Trabajador::where('codigo_biometrico', $pin)->first();

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
                'estado' => $trabajador
                    ? 'SINCRONIZADO'
                    : 'SOLO_DISPOSITIVO',
                'ultima_verificacion' => now(),
            ]
        );
    }

    public function reconciliar(DispositivoBiometrico $dispositivo): array
    {
        $usuariosDispositivo = BiometricoUsuario::where(
            'dispositivo_id',
            $dispositivo->id
        )->get();

        $porPin = $usuariosDispositivo->keyBy(
            fn($usuario) => (string) $usuario->pin
        );

        foreach ($usuariosDispositivo as $usuario) {
            if ($usuario->estado === 'PENDIENTE') {
                continue;
            }

            $trabajador = Trabajador::where(
                'codigo_biometrico',
                $usuario->pin
            )->first();

            $usuario->update([
                'trabajador_id' => $trabajador?->id,
                'estado' => $trabajador
                    ? 'SINCRONIZADO'
                    : 'SOLO_DISPOSITIVO',
            ]);
        }

        $trabajadores = Trabajador::whereNotNull('codigo_biometrico')
            ->where('codigo_biometrico', '!=', '')
            ->get();

        foreach ($trabajadores as $trabajador) {
            $pin = trim((string) $trabajador->codigo_biometrico);

            if ($porPin->has($pin)) {
                continue;
            }

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

        return BiometricoUsuario::where(
            'dispositivo_id',
            $dispositivo->id
        )
            ->selectRaw('estado, COUNT(*) total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();
    }

    private function nombreParaDispositivo(Trabajador $trabajador): string
    {
        $nombre = trim(
            $trabajador->nombres . ' ' . $trabajador->apellidos
        );

        $nombre = preg_replace('/[\t\r\n]+/', ' ', $nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        return mb_substr(trim($nombre), 0, 50);
    }
}
