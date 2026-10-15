<?php

namespace App\Http\Controllers;

use App\Models\AsignacionCampo;
use App\Models\Asistencia;
use App\Models\Feriado;
use App\Models\Marcacion;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use App\Models\Vacaciones;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $this->verificarConsulta();

        $puedeProcesar = $this->rolActual() === 'ADMINISTRADOR';

        $query = Asistencia::with([
            'trabajador.area',
            'horario',
            'marcacionEntrada',
            'marcacionSalida',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->whereHas('trabajador', function ($q) use ($buscar) {
                $q
                    ->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_control')) {
            $query->where('tipo_control_aplicado', $request->tipo_control);
        }

        $asistencias = $query
            ->orderByDesc('fecha')
            ->orderBy('trabajador_id')
            ->paginate(15)
            ->withQueryString();

        return view('asistencias.index', compact('asistencias', 'puedeProcesar'));
    }

    public function procesar(Request $request)
    {
        $this->verificarAdministrador();

        $data = $request->validate([
            'fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($data['fecha'])->startOfDay();
        $trabajadores = Trabajador::where('estado', 'ACTIVO')->get();
        $procesados = 0;

        DB::transaction(function () use ($trabajadores, $fecha, &$procesados) {
            foreach ($trabajadores as $trabajador) {
                $this->procesarTrabajador($trabajador, $fecha);
                $procesados++;
            }
        });

        return redirect()
            ->route('asistencias.index', [
                'fecha_desde' => $fecha->toDateString(),
                'fecha_hasta' => $fecha->toDateString(),
            ])
            ->with('success', "Se procesaron {$procesados} trabajadores.");
    }

    private function procesarTrabajador(Trabajador $trabajador, Carbon $fecha): void
    {
        $fechaTexto = $fecha->toDateString();

        /*
         * |--------------------------------------------------------------------------
         * | HORARIO Y MARCACIONES
         * |--------------------------------------------------------------------------
         * | Se obtienen antes de revisar excepciones para conservar siempre
         * | las marcaciones reales aunque el día sea feriado, vacaciones, etc.
         */
        $asignacion = TrabajadorHorario::with('horario.dias')
            ->where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_inicio', '<=', $fechaTexto)
            ->where(function ($q) use ($fechaTexto) {
                $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fechaTexto);
            })
            ->orderByDesc('fecha_inicio')
            ->first();

        $horario = $asignacion?->horario;
        $diaHorario = $horario?->dias?->firstWhere('dia_semana', $fecha->isoWeekday());

        $marcaciones = Marcacion::where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_hora', $fechaTexto)
            ->where('anulada', false)
            ->orderBy('fecha_hora')
            ->get();

        $entrada = $marcaciones->where('tipo', 'ENTRADA')->first();
        $salida = $marcaciones->where('tipo', 'SALIDA')->last();
        $minutosTrabajados = $this->calcularMinutosTrabajados($entrada, $salida);

        /*
         * |--------------------------------------------------------------------------
         * | 1. FERIADO
         * |--------------------------------------------------------------------------
         */
        $feriado = Feriado::whereDate('fecha', $fechaTexto)
            ->where('estado', true)
            ->where(function ($q) use ($trabajador) {
                $q->whereNull('area_id');

                if ($trabajador->area_id) {
                    $q->orWhere('area_id', $trabajador->area_id);
                }
            })
            ->orderByRaw('area_id IS NULL ASC')
            ->first();

        if ($feriado) {
            $this->guardarExcepcion(
                $trabajador, $fecha, 'FERIADO',
                "Feriado: {$feriado->nombre}.",
                $asignacion, $horario, $diaHorario,
                $entrada, $salida, $minutosTrabajados
            );
            return;
        }

        /*
         * |--------------------------------------------------------------------------
         * | 2. VACACIONES
         * |--------------------------------------------------------------------------
         * | GOZADA también se considera porque al reprocesar una fecha histórica
         * | el periodo debe continuar siendo reconocido como vacaciones.
         */
        $vacacion = Vacaciones::where('trabajador_id', $trabajador->id)
            ->whereIn('estado', ['APROBADA', 'PROGRAMADA', 'EN_CURSO', 'GOZADA'])
            ->whereDate('fecha_inicio', '<=', $fechaTexto)
            ->whereDate('fecha_fin', '>=', $fechaTexto)
            ->first();

        if ($vacacion) {
            $this->guardarExcepcion(
                $trabajador, $fecha, 'VACACIONES',
                'Trabajador dentro de su periodo de vacaciones aprobado.',
                $asignacion, $horario, $diaHorario,
                $entrada, $salida, $minutosTrabajados
            );
            return;
        }

        /*
         * |--------------------------------------------------------------------------
         * | 3. TRABAJO EN CAMPO
         * |--------------------------------------------------------------------------
         * | FINALIZADA sigue siendo válida para fechas históricas del periodo.
         */
        $campo = AsignacionCampo::where('trabajador_id', $trabajador->id)
            ->where('estado', '!=', 'CANCELADA')
            ->whereDate('fecha_inicio', '<=', $fechaTexto)
            ->whereDate('fecha_fin', '>=', $fechaTexto)
            ->first();

        if ($campo) {
            $detalle = 'Trabajo en campo';
            if ($campo->lugar) {
                $detalle .= " en {$campo->lugar}";
            }

            $this->guardarExcepcion(
                $trabajador, $fecha, 'CAMPO',
                Str::limit($detalle . '. Actividad: ' . $campo->actividad, 450),
                $asignacion, $horario, $diaHorario,
                $entrada, $salida, $minutosTrabajados
            );
            return;
        }

        /*
         * |--------------------------------------------------------------------------
         * | 4. SOLICITUD APROBADA
         * |--------------------------------------------------------------------------
         * | PERMISO           -> PERMISO
         * | TARDANZA          -> JUSTIFICADO
         * | FALTA             -> JUSTIFICADO
         * | OMISION_MARCACION -> JUSTIFICADO
         * | OTRO              -> JUSTIFICADO
         */
        $solicitud = Solicitud::where('trabajador_id', $trabajador->id)
            ->where('estado', 'APROBADA')
            ->whereDate('fecha_inicio', '<=', $fechaTexto)
            ->where(function ($q) use ($fechaTexto) {
                $q->where(function ($sub) use ($fechaTexto) {
                    $sub
                        ->whereNull('fecha_fin')
                        ->whereDate('fecha_inicio', $fechaTexto);
                })->orWhereDate('fecha_fin', '>=', $fechaTexto);
            })
            ->orderByRaw("CASE WHEN tipo = 'PERMISO' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_revision')
            ->first();

        if ($solicitud) {
            $estado = $solicitud->tipo === 'PERMISO' ? 'PERMISO' : 'JUSTIFICADO';
            $observacion = "Solicitud aprobada ({$solicitud->tipo})";

            if ($solicitud->motivo) {
                $observacion .= ': ' . Str::limit($solicitud->motivo, 350);
            }

            $this->guardarExcepcion(
                $trabajador, $fecha, $estado,
                $observacion,
                $asignacion, $horario, $diaHorario,
                $entrada, $salida, $minutosTrabajados
            );
            return;
        }

        /*
         * |--------------------------------------------------------------------------
         * | SIN HORARIO
         * |--------------------------------------------------------------------------
         */
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

        /*
         * |--------------------------------------------------------------------------
         * | DÍA NO LABORABLE
         * |--------------------------------------------------------------------------
         */
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

        /*
         * |--------------------------------------------------------------------------
         * | CONTROL REFERENCIAL - LOCADOR
         * |--------------------------------------------------------------------------
         */
        if ($asignacion->tipo_control === 'REFERENCIAL') {
            $estado = (!$entrada && !$salida) ? 'SIN_REGISTRO' : 'PRESENTE';
            $observacion = null;

            if (!$entrada && !$salida) {
                $observacion = 'No existen marcaciones para este día. Control referencial.';
            } elseif (!$entrada || !$salida) {
                $observacion = 'Marcación incompleta. Control referencial.';
            }

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

        /*
         * |--------------------------------------------------------------------------
         * | CONTROL OBLIGATORIO - CONTRATADO
         * |--------------------------------------------------------------------------
         */
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

        $horaEntrada = Carbon::parse($entrada->fecha_hora);
        $horaProgramada = Carbon::parse($fechaTexto . ' ' . $diaHorario->hora_entrada);
        $horaLimite = $horaProgramada->copy()->addMinutes($diaHorario->tolerancia_minutos);
        $minutosTardanza = 0;
        $estado = 'PRESENTE';

        if ($horaEntrada->gt($horaLimite)) {
            $minutosTardanza = $horaLimite->diffInMinutes($horaEntrada);
            $estado = 'TARDANZA';
        }

        $this->guardarAsistencia(
            trabajador: $trabajador,
            fecha: $fecha,
            estado: $estado,
            horarioId: $horario->id,
            tipoControl: 'OBLIGATORIO',
            horaProgramadaEntrada: $diaHorario->hora_entrada,
            horaProgramadaSalida: $diaHorario->hora_salida,
            tolerancia: $diaHorario->tolerancia_minutos,
            entrada: $entrada,
            salida: $salida,
            minutosTrabajados: $minutosTrabajados,
            minutosTardanza: $minutosTardanza,
            observacion: $salida ? null : 'No existe marcación de salida.'
        );
    }

    private function guardarExcepcion(
        Trabajador $trabajador,
        Carbon $fecha,
        string $estado,
        string $observacion,
        ?TrabajadorHorario $asignacion,
        $horario,
        $diaHorario,
        ?Marcacion $entrada,
        ?Marcacion $salida,
        int $minutosTrabajados
    ): void {
        $this->guardarAsistencia(
            trabajador: $trabajador,
            fecha: $fecha,
            estado: $estado,
            horarioId: $horario?->id,
            tipoControl: $asignacion?->tipo_control,
            horaProgramadaEntrada: $diaHorario?->hora_entrada,
            horaProgramadaSalida: $diaHorario?->hora_salida,
            tolerancia: (int) ($diaHorario?->tolerancia_minutos ?? 0),
            entrada: $entrada,
            salida: $salida,
            minutosTrabajados: $minutosTrabajados,
            minutosTardanza: 0,
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
            ? $horaEntrada->diffInMinutes($horaSalida)
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
        Asistencia::updateOrCreate(
            [
                'trabajador_id' => $trabajador->id,
                'fecha' => $fecha->toDateString(),
            ],
            [
                'horario_id' => $horarioId,
                'tipo_control_aplicado' => $tipoControl,
                'hora_programada_entrada' => $horaProgramadaEntrada,
                'hora_programada_salida' => $horaProgramadaSalida,
                'tolerancia_aplicada' => $tolerancia,
                'marcacion_entrada_id' => $entrada?->id,
                'marcacion_salida_id' => $salida?->id,
                'hora_entrada' => $entrada ? Carbon::parse($entrada->fecha_hora)->format('H:i:s') : null,
                'hora_salida' => $salida ? Carbon::parse($salida->fecha_hora)->format('H:i:s') : null,
                'minutos_trabajados' => $minutosTrabajados,
                'minutos_tardanza' => $minutosTardanza,
                'estado' => $estado,
                'observacion' => $observacion,
                'procesado_en' => now(),
            ]
        );
    }

    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() && $this->rolActual() === 'ADMINISTRADOR',
            403
        );
    }

    private function verificarConsulta(): void
    {
        abort_unless(
            Auth::check() && in_array($this->rolActual(), ['ADMINISTRADOR', 'GERENTE'], true),
            403
        );
    }

    private function rolActual(): string
    {
        return strtoupper(trim((string) Auth::user()?->rol?->nombre));
    }
}
