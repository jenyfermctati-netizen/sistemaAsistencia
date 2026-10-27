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
    public function procesarFecha(Carbon $fecha): int
    {
        $procesados = 0;

        Trabajador::where('estado', 'ACTIVO')
            ->orderBy('id')
            ->eachById(function (Trabajador $trabajador) use ($fecha, &$procesados) {
                $this->procesarTrabajador($trabajador, $fecha);
                $procesados++;
            });

        return $procesados;
    }

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

        $entradas = $marcaciones
            ->where('tipo', 'ENTRADA')
            ->values();

        $salidas = $marcaciones
            ->where('tipo', 'SALIDA')
            ->values();

        $entrada = $entradas->get(0);
        $usaHorarioPartido = filled($diaHorario?->hora_salida_refrigerio)
            && filled($diaHorario?->hora_retorno_refrigerio);
        $esJornadaCompleta = $usaHorarioPartido
            && $asignacion?->tipo_control === 'OBLIGATORIO';

        $salidaRefrigerio = $esJornadaCompleta ? $salidas->get(0) : null;
        $retornoRefrigerio = $esJornadaCompleta ? $entradas->get(1) : null;
        $salida = $esJornadaCompleta ? $salidas->get(1) : $salidas->last();
        $retornoRefrigerioEstimado = false;
        $salidaEstimada = false;
        $salidaReinterpretada = false;

        $horaProgramadaEntrada = $diaHorario?->hora_entrada;
        $horaProgramadaSalidaRefrigerio = $esJornadaCompleta
            ? $diaHorario?->hora_salida_refrigerio
            : null;
        $horaProgramadaRetornoRefrigerio = $esJornadaCompleta
            ? $diaHorario?->hora_retorno_refrigerio
            : null;
        $horaProgramadaSalida = $diaHorario?->hora_salida;

        if ($usaHorarioPartido && ! $esJornadaCompleta) {
            $primeraMarca = $marcaciones->first();
            $esTurnoTarde = $primeraMarca
                && $primeraMarca->fecha_hora->format('H:i:s') >= $diaHorario->hora_retorno_refrigerio;

            $horaProgramadaEntrada = $esTurnoTarde
                ? $diaHorario->hora_retorno_refrigerio
                : $diaHorario->hora_entrada;
            $horaProgramadaSalida = $esTurnoTarde
                ? $diaHorario->hora_salida
                : $diaHorario->hora_salida_refrigerio;
        }

        $minutosTrabajados = $this
            ->calcularMinutosTrabajados(
                $entrada,
                $salidaRefrigerio,
                $retornoRefrigerio,
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
                $horaProgramadaEntrada,
                $horaProgramadaSalidaRefrigerio,
                $horaProgramadaRetornoRefrigerio,
                $horaProgramadaSalida,
                (int) (
                    $diaHorario?->tolerancia_minutos
                        ?? 0
                ),
                $entrada,
                $salidaRefrigerio,
                $retornoRefrigerio,
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
                    $horaProgramadaEntrada,
                    $horaProgramadaSalidaRefrigerio,
                    $horaProgramadaRetornoRefrigerio,
                    $horaProgramadaSalida,
                    (int) (
                        $diaHorario?->tolerancia_minutos
                            ?? 0
                    ),
                    $entrada,
                    $salidaRefrigerio,
                    $retornoRefrigerio,
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
                        .'. Actividad: '
                        .$campo->actividad,
                    450
                ),
                $asignacion,
                $horario?->id,
                $horaProgramadaEntrada,
                $horaProgramadaSalidaRefrigerio,
                $horaProgramadaRetornoRefrigerio,
                $horaProgramadaSalida,
                (int) (
                    $diaHorario?->tolerancia_minutos
                        ?? 0
                ),
                $entrada,
                $salidaRefrigerio,
                $retornoRefrigerio,
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
                $observacion .= ': '.Str::limit(
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
                $horaProgramadaEntrada,
                $horaProgramadaSalidaRefrigerio,
                $horaProgramadaRetornoRefrigerio,
                $horaProgramadaSalida,
                (int) (
                    $diaHorario?->tolerancia_minutos
                        ?? 0
                ),
                $entrada,
                $salidaRefrigerio,
                $retornoRefrigerio,
                $salida,
                $minutosTrabajados
            );

            return;
        }

        if (! $asignacion) {
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

        if (! $diaHorario || ! $diaHorario->es_laborable) {
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

        if ($fecha->lt(today())) {
            if (
                $esJornadaCompleta
                && ! $salida
                && $retornoRefrigerio
                && $this->pareceSalidaFinal(
                    $retornoRefrigerio,
                    $fecha,
                    $horaProgramadaRetornoRefrigerio,
                    $horaProgramadaSalida
                )
            ) {
                $salida = $retornoRefrigerio;
                $retornoRefrigerio = null;
                $salidaReinterpretada = true;
            }

            if (
                $esJornadaCompleta
                && $entrada
                && $salidaRefrigerio
                && ! $retornoRefrigerio
                && $horaProgramadaRetornoRefrigerio
            ) {
                $retornoProgramado = $this->crearMarcacionEstimada(
                    $fecha,
                    $horaProgramadaRetornoRefrigerio,
                    'ENTRADA'
                );

                if ($retornoProgramado->fecha_hora->gt($salidaRefrigerio->fecha_hora)) {
                    $retornoRefrigerio = $retornoProgramado;
                    $retornoRefrigerioEstimado = true;
                }
            }

            if (
                $esJornadaCompleta
                && $entrada
                && $salidaRefrigerio
                && $retornoRefrigerio
                && ! $salida
                && $horaProgramadaSalida
            ) {
                $salidaProgramada = $this->crearMarcacionEstimada(
                    $fecha,
                    $horaProgramadaSalida,
                    'SALIDA'
                );

                if ($salidaProgramada->fecha_hora->gt($retornoRefrigerio->fecha_hora)) {
                    $salida = $salidaProgramada;
                    $salidaEstimada = true;
                }
            }

            if (
                ! $esJornadaCompleta
                && $entrada
                && ! $salida
                && $horaProgramadaSalida
            ) {
                $salidaProgramada = $this->crearMarcacionEstimada(
                    $fecha,
                    $horaProgramadaSalida,
                    'SALIDA'
                );

                if ($salidaProgramada->fecha_hora->gt($entrada->fecha_hora)) {
                    $salida = $salidaProgramada;
                    $salidaEstimada = true;
                }
            }

            $minutosTrabajados = $this->calcularMinutosTrabajados(
                $entrada,
                $salidaRefrigerio,
                $retornoRefrigerio,
                $salida
            );
        }

        $ajustesCierre = collect();

        if ($retornoRefrigerioEstimado) {
            $ajustesCierre->push(
                'Retorno de refrigerio asumido automáticamente según el horario ('
                .substr((string) $horaProgramadaRetornoRefrigerio, 0, 5).').'
            );
        }

        if ($salidaEstimada) {
            $ajustesCierre->push(
                'Salida final asumida automáticamente según el horario ('
                .substr((string) $horaProgramadaSalida, 0, 5).').'
            );
        }

        if ($salidaReinterpretada) {
            $ajustesCierre->push(
                'La última marcación fue interpretada como salida final durante el cierre.'
            );
        }

        if ($asignacion->tipo_control === 'REFERENCIAL') {
            $estado = $marcaciones->isEmpty()
                ? 'SIN_REGISTRO'
                : 'PRESENTE';

            $observacionBase = match (true) {
                $marcaciones->isEmpty() => 'No existen marcaciones para este día. Control referencial.',
                ! $entrada || ! $salida => 'Marcación incompleta. Control referencial.',
                default => null,
            };
            $observacion = $ajustesCierre->isNotEmpty()
                ? $ajustesCierre->join(' ')
                : $observacionBase;

            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: $estado,
                horarioId: $horario->id,
                tipoControl: 'REFERENCIAL',
                horaProgramadaEntrada: $horaProgramadaEntrada,
                horaProgramadaSalida: $horaProgramadaSalida,
                tolerancia: $horaProgramadaEntrada === $diaHorario->hora_entrada
                    ? $diaHorario->tolerancia_minutos
                    : 0,
                entrada: $entrada,
                salida: $salida,
                minutosTrabajados: $minutosTrabajados,
                salidaEstimada: $salidaEstimada,
                observacion: $observacion
            );

            return;
        }

        if ($marcaciones->isEmpty()) {
            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'FALTA',
                horarioId: $horario->id,
                tipoControl: 'OBLIGATORIO',
                horaProgramadaEntrada: $horaProgramadaEntrada,
                horaProgramadaSalidaRefrigerio: $horaProgramadaSalidaRefrigerio,
                horaProgramadaRetornoRefrigerio: $horaProgramadaRetornoRefrigerio,
                horaProgramadaSalida: $horaProgramadaSalida,
                tolerancia: $diaHorario->tolerancia_minutos,
                observacion: 'No existen marcaciones de entrada ni salida.'
            );

            return;
        }

        if (! $entrada) {
            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'SIN_REGISTRO',
                horarioId: $horario->id,
                tipoControl: 'OBLIGATORIO',
                horaProgramadaEntrada: $horaProgramadaEntrada,
                horaProgramadaSalidaRefrigerio: $horaProgramadaSalidaRefrigerio,
                horaProgramadaRetornoRefrigerio: $horaProgramadaRetornoRefrigerio,
                horaProgramadaSalida: $horaProgramadaSalida,
                tolerancia: $diaHorario->tolerancia_minutos,
                salidaRefrigerio: $salidaRefrigerio,
                retornoRefrigerio: $retornoRefrigerio,
                salida: $salida,
                observacion: 'Existe salida, pero no existe marcación de entrada.'
            );

            return;
        }

        $horaEntrada = Carbon::parse(
            $entrada->fecha_hora
        );

        $horaProgramada = Carbon::parse(
            $fechaTexto.' '
            .$horaProgramadaEntrada
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

        if ($esJornadaCompleta && $retornoRefrigerio) {
            $retornoProgramado = Carbon::parse(
                $fechaTexto.' '.$horaProgramadaRetornoRefrigerio
            );

            if ($retornoRefrigerio->fecha_hora->gt($retornoProgramado)) {
                $minutosTardanza += $retornoProgramado->diffInMinutes(
                    $retornoRefrigerio->fecha_hora
                );
            }
        }

        $marcasFaltantes = collect();

        if ($esJornadaCompleta) {
            if (! $salidaRefrigerio) {
                $marcasFaltantes->push('salida a refrigerio');
            }

            if (! $retornoRefrigerio) {
                $marcasFaltantes->push('retorno de refrigerio');
            }
        }

        if (! $salida) {
            $marcasFaltantes->push('salida final');
        }

        $this->guardarAsistencia(
            trabajador: $trabajador,
            fecha: $fecha,
            estado: $minutosTardanza > 0
                ? 'TARDANZA'
                : 'PRESENTE',
            horarioId: $horario->id,
            tipoControl: 'OBLIGATORIO',
            horaProgramadaEntrada: $horaProgramadaEntrada,
            horaProgramadaSalidaRefrigerio: $horaProgramadaSalidaRefrigerio,
            horaProgramadaRetornoRefrigerio: $horaProgramadaRetornoRefrigerio,
            horaProgramadaSalida: $horaProgramadaSalida,
            tolerancia: $diaHorario->tolerancia_minutos,
            entrada: $entrada,
            salidaRefrigerio: $salidaRefrigerio,
            retornoRefrigerio: $retornoRefrigerio,
            salida: $salida,
            minutosTrabajados: $minutosTrabajados,
            minutosTardanza: $minutosTardanza,
            retornoRefrigerioEstimado: $retornoRefrigerioEstimado,
            salidaEstimada: $salidaEstimada,
            observacion: $marcasFaltantes->isEmpty() && $ajustesCierre->isEmpty()
                ? null
                : collect([
                    $marcasFaltantes->isEmpty()
                        ? null
                        : 'Falta marcar: '.$marcasFaltantes->join(', ').'.',
                    $ajustesCierre->isEmpty()
                        ? null
                        : $ajustesCierre->join(' '),
                ])->filter()->join(' ')
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
        ?string $horaProgramadaSalidaRefrigerio,
        ?string $horaProgramadaRetornoRefrigerio,
        ?string $horaProgramadaSalida,
        int $tolerancia,
        ?Marcacion $entrada,
        ?Marcacion $salidaRefrigerio,
        ?Marcacion $retornoRefrigerio,
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
            horaProgramadaSalidaRefrigerio: $horaProgramadaSalidaRefrigerio,
            horaProgramadaRetornoRefrigerio: $horaProgramadaRetornoRefrigerio,
            horaProgramadaSalida: $horaProgramadaSalida,
            tolerancia: $tolerancia,
            entrada: $entrada,
            salidaRefrigerio: $salidaRefrigerio,
            retornoRefrigerio: $retornoRefrigerio,
            salida: $salida,
            minutosTrabajados: $minutosTrabajados,
            observacion: $observacion
        );
    }

    private function calcularMinutosTrabajados(
        ?Marcacion $entrada,
        ?Marcacion $salidaRefrigerio,
        ?Marcacion $retornoRefrigerio,
        ?Marcacion $salida
    ): int {
        return $this->minutosEntre($entrada, $salidaRefrigerio ?? $salida)
            + $this->minutosEntre($retornoRefrigerio, $salida);
    }

    private function minutosEntre(?Marcacion $entrada, ?Marcacion $salida): int
    {
        if (! $entrada || ! $salida || ! $salida->fecha_hora->gt($entrada->fecha_hora)) {
            return 0;
        }

        return $entrada->fecha_hora->diffInMinutes($salida->fecha_hora);
    }

    private function crearMarcacionEstimada(
        Carbon $fecha,
        string $hora,
        string $tipo
    ): Marcacion {
        $marcacion = new Marcacion;
        $marcacion->fecha_hora = Carbon::parse(
            $fecha->toDateString().' '.$hora
        );
        $marcacion->tipo = $tipo;

        return $marcacion;
    }

    private function pareceSalidaFinal(
        Marcacion $marcacion,
        Carbon $fecha,
        ?string $horaRetorno,
        ?string $horaSalida
    ): bool {
        if (! $horaRetorno || ! $horaSalida) {
            return false;
        }

        $retornoProgramado = Carbon::parse(
            $fecha->toDateString().' '.$horaRetorno
        );
        $salidaProgramada = Carbon::parse(
            $fecha->toDateString().' '.$horaSalida
        );

        return abs($marcacion->fecha_hora->diffInSeconds($salidaProgramada))
            < abs($marcacion->fecha_hora->diffInSeconds($retornoProgramado));
    }

    private function guardarAsistencia(
        Trabajador $trabajador,
        Carbon $fecha,
        string $estado,
        ?int $horarioId = null,
        ?string $tipoControl = null,
        ?string $horaProgramadaEntrada = null,
        ?string $horaProgramadaSalidaRefrigerio = null,
        ?string $horaProgramadaRetornoRefrigerio = null,
        ?string $horaProgramadaSalida = null,
        int $tolerancia = 0,
        ?Marcacion $entrada = null,
        ?Marcacion $salidaRefrigerio = null,
        ?Marcacion $retornoRefrigerio = null,
        ?Marcacion $salida = null,
        int $minutosTrabajados = 0,
        int $minutosTardanza = 0,
        bool $retornoRefrigerioEstimado = false,
        bool $salidaEstimada = false,
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
            'hora_programada_salida_refrigerio' => $horaProgramadaSalidaRefrigerio,
            'hora_programada_retorno_refrigerio' => $horaProgramadaRetornoRefrigerio,
            'hora_programada_salida' => $horaProgramadaSalida,
            'tolerancia_aplicada' => $tolerancia,
            'marcacion_entrada_id' => $entrada?->id,
            'marcacion_salida_refrigerio_id' => $salidaRefrigerio?->id,
            'marcacion_retorno_refrigerio_id' => $retornoRefrigerio?->id,
            'marcacion_salida_id' => $salida?->id,
            'hora_entrada' => $entrada?->fecha_hora?->format('H:i:s'),
            'hora_salida_refrigerio' => $salidaRefrigerio?->fecha_hora?->format('H:i:s'),
            'hora_retorno_refrigerio' => $retornoRefrigerio?->fecha_hora?->format('H:i:s'),
            'retorno_refrigerio_estimado' => $retornoRefrigerioEstimado,
            'hora_salida' => $salida?->fecha_hora?->format('H:i:s'),
            'salida_estimada' => $salidaEstimada,
            'minutos_trabajados' => $minutosTrabajados,
            'minutos_tardanza' => $minutosTardanza,
            'estado' => $estado,
            'observacion' => $observacion,
            'procesado_en' => now(),
            'cerrada_en' => $fecha->lt(today())
                ? ($asistencia->cerrada_en ?? now())
                : null,
        ]);

        $asistencia->save();
    }
}
