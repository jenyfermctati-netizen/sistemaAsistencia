<?php

namespace App\Services;

use App\Models\AsignacionCampo;
use App\Models\Asistencia;
use App\Models\Feriado;
use App\Models\Marcacion;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use App\Models\Vacaciones;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AsistenciaService
{
    public function procesarTrabajador(Trabajador $trabajador, Carbon $fecha): void
    {
        $fecha = $fecha->copy()->startOfDay();
        $fechaTexto = $fecha->toDateString();

        $asignacion = TrabajadorHorario::with(
            'horario.dias'
        )
            ->where('trabajador_id', $trabajador->id)
            ->where('estado', true)
            ->whereDate(
                'fecha_inicio',
                '<=',
                $fechaTexto
            )
            ->where(function ($query) use ($fechaTexto) {
                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate(
                        'fecha_fin',
                        '>=',
                        $fechaTexto
                    );
            })
            ->orderByDesc('fecha_inicio')
            ->first();

        $horario = $asignacion?->horario;

        $diaHorario = $horario?->dias?->firstWhere(
            'dia_semana',
            $fecha->isoWeekday()
        );

        $marcaciones = Marcacion::where(
            'trabajador_id',
            $trabajador->id
        )
            ->whereDate('fecha_hora', $fechaTexto)
            ->where('anulada', false)
            ->orderBy('fecha_hora')
            ->get();

        $entrada = $marcaciones
            ->where('tipo', 'ENTRADA')
            ->first();

        $salida = $marcaciones
            ->where('tipo', 'SALIDA')
            ->last();

        $minutosTrabajados = $this
            ->calcularMinutosTrabajados(
                $entrada,
                $salida
            );

        $feriado = Feriado::whereDate(
            'fecha',
            $fechaTexto
        )
            ->where('estado', true)
            ->where(function ($query) use ($trabajador) {
                $query->whereNull('area_id');

                if ($trabajador->area_id) {
                    $query->orWhere(
                        'area_id',
                        $trabajador->area_id
                    );
                }
            })
            ->orderByRaw('area_id IS NULL ASC')
            ->first();

        if ($feriado) {
            $this->guardarExcepcion(
                $trabajador,
                $fecha,
                'FERIADO',
                "Feriado: {$feriado->nombre}.",
                $asignacion,
                $horario?->id,
                $diaHorario?->hora_entrada,
                $diaHorario?->hora_salida,
                (int) (
                    $diaHorario?->tolerancia_minutos
                        ?? 0
                ),
                $entrada,
                $salida,
                $minutosTrabajados
            );

            return;
        }

        if (strtoupper(trim((string) $trabajador->tipo_vinculo)) === 'CONTRATADO') {
            $vacacion = Vacaciones::where(
                'trabajador_id',
                $trabajador->id
            )
                ->whereIn('estado', [
                    'APROBADA',
                    'PROGRAMADA',
                    'EN_CURSO',
                    'GOZADA',
                ])
                ->whereDate(
                    'fecha_inicio',
                    '<=',
                    $fechaTexto
                )
                ->whereDate(
                    'fecha_fin',
                    '>=',
                    $fechaTexto
                )
                ->first();

            if ($vacacion) {
                $this->guardarExcepcion(
                    $trabajador,
                    $fecha,
                    'VACACIONES',
                    'Trabajador dentro de su periodo de vacaciones aprobado.',
                    $asignacion,
                    $horario?->id,
                    $diaHorario?->hora_entrada,
                    $diaHorario?->hora_salida,
                    (int) (
                        $diaHorario?->tolerancia_minutos
                            ?? 0
                    ),
                    $entrada,
                    $salida,
                    $minutosTrabajados
                );

                return;
            }
        }

        $campo = AsignacionCampo::where(
            'trabajador_id',
            $trabajador->id
        )
            ->where('estado', '!=', 'CANCELADA')
            ->whereDate(
                'fecha_inicio',
                '<=',
                $fechaTexto
            )
            ->whereDate(
                'fecha_fin',
                '>=',
                $fechaTexto
            )
            ->first();

        if ($campo) {
            $detalle = 'Trabajo en campo';

            if ($campo->lugar) {
                $detalle .= " en {$campo->lugar}";
            }

            $this->guardarExcepcion(
                $trabajador,
                $fecha,
                'CAMPO',
                Str::limit(
                    $detalle
                        . '. Actividad: '
                        . $campo->actividad,
                    450
                ),
                $asignacion,
                $horario?->id,
                $diaHorario?->hora_entrada,
                $diaHorario?->hora_salida,
                (int) (
                    $diaHorario?->tolerancia_minutos
                        ?? 0
                ),
                $entrada,
                $salida,
                $minutosTrabajados
            );

            return;
        }

        $solicitud = Solicitud::where(
            'trabajador_id',
            $trabajador->id
        )
            ->where('estado', 'APROBADA')
            ->whereDate(
                'fecha_inicio',
                '<=',
                $fechaTexto
            )
            ->where(function ($query) use ($fechaTexto) {
                $query
                    ->where(function (
                        $sinFechaFin
                    ) use ($fechaTexto) {
                        $sinFechaFin
                            ->whereNull('fecha_fin')
                            ->whereDate(
                                'fecha_inicio',
                                $fechaTexto
                            );
                    })
                    ->orWhereDate(
                        'fecha_fin',
                        '>=',
                        $fechaTexto
                    );
            })
            ->orderByRaw(
                "CASE WHEN tipo = 'PERMISO' THEN 0 ELSE 1 END"
            )
            ->orderByDesc('fecha_revision')
            ->first();

        if ($solicitud) {
            $estado = $solicitud->tipo === 'PERMISO'
                ? 'PERMISO'
                : 'JUSTIFICADO';

            $observacion =
                "Solicitud aprobada ({$solicitud->tipo})";

            if ($solicitud->motivo) {
                $observacion .= ': ' . Str::limit(
                    $solicitud->motivo,
                    350
                );
            }

            $this->guardarExcepcion(
                $trabajador,
                $fecha,
                $estado,
                $observacion,
                $asignacion,
                $horario?->id,
                $diaHorario?->hora_entrada,
                $diaHorario?->hora_salida,
                (int) (
                    $diaHorario?->tolerancia_minutos
                        ?? 0
                ),
                $entrada,
                $salida,
                $minutosTrabajados
            );

            return;
        }

        if (!$asignacion) {
            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'SIN_REGISTRO',
                entrada: $entrada,
                salida: $salida,
                minutosTrabajados: $minutosTrabajados,
                observacion: 'El trabajador no tiene un horario asignado para esta fecha.'
            );

            return;
        }

        if (!$diaHorario || !$diaHorario->es_laborable) {
            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'NO_LABORABLE',
                horarioId: $horario?->id,
                tipoControl: $asignacion->tipo_control,
                entrada: $entrada,
                salida: $salida,
                minutosTrabajados: $minutosTrabajados,
                observacion: 'Día no laborable según el horario asignado.'
            );

            return;
        }

        if ($asignacion->tipo_control === 'REFERENCIAL') {
            $estado = (!$entrada && !$salida)
                ? 'SIN_REGISTRO'
                : 'PRESENTE';

            $observacion = match (true) {
                !$entrada && !$salida =>
                    'No existen marcaciones para este día. Control referencial.',
                !$entrada || !$salida =>
                    'Marcación incompleta. Control referencial.',
                default => null,
            };

            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: $estado,
                horarioId: $horario->id,
                tipoControl: 'REFERENCIAL',
                horaProgramadaEntrada: $diaHorario->hora_entrada,
                horaProgramadaSalida: $diaHorario->hora_salida,
                tolerancia: $diaHorario->tolerancia_minutos,
                entrada: $entrada,
                salida: $salida,
                minutosTrabajados: $minutosTrabajados,
                observacion: $observacion
            );

            return;
        }

        if (!$entrada && !$salida) {
            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'FALTA',
                horarioId: $horario->id,
                tipoControl: 'OBLIGATORIO',
                horaProgramadaEntrada: $diaHorario->hora_entrada,
                horaProgramadaSalida: $diaHorario->hora_salida,
                tolerancia: $diaHorario->tolerancia_minutos,
                observacion: 'No existen marcaciones de entrada ni salida.'
            );

            return;
        }

        if (!$entrada) {
            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'SIN_REGISTRO',
                horarioId: $horario->id,
                tipoControl: 'OBLIGATORIO',
                horaProgramadaEntrada: $diaHorario->hora_entrada,
                horaProgramadaSalida: $diaHorario->hora_salida,
                tolerancia: $diaHorario->tolerancia_minutos,
                salida: $salida,
                observacion: 'Existe salida, pero no existe marcación de entrada.'
            );

            return;
        }

        $horaEntrada = Carbon::parse(
            $entrada->fecha_hora
        );

        $horaProgramada = Carbon::parse(
            $fechaTexto . ' '
            . $diaHorario->hora_entrada
        );

        $horaLimite = $horaProgramada
            ->copy()
            ->addMinutes(
                $diaHorario->tolerancia_minutos
            );

        $minutosTardanza = $horaEntrada
            ->gt($horaLimite)
                ? $horaLimite->diffInMinutes(
                    $horaEntrada
                )
                : 0;

        $this->guardarAsistencia(
            trabajador: $trabajador,
            fecha: $fecha,
            estado: $minutosTardanza > 0
                ? 'TARDANZA'
                : 'PRESENTE',
            horarioId: $horario->id,
            tipoControl: 'OBLIGATORIO',
            horaProgramadaEntrada: $diaHorario->hora_entrada,
            horaProgramadaSalida: $diaHorario->hora_salida,
            tolerancia: $diaHorario->tolerancia_minutos,
            entrada: $entrada,
            salida: $salida,
            minutosTrabajados: $minutosTrabajados,
            minutosTardanza: $minutosTardanza,
            observacion: $salida
                ? null
                : 'No existe marcación de salida.'
        );
    }

    private function guardarExcepcion(
        Trabajador $trabajador,
        Carbon $fecha,
        string $estado,
        string $observacion,
        ?TrabajadorHorario $asignacion,
        ?int $horarioId,
        ?string $horaProgramadaEntrada,
        ?string $horaProgramadaSalida,
        int $tolerancia,
        ?Marcacion $entrada,
        ?Marcacion $salida,
        int $minutosTrabajados
    ): void {
        $this->guardarAsistencia(
            trabajador: $trabajador,
            fecha: $fecha,
            estado: $estado,
            horarioId: $horarioId,
            tipoControl: $asignacion?->tipo_control,
            horaProgramadaEntrada: $horaProgramadaEntrada,
            horaProgramadaSalida: $horaProgramadaSalida,
            tolerancia: $tolerancia,
            entrada: $entrada,
            salida: $salida,
            minutosTrabajados: $minutosTrabajados,
            observacion: $observacion
        );
    }

    private function calcularMinutosTrabajados(?Marcacion $entrada, ?Marcacion $salida): int
    {
        if (!$entrada || !$salida) {
            return 0;
        }

        $horaEntrada = Carbon::parse($entrada->fecha_hora);

        $horaSalida = Carbon::parse($salida->fecha_hora);

        return $horaSalida->gt($horaEntrada)
            ? $horaEntrada->diffInMinutes(
                $horaSalida
            )
            : 0;
    }

    private function guardarAsistencia(
        Trabajador $trabajador,
        Carbon $fecha,
        string $estado,
        ?int $horarioId = null,
        ?string $tipoControl = null,
        ?string $horaProgramadaEntrada = null,
        ?string $horaProgramadaSalida = null,
        int $tolerancia = 0,
        ?Marcacion $entrada = null,
        ?Marcacion $salida = null,
        int $minutosTrabajados = 0,
        int $minutosTardanza = 0,
        ?string $observacion = null
    ): void {
        $asistencia = Asistencia::where(
            'trabajador_id',
            $trabajador->id
        )
            ->whereDate(
                'fecha',
                $fecha->toDateString()
            )
            ->first()
                ?? new Asistencia([
                    'trabajador_id' => $trabajador->id,
                    'fecha' => $fecha->toDateString(),
                ]);

        $asistencia->fill([
            'horario_id' => $horarioId,
            'tipo_control_aplicado' => $tipoControl,
            'hora_programada_entrada' => $horaProgramadaEntrada,
            'hora_programada_salida' => $horaProgramadaSalida,
            'tolerancia_aplicada' => $tolerancia,
            'marcacion_entrada_id' => $entrada?->id,
            'marcacion_salida_id' => $salida?->id,
            'hora_entrada' => $entrada?->fecha_hora?->format('H:i:s'),
            'hora_salida' => $salida?->fecha_hora?->format('H:i:s'),
            'minutos_trabajados' => $minutosTrabajados,
            'minutos_tardanza' => $minutosTardanza,
            'estado' => $estado,
            'observacion' => $observacion,
            'procesado_en' => now(),
        ]);

        $asistencia->save();
    }
}
