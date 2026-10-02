<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Horario;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HorarioController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Horario::with('dias')
            ->withCount([
                'trabajadorHorarios as asignaciones_activas' => function ($q) {
                    $q->where('estado', true);
                },
            ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'ACTIVO') {
                $query->where('estado', true);
            }

            if ($request->estado === 'INACTIVO') {
                $query->where('estado', false);
            }
        }

        $horarios = $query
            ->orderByDesc('estado')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | HORARIOS DISPONIBLES
        |--------------------------------------------------------------------------
        */
        $horariosActivos = Horario::with('dias')
            ->where('estado', true)
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TRABAJADORES ACTIVOS
        |--------------------------------------------------------------------------
        */
        $trabajadores = Trabajador::with([
            'area',
        ])
            ->where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ÁREAS
        |--------------------------------------------------------------------------
        */
        $areas = Area::where('estado', true)
            ->orderBy('nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | HISTORIAL DE ASIGNACIONES
        |--------------------------------------------------------------------------
        */
        $asignaciones = TrabajadorHorario::with([
            'trabajador.area',
            'horario',
        ])
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->paginate(
                15,
                ['*'],
                'historial'
            );

        return view(
            'trabajadores.horarios',
            compact(
                'horarios',
                'horariosActivos',
                'trabajadores',
                'areas',
                'asignaciones'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR HORARIO
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $data = $this->validarHorario($request);

        DB::transaction(function () use ($data) {

            $horario = Horario::create([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'estado' => true,
            ]);

            $this->guardarDias(
                $horario,
                $data['dias']
            );
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with(
                'success',
                'Horario registrado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR HORARIO
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Horario $horario
    ) {
        $data = $this->validarHorario(
            $request,
            $horario->id
        );

        DB::transaction(function () use (
            $data,
            $horario
        ) {

            $horario->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
            ]);

            $this->guardarDias(
                $horario,
                $data['dias']
            );
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with(
                'success',
                'Horario actualizado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO
    |--------------------------------------------------------------------------
    */
    public function cambiarEstado(Horario $horario)
    {
        if ($horario->estado) {

            $tieneAsignaciones = TrabajadorHorario::where(
                'horario_id',
                $horario->id
            )
                ->where('estado', true)
                ->exists();

            if ($tieneAsignaciones) {
                return back()->with(
                    'error',
                    'No puedes desactivar este horario porque tiene trabajadores asignados actualmente.'
                );
            }
        }

        $horario->update([
            'estado' => !$horario->estado,
        ]);

        return back()->with(
            'success',
            $horario->estado
                ? 'Horario activado correctamente.'
                : 'Horario desactivado correctamente.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ASIGNACIÓN INDIVIDUAL
    |--------------------------------------------------------------------------
    */
    public function asignar(Request $request)
    {
        $data = $request->validate([
            'trabajador_id' => [
                'required',
                'exists:trabajadores,id',
            ],

            'horario_id' => [
                'required',
                'exists:horarios,id',
            ],

            'fecha_inicio' => [
                'required',
                'date',
            ],
        ], [
            'trabajador_id.required' =>
                'Selecciona un trabajador.',

            'horario_id.required' =>
                'Selecciona un horario.',

            'fecha_inicio.required' =>
                'Selecciona una fecha de inicio.',
        ]);

        $trabajador = Trabajador::findOrFail(
            $data['trabajador_id']
        );

        $horario = Horario::findOrFail(
            $data['horario_id']
        );

        if (!$horario->estado) {
            return back()->with(
                'error',
                'No puedes asignar un horario inactivo.'
            );
        }

        $fechaInicio = Carbon::parse(
            $data['fecha_inicio']
        );

        DB::transaction(function () use (
            $trabajador,
            $horario,
            $fechaInicio
        ) {

            $this->cerrarAsignacionesActuales(
                $trabajador,
                $fechaInicio
            );

            $this->crearAsignacion(
                $trabajador,
                $horario,
                $fechaInicio
            );
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with(
                'success',
                'Horario asignado correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ASIGNACIÓN MASIVA
    |--------------------------------------------------------------------------
    */
    public function asignarMasivo(Request $request)
    {
        $data = $request->validate([
            'horario_id' => [
                'required',
                'exists:horarios,id',
            ],

            'fecha_inicio' => [
                'required',
                'date',
            ],

            'trabajadores' => [
                'required',
                'array',
                'min:1',
            ],

            'trabajadores.*' => [
                'integer',
                'exists:trabajadores,id',
            ],
        ], [
            'horario_id.required' =>
                'Selecciona un horario.',

            'fecha_inicio.required' =>
                'Selecciona una fecha de inicio.',

            'trabajadores.required' =>
                'Selecciona al menos un trabajador.',

            'trabajadores.min' =>
                'Selecciona al menos un trabajador.',
        ]);

        $horario = Horario::findOrFail(
            $data['horario_id']
        );

        if (!$horario->estado) {
            return back()->with(
                'error',
                'No puedes asignar un horario inactivo.'
            );
        }

        $fechaInicio = Carbon::parse(
            $data['fecha_inicio']
        );

        $trabajadores = Trabajador::whereIn(
            'id',
            $data['trabajadores']
        )
            ->where('estado', 'ACTIVO')
            ->get();

        if ($trabajadores->isEmpty()) {
            return back()->with(
                'error',
                'No se encontraron trabajadores activos.'
            );
        }

        DB::transaction(function () use (
            $trabajadores,
            $horario,
            $fechaInicio
        ) {

            foreach ($trabajadores as $trabajador) {

                $this->cerrarAsignacionesActuales(
                    $trabajador,
                    $fechaInicio
                );

                $this->crearAsignacion(
                    $trabajador,
                    $horario,
                    $fechaInicio
                );
            }
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with(
                'success',
                'Horario asignado correctamente a '
                . $trabajadores->count()
                . ' trabajadores.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CERRAR HORARIO ANTERIOR
    |--------------------------------------------------------------------------
    */
    private function cerrarAsignacionesActuales(
        Trabajador $trabajador,
        Carbon $fechaInicio
    ): void {

        $asignaciones = TrabajadorHorario::where(
            'trabajador_id',
            $trabajador->id
        )
            ->where('estado', true)
            ->get();

        foreach ($asignaciones as $asignacion) {

            $inicioAnterior = Carbon::parse(
                $asignacion->fecha_inicio
            );

            /*
             * Si el horario anterior comenzó antes
             * de la nueva asignación, cerramos
             * el día anterior.
             */
            if ($inicioAnterior->lt($fechaInicio)) {

                $asignacion->update([
                    'fecha_fin' => $fechaInicio
                        ->copy()
                        ->subDay()
                        ->toDateString(),

                    'estado' => false,
                ]);

            } else {

                /*
                 * Si tenía la misma fecha de inicio,
                 * simplemente lo desactivamos.
                 */
                $asignacion->update([
                    'estado' => false,
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR ASIGNACIÓN
    |--------------------------------------------------------------------------
    */
    private function crearAsignacion(
        Trabajador $trabajador,
        Horario $horario,
        Carbon $fechaInicio
    ): void {

        /*
         * CONTRATADO = obligatorio
         * LOCADOR    = referencial
         */
        $tipoControl =
            $trabajador->tipo_vinculo === 'LOCADOR'
                ? 'REFERENCIAL'
                : 'OBLIGATORIO';

        TrabajadorHorario::create([
            'trabajador_id' => $trabajador->id,
            'horario_id' => $horario->id,
            'tipo_control' => $tipoControl,
            'fecha_inicio' => $fechaInicio->toDateString(),
            'fecha_fin' => null,
            'estado' => true,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN DE HORARIO
    |--------------------------------------------------------------------------
    */
    private function validarHorario(
        Request $request,
        ?int $horarioId = null
    ): array {

        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'horarios',
                    'nombre'
                )->ignore($horarioId),
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:300',
            ],

            'dias' => [
                'required',
                'array',
                'size:7',
            ],

            'dias.*.dia_semana' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'dias.*.es_laborable' => [
                'nullable',
            ],

            'dias.*.hora_entrada' => [
                'nullable',
                'date_format:H:i',
            ],

            'dias.*.hora_salida' => [
                'nullable',
                'date_format:H:i',
            ],

            'dias.*.tolerancia_minutos' => [
                'nullable',
                'integer',
                'min:0',
                'max:180',
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR DÍAS
    |--------------------------------------------------------------------------
    */
    private function guardarDias(
        Horario $horario,
        array $dias
    ): void {

        foreach ($dias as $dia) {

            $esLaborable = isset(
                $dia['es_laborable']
            )
                && (string) $dia['es_laborable'] === '1';

            if ($esLaborable) {

                if (
                    empty($dia['hora_entrada'])
                    ||
                    empty($dia['hora_salida'])
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'dias' =>
                            'Los días laborables deben tener hora de entrada y salida.',
                    ]);
                }

                if (
                    $dia['hora_salida']
                    <= $dia['hora_entrada']
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'dias' =>
                            'La hora de salida debe ser posterior a la hora de entrada.',
                    ]);
                }
            }

            $horario->dias()->updateOrCreate(
                [
                    'dia_semana' =>
                        $dia['dia_semana'],
                ],
                [
                    'es_laborable' =>
                        $esLaborable,

                    'hora_entrada' =>
                        $esLaborable
                            ? $dia['hora_entrada']
                            : null,

                    'hora_salida' =>
                        $esLaborable
                            ? $dia['hora_salida']
                            : null,

                    'tolerancia_minutos' =>
                        $esLaborable
                            ? ($dia['tolerancia_minutos'] ?? 0)
                            : 0,
                ]
            );
        }
    }
}