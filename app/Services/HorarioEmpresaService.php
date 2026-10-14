<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use Carbon\Carbon;

class HorarioEmpresaService
{
    public function asignarATodos(Horario $horario): void
    {
        Trabajador::where('estado', 'ACTIVO')
            ->orderBy('id')
            ->eachById(function (Trabajador $trabajador) use ($horario) {
                $this->asignar($trabajador, $horario, now()->startOfDay());
            });
    }

    public function asignar(Trabajador $trabajador, Horario $horario, Carbon $fechaInicio): void
    {
        $asignaciones = TrabajadorHorario::where('trabajador_id', $trabajador->id)
            ->where('estado', true)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get();

        $asignacionActual = $asignaciones->firstWhere('horario_id', $horario->id);
        $tipoControl = $trabajador->tipo_vinculo === 'LOCADOR'
            ? 'REFERENCIAL'
            : 'OBLIGATORIO';

        foreach ($asignaciones as $asignacion) {
            if ($asignacionActual && $asignacion->is($asignacionActual)) {
                continue;
            }

            $fechaFin = Carbon::parse($asignacion->fecha_inicio)->lt($fechaInicio)
                ? $fechaInicio->copy()->subDay()->toDateString()
                : $asignacion->fecha_fin;

            $asignacion->update([
                'fecha_fin' => $fechaFin,
                'estado' => false,
            ]);
        }

        if ($asignacionActual) {
            $asignacionActual->update([
                'tipo_control' => $tipoControl,
                'fecha_fin' => null,
                'estado' => true,
            ]);

            return;
        }

        TrabajadorHorario::create([
            'trabajador_id' => $trabajador->id,
            'horario_id' => $horario->id,
            'tipo_control' => $tipoControl,
            'fecha_inicio' => $fechaInicio->toDateString(),
            'fecha_fin' => null,
            'estado' => true,
        ]);
    }
}
