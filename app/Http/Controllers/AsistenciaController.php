<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Marcacion;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $this->verificarAdministrador();

        $query = Asistencia::with([
            'trabajador.area',
            'horario',
            'marcacionEntrada',
            'marcacionSalida',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->whereHas('trabajador', function ($q) use ($buscar) {
                $q->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate(
                'fecha',
                '>=',
                $request->fecha_desde
            );
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate(
                'fecha',
                '<=',
                $request->fecha_hasta
            );
        }

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        if ($request->filled('tipo_control')) {
            $query->where(
                'tipo_control_aplicado',
                $request->tipo_control
            );
        }

        $asistencias = $query
            ->orderByDesc('fecha')
            ->orderBy('trabajador_id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'asistencias.index',
            compact('asistencias')
        );
    }


    public function procesar(Request $request)
    {
        $this->verificarAdministrador();

        $data = $request->validate([
            'fecha' => [
                'required',
                'date',
            ],
        ]);

        $fecha = Carbon::parse(
            $data['fecha']
        )->startOfDay();

        $trabajadores = Trabajador::where(
            'estado',
            'ACTIVO'
        )->get();

        $procesados = 0;

        DB::transaction(function () use (
            $trabajadores,
            $fecha,
            &$procesados
        ) {

            foreach ($trabajadores as $trabajador) {

                $this->procesarTrabajador(
                    $trabajador,
                    $fecha
                );

                $procesados++;
            }
        });

        return redirect()
            ->route('asistencias.index', [
                'fecha_desde' => $fecha->toDateString(),
                'fecha_hasta' => $fecha->toDateString(),
            ])
            ->with(
                'success',
                "Se procesaron {$procesados} trabajadores."
            );
    }


    private function procesarTrabajador(
        Trabajador $trabajador,
        Carbon $fecha
    ): void {

        $asignacion = TrabajadorHorario::with([
            'horario.dias',
        ])
            ->where(
                'trabajador_id',
                $trabajador->id
            )
            ->whereDate(
                'fecha_inicio',
                '<=',
                $fecha->toDateString()
            )
            ->where(function ($query) use ($fecha) {

                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate(
                        'fecha_fin',
                        '>=',
                        $fecha->toDateString()
                    );
            })
            ->orderByDesc('fecha_inicio')
            ->first();


        if (!$asignacion) {

            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'SIN_REGISTRO',
                observacion: 'El trabajador no tiene un horario asignado para esta fecha.'
            );

            return;
        }


        $horario = $asignacion->horario;

        $diaSemana = $fecha->isoWeekday();

        $diaHorario = $horario
            ?->dias
            ?->firstWhere(
                'dia_semana',
                $diaSemana
            );


        if (
            !$diaHorario
            ||
            !$diaHorario->es_laborable
        ) {

            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'NO_LABORABLE',
                horarioId: $horario?->id,
                tipoControl: $asignacion->tipo_control,
                observacion: 'Día no laborable según el horario asignado.'
            );

            return;
        }


        $marcaciones = Marcacion::where(
            'trabajador_id',
            $trabajador->id
        )
            ->whereDate(
                'fecha_hora',
                $fecha->toDateString()
            )
            ->where(
                'anulada',
                false
            )
            ->orderBy('fecha_hora')
            ->get();


        $entrada = $marcaciones
            ->where('tipo', 'ENTRADA')
            ->first();


        $salida = $marcaciones
            ->where('tipo', 'SALIDA')
            ->last();


        $horaEntrada = $entrada
            ? Carbon::parse($entrada->fecha_hora)
            : null;


        $horaSalida = $salida
            ? Carbon::parse($salida->fecha_hora)
            : null;


        $minutosTrabajados = 0;

        if (
            $horaEntrada
            &&
            $horaSalida
            &&
            $horaSalida->gt($horaEntrada)
        ) {
            $minutosTrabajados =
                $horaEntrada->diffInMinutes(
                    $horaSalida
                );
        }


        /*
        |--------------------------------------------------------------------------
        | LOCADOR
        |--------------------------------------------------------------------------
        |
        | El horario es referencial.
        | No se calcula tardanza automáticamente.
        |
        */

        if (
            $asignacion->tipo_control
            === 'REFERENCIAL'
        ) {

            if (!$entrada && !$salida) {

                $estado = 'SIN_REGISTRO';

                $observacion =
                    'No existen marcaciones para este día. Control referencial.';

            } else {

                $estado = 'PRESENTE';

                $observacion = null;


                if (!$entrada || !$salida) {
                    $observacion =
                        'Marcación incompleta. Control referencial.';
                }
            }


            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: $estado,
                horarioId: $horario->id,
                tipoControl: 'REFERENCIAL',

                horaProgramadaEntrada:
                    $diaHorario->hora_entrada,

                horaProgramadaSalida:
                    $diaHorario->hora_salida,

                tolerancia:
                    $diaHorario->tolerancia_minutos,

                entrada: $entrada,
                salida: $salida,

                minutosTrabajados:
                    $minutosTrabajados,

                minutosTardanza: 0,

                observacion:
                    $observacion
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTRATADO
        |--------------------------------------------------------------------------
        */

        if (!$entrada && !$salida) {

            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'FALTA',
                horarioId: $horario->id,
                tipoControl: 'OBLIGATORIO',

                horaProgramadaEntrada:
                    $diaHorario->hora_entrada,

                horaProgramadaSalida:
                    $diaHorario->hora_salida,

                tolerancia:
                    $diaHorario->tolerancia_minutos,

                observacion:
                    'No existen marcaciones de entrada ni salida.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MARCACIÓN INCOMPLETA
        |--------------------------------------------------------------------------
        */

        if (!$entrada) {

            $this->guardarAsistencia(
                trabajador: $trabajador,
                fecha: $fecha,
                estado: 'SIN_REGISTRO',
                horarioId: $horario->id,
                tipoControl: 'OBLIGATORIO',

                horaProgramadaEntrada:
                    $diaHorario->hora_entrada,

                horaProgramadaSalida:
                    $diaHorario->hora_salida,

                tolerancia:
                    $diaHorario->tolerancia_minutos,

                entrada: null,
                salida: $salida,

                minutosTrabajados: 0,
                minutosTardanza: 0,

                observacion:
                    'Existe salida, pero no existe marcación de entrada.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | CÁLCULO DE TARDANZA
        |--------------------------------------------------------------------------
        */

        $horaProgramada = Carbon::parse(
            $fecha->toDateString()
            . ' '
            . $diaHorario->hora_entrada
        );


        $horaLimite = $horaProgramada
            ->copy()
            ->addMinutes(
                $diaHorario->tolerancia_minutos
            );


        $minutosTardanza = 0;

        $estado = 'PRESENTE';


        if ($horaEntrada->gt($horaLimite)) {

            $minutosTardanza =
                $horaLimite->diffInMinutes(
                    $horaEntrada
                );

            $estado = 'TARDANZA';
        }


        $observacion = null;


        if (!$salida) {
            $observacion =
                'No existe marcación de salida.';
        }


        $this->guardarAsistencia(
            trabajador: $trabajador,
            fecha: $fecha,
            estado: $estado,
            horarioId: $horario->id,
            tipoControl: 'OBLIGATORIO',

            horaProgramadaEntrada:
                $diaHorario->hora_entrada,

            horaProgramadaSalida:
                $diaHorario->hora_salida,

            tolerancia:
                $diaHorario->tolerancia_minutos,

            entrada: $entrada,
            salida: $salida,

            minutosTrabajados:
                $minutosTrabajados,

            minutosTardanza:
                $minutosTardanza,

            observacion:
                $observacion
        );
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

        Asistencia::updateOrCreate(
            [
                'trabajador_id' =>
                    $trabajador->id,

                'fecha' =>
                    $fecha->toDateString(),
            ],
            [
                'horario_id' =>
                    $horarioId,

                'tipo_control_aplicado' =>
                    $tipoControl,

                'hora_programada_entrada' =>
                    $horaProgramadaEntrada,

                'hora_programada_salida' =>
                    $horaProgramadaSalida,

                'tolerancia_aplicada' =>
                    $tolerancia,

                'marcacion_entrada_id' =>
                    $entrada?->id,

                'marcacion_salida_id' =>
                    $salida?->id,

                'hora_entrada' =>
                    $entrada
                        ? Carbon::parse(
                            $entrada->fecha_hora
                        )->format('H:i:s')
                        : null,

                'hora_salida' =>
                    $salida
                        ? Carbon::parse(
                            $salida->fecha_hora
                        )->format('H:i:s')
                        : null,

                'minutos_trabajados' =>
                    $minutosTrabajados,

                'minutos_tardanza' =>
                    $minutosTardanza,

                'estado' =>
                    $estado,

                'observacion' =>
                    $observacion,

                'procesado_en' =>
                    now(),
            ]
        );
    }


    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check()
            &&
            Auth::user()->rol?->nombre
                === 'ADMINISTRADOR',
            403
        );
    }
}