<?php

namespace App\Http\Controllers;

use App\Models\ReprogramacionVacacion;
use App\Models\Vacaciones;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VacacionesController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $usuario = Auth::user();

        $esAdministrador =
            $usuario->rol?->nombre === 'ADMINISTRADOR';

        $this->actualizarEstadosAutomaticos();

        $query = Vacaciones::with([
            'trabajador.area',
            'registradoPor',
            'revisadoPor',
            'reprogramaciones.reprogramadoPor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | TRABAJADOR SOLO VE SUS VACACIONES
        |--------------------------------------------------------------------------
        */
        if (!$esAdministrador) {

            if (!$usuario->trabajador_id) {

                $query->whereRaw('1 = 0');

            } else {

                $query->where(
                    'trabajador_id',
                    $usuario->trabajador_id
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR
        |--------------------------------------------------------------------------
        */
        if ($request->filled('buscar')) {

            $buscar = trim($request->buscar);

            $query->whereHas(
                'trabajador',
                function ($q) use ($buscar) {

                    $q->where(
                        'dni',
                        'like',
                        "%{$buscar}%"
                    )
                    ->orWhere(
                        'nombres',
                        'like',
                        "%{$buscar}%"
                    )
                    ->orWhere(
                        'apellidos',
                        'like',
                        "%{$buscar}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ESTADO
        |--------------------------------------------------------------------------
        */
        if ($request->filled('estado')) {

            $query->where(
                'estado',
                $request->estado
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */
        if ($request->filled('fecha')) {

            $fecha = $request->fecha;

            $query
                ->whereDate(
                    'fecha_inicio',
                    '<=',
                    $fecha
                )
                ->whereDate(
                    'fecha_fin',
                    '>=',
                    $fecha
                );
        }

        /*
        |--------------------------------------------------------------------------
        | AÑO
        |--------------------------------------------------------------------------
        */
        if ($request->filled('anio')) {

            $query->whereYear(
                'fecha_inicio',
                $request->anio
            );
        }

        $vacaciones = $query
            ->orderByRaw(
                "CASE
                    WHEN estado = 'PENDIENTE' THEN 1
                    WHEN estado = 'EN_CURSO' THEN 2
                    WHEN estado = 'PROGRAMADA' THEN 3
                    WHEN estado = 'APROBADA' THEN 4
                    WHEN estado = 'GOZADA' THEN 5
                    WHEN estado = 'RECHAZADA' THEN 6
                    ELSE 7
                END"
            )
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view(
            'trabajadores.vacaciones',
            compact(
                'vacaciones',
                'esAdministrador'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR SOLICITUD
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $usuario = Auth::user();

        /*
         * Normalmente las vacaciones son solicitadas
         * por el trabajador.
         */
        if (
            $usuario->rol?->nombre
            === 'ADMINISTRADOR'
        ) {
            abort(403);
        }

        if (!$usuario->trabajador_id) {

            return back()->with(
                'error',
                'Tu cuenta no está vinculada a un trabajador.'
            );
        }

        $data = $this->validarVacaciones(
            $request
        );

        $this->validarCruceFechas(
            $usuario->trabajador_id,
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        $dias = $this->calcularDias(
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        Vacaciones::create([
            'trabajador_id' =>
                $usuario->trabajador_id,

            'fecha_inicio' =>
                $data['fecha_inicio'],

            'fecha_fin' =>
                $data['fecha_fin'],

            'dias_solicitados' =>
                $dias,

            'estado' =>
                'PENDIENTE',

            'observacion' =>
                $data['observacion']
                ?? null,

            'registrado_por' =>
                Auth::id(),

            'revisado_por' =>
                null,

            'comentario_revision' =>
                null,

            'fecha_revision' =>
                null,
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with(
                'success',
                'Solicitud de vacaciones registrada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR SOLICITUD
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Vacaciones $vacacion
    ) {
        $this->verificarPropietario(
            $vacacion
        );

        if (
            $vacacion->estado
            !== 'PENDIENTE'
        ) {

            return back()->with(
                'error',
                'Solo puedes editar vacaciones pendientes.'
            );
        }

        $data = $this->validarVacaciones(
            $request
        );

        $this->validarCruceFechas(
            $vacacion->trabajador_id,
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $vacacion->id
        );

        $dias = $this->calcularDias(
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        $vacacion->update([
            'fecha_inicio' =>
                $data['fecha_inicio'],

            'fecha_fin' =>
                $data['fecha_fin'],

            'dias_solicitados' =>
                $dias,

            'observacion' =>
                $data['observacion']
                ?? null,
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with(
                'success',
                'Solicitud de vacaciones actualizada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | REVISAR
    |--------------------------------------------------------------------------
    */
    public function revisar(
        Request $request,
        Vacaciones $vacacion
    ) {
        $this->verificarAdministrador();

        if (
            $vacacion->estado
            !== 'PENDIENTE'
        ) {

            return back()->with(
                'error',
                'Estas vacaciones ya fueron revisadas.'
            );
        }

        $data = $request->validate([
            'decision' => [
                'required',
                Rule::in([
                    'APROBADA',
                    'RECHAZADA',
                ]),
            ],

            'comentario_revision' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | COMENTARIO OBLIGATORIO SI RECHAZA
        |--------------------------------------------------------------------------
        */
        if (
            $data['decision']
            === 'RECHAZADA'
            &&
            blank(
                $data['comentario_revision']
                ?? null
            )
        ) {

            throw ValidationException::withMessages([
                'comentario_revision' =>
                    'Debes indicar el motivo del rechazo.',
            ]);
        }

        $estadoFinal =
            $data['decision'];

        /*
        |--------------------------------------------------------------------------
        | SI APRUEBA, COLOCAMOS ESTADO SEGÚN FECHA
        |--------------------------------------------------------------------------
        */
        if (
            $data['decision']
            === 'APROBADA'
        ) {

            $estadoFinal =
                $this->determinarEstadoAprobado(
                    $vacacion->fecha_inicio,
                    $vacacion->fecha_fin
                );
        }

        $vacacion->update([
            'estado' =>
                $estadoFinal,

            'revisado_por' =>
                Auth::id(),

            'comentario_revision' =>
                $data['comentario_revision']
                ?? null,

            'fecha_revision' =>
                now(),
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with(
                'success',
                $data['decision']
                    === 'APROBADA'
                    ? 'Vacaciones aprobadas correctamente.'
                    : 'Vacaciones rechazadas correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CANCELAR
    |--------------------------------------------------------------------------
    */
    public function cancelar(
        Vacaciones $vacacion
    ) {
        $usuario = Auth::user();

        $esAdministrador =
            $usuario->rol?->nombre
            === 'ADMINISTRADOR';

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRADOR
        |--------------------------------------------------------------------------
        */
        if ($esAdministrador) {

            if (
                !in_array(
                    $vacacion->estado,
                    [
                        'PENDIENTE',
                        'APROBADA',
                        'PROGRAMADA',
                    ],
                    true
                )
            ) {

                return back()->with(
                    'error',
                    'Estas vacaciones ya no pueden cancelarse.'
                );
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | TRABAJADOR
            |--------------------------------------------------------------------------
            */
            $this->verificarPropietario(
                $vacacion
            );

            if (
                $vacacion->estado
                !== 'PENDIENTE'
            ) {

                return back()->with(
                    'error',
                    'Solo puedes cancelar solicitudes pendientes.'
                );
            }
        }

        $vacacion->update([
            'estado' => 'CANCELADA',
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with(
                'success',
                'Vacaciones canceladas correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | REPROGRAMAR
    |--------------------------------------------------------------------------
    */
    public function reprogramar(
        Request $request,
        Vacaciones $vacacion
    ) {
        $this->verificarAdministrador();

        if (
            !in_array(
                $vacacion->estado,
                [
                    'APROBADA',
                    'PROGRAMADA',
                ],
                true
            )
        ) {

            return back()->with(
                'error',
                'Solo se pueden reprogramar vacaciones aprobadas o programadas.'
            );
        }

        $data = $request->validate([
            'fecha_inicio_nueva' => [
                'required',
                'date',
            ],

            'fecha_fin_nueva' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio_nueva',
            ],

            'motivo' => [
                'required',
                'string',
                'max:2000',
            ],
        ], [
            'fecha_inicio_nueva.required' =>
                'Selecciona la nueva fecha de inicio.',

            'fecha_fin_nueva.required' =>
                'Selecciona la nueva fecha de fin.',

            'fecha_fin_nueva.after_or_equal' =>
                'La nueva fecha de fin no puede ser anterior al inicio.',

            'motivo.required' =>
                'Debes indicar el motivo de la reprogramación.',
        ]);

        $this->validarCruceFechas(
            $vacacion->trabajador_id,
            $data['fecha_inicio_nueva'],
            $data['fecha_fin_nueva'],
            $vacacion->id
        );

        $diasNuevos =
            $this->calcularDias(
                $data['fecha_inicio_nueva'],
                $data['fecha_fin_nueva']
            );

        DB::transaction(function () use (
            $vacacion,
            $data,
            $diasNuevos
        ) {

            ReprogramacionVacacion::create([
                'vacaciones_id' =>
                    $vacacion->id,

                'fecha_inicio_anterior' =>
                    $vacacion
                        ->fecha_inicio
                        ->toDateString(),

                'fecha_fin_anterior' =>
                    $vacacion
                        ->fecha_fin
                        ->toDateString(),

                'fecha_inicio_nueva' =>
                    $data['fecha_inicio_nueva'],

                'fecha_fin_nueva' =>
                    $data['fecha_fin_nueva'],

                'dias_nuevos' =>
                    $diasNuevos,

                'motivo' =>
                    $data['motivo'],

                'reprogramado_por' =>
                    Auth::id(),
            ]);

            $nuevoEstado =
                $this->determinarEstadoAprobado(
                    $data['fecha_inicio_nueva'],
                    $data['fecha_fin_nueva']
                );

            $vacacion->update([
                'fecha_inicio' =>
                    $data['fecha_inicio_nueva'],

                'fecha_fin' =>
                    $data['fecha_fin_nueva'],

                'dias_solicitados' =>
                    $diasNuevos,

                'estado' =>
                    $nuevoEstado,
            ]);
        });

        return redirect()
            ->route('vacaciones.index')
            ->with(
                'success',
                'Vacaciones reprogramadas correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN
    |--------------------------------------------------------------------------
    */
    private function validarVacaciones(
        Request $request
    ): array {

        return $request->validate([
            'fecha_inicio' => [
                'required',
                'date',
            ],

            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'fecha_inicio.required' =>
                'Selecciona la fecha de inicio.',

            'fecha_fin.required' =>
                'Selecciona la fecha de fin.',

            'fecha_fin.after_or_equal' =>
                'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULAR DÍAS
    |--------------------------------------------------------------------------
    |
    | Actualmente contamos días calendario de manera inclusiva.
    |
    | Ejemplo:
    | 10 al 12 = 3 días.
    |
    */
    private function calcularDias(
        string $fechaInicio,
        string $fechaFin
    ): int {

        $inicio =
            Carbon::parse(
                $fechaInicio
            )->startOfDay();

        $fin =
            Carbon::parse(
                $fechaFin
            )->startOfDay();

        return $inicio->diffInDays(
            $fin
        ) + 1;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR CRUCE
    |--------------------------------------------------------------------------
    */
    private function validarCruceFechas(
        int $trabajadorId,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarId = null
    ): void {

        $query = Vacaciones::where(
            'trabajador_id',
            $trabajadorId
        )
            ->whereNotIn(
                'estado',
                [
                    'RECHAZADA',
                    'CANCELADA',
                ]
            )
            ->where(function ($q) use (
                $fechaInicio,
                $fechaFin
            ) {

                $q
                    ->whereDate(
                        'fecha_inicio',
                        '<=',
                        $fechaFin
                    )
                    ->whereDate(
                        'fecha_fin',
                        '>=',
                        $fechaInicio
                    );
            });

        if ($ignorarId) {

            $query->where(
                'id',
                '!=',
                $ignorarId
            );
        }

        if ($query->exists()) {

            throw ValidationException::withMessages([
                'fecha_inicio' =>
                    'El trabajador ya tiene vacaciones registradas que se cruzan con ese periodo.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ESTADO DE VACACIONES APROBADAS
    |--------------------------------------------------------------------------
    */
    private function determinarEstadoAprobado(
        $fechaInicio,
        $fechaFin
    ): string {

        $hoy = today();

        $inicio =
            Carbon::parse(
                $fechaInicio
            )->startOfDay();

        $fin =
            Carbon::parse(
                $fechaFin
            )->startOfDay();

        if ($hoy->lt($inicio)) {
            return 'PROGRAMADA';
        }

        if (
            $hoy->betweenIncluded(
                $inicio,
                $fin
            )
        ) {
            return 'EN_CURSO';
        }

        return 'GOZADA';
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADOS
    |--------------------------------------------------------------------------
    */
    private function actualizarEstadosAutomaticos(): void
    {
        $hoy = today()
            ->toDateString();

        /*
         * PROGRAMADA
         */
        Vacaciones::whereNotIn(
            'estado',
            [
                'PENDIENTE',
                'RECHAZADA',
                'CANCELADA',
            ]
        )
            ->whereDate(
                'fecha_inicio',
                '>',
                $hoy
            )
            ->update([
                'estado' => 'PROGRAMADA',
            ]);

        /*
         * EN CURSO
         */
        Vacaciones::whereNotIn(
            'estado',
            [
                'PENDIENTE',
                'RECHAZADA',
                'CANCELADA',
            ]
        )
            ->whereDate(
                'fecha_inicio',
                '<=',
                $hoy
            )
            ->whereDate(
                'fecha_fin',
                '>=',
                $hoy
            )
            ->update([
                'estado' => 'EN_CURSO',
            ]);

        /*
         * GOZADA
         */
        Vacaciones::whereNotIn(
            'estado',
            [
                'PENDIENTE',
                'RECHAZADA',
                'CANCELADA',
            ]
        )
            ->whereDate(
                'fecha_fin',
                '<',
                $hoy
            )
            ->update([
                'estado' => 'GOZADA',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PROPIETARIO
    |--------------------------------------------------------------------------
    */
    private function verificarPropietario(
        Vacaciones $vacacion
    ): void {

        $usuario = Auth::user();

        abort_unless(
            $usuario
            &&
            $usuario->trabajador_id
                === $vacacion->trabajador_id,
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADMINISTRADOR
    |--------------------------------------------------------------------------
    */
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