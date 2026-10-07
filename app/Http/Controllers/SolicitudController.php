<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudController extends Controller
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

        $query = Solicitud::with([
            'trabajador.area',
            'asistencia',
            'revisadoPor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | SI NO ES ADMINISTRADOR SOLO VE SUS SOLICITUDES
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

            $buscar = trim(
                $request->buscar
            );

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'motivo',
                    'like',
                    "%{$buscar}%"
                )
                    ->orWhereHas(
                        'trabajador',
                        function ($trabajadorQuery) use ($buscar) {

                            $trabajadorQuery
                                ->where(
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
            });
        }

        /*
        |--------------------------------------------------------------------------
        | TIPO
        |--------------------------------------------------------------------------
        */
        if ($request->filled('tipo')) {

            $query->where(
                'tipo',
                $request->tipo
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

            $query->whereDate(
                'fecha_inicio',
                '<=',
                $fecha
            )
                ->where(function ($q) use ($fecha) {

                    $q->whereNull(
                        'fecha_fin'
                    )
                        ->orWhereDate(
                            'fecha_fin',
                            '>=',
                            $fecha
                        );
                });
        }

        $solicitudes = $query
            ->orderByRaw(
                "CASE
                    WHEN estado = 'PENDIENTE' THEN 1
                    WHEN estado = 'APROBADA' THEN 2
                    WHEN estado = 'RECHAZADA' THEN 3
                    ELSE 4
                END"
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view(
            'trabajadores.solicitudes',
            compact(
                'solicitudes',
                'esAdministrador'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REGISTRAR SOLICITUD
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $usuario = Auth::user();

        /*
         * El administrador revisa solicitudes;
         * no registra solicitudes en nombre de otros.
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
                'Tu cuenta no está asociada a un trabajador.'
            );
        }

        $data = $this->validarSolicitud(
            $request
        );

        $this->validarHoras(
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | ARCHIVO
        |--------------------------------------------------------------------------
        */
        $archivo = null;

        if ($request->hasFile('archivo')) {

            $archivo = $request
                ->file('archivo')
                ->store(
                    'solicitudes',
                    'public'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | ASISTENCIA RELACIONADA
        |--------------------------------------------------------------------------
        |
        | Solo vinculamos automáticamente cuando se trata
        | de una solicitud de un único día.
        |
        */
        $asistenciaId =
            $this->obtenerAsistenciaRelacionada(
                $usuario->trabajador_id,
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null
            );

        Solicitud::create([
            'trabajador_id' =>
                $usuario->trabajador_id,

            'asistencia_id' =>
                $asistenciaId,

            'tipo' =>
                $data['tipo'],

            'fecha_inicio' =>
                $data['fecha_inicio'],

            'fecha_fin' =>
                $data['fecha_fin'] ?? null,

            'hora_inicio' =>
                $data['hora_inicio'] ?? null,

            'hora_fin' =>
                $data['hora_fin'] ?? null,

            'motivo' =>
                $data['motivo'],

            'archivo' =>
                $archivo,

            'estado' =>
                'PENDIENTE',

            'revisado_por' =>
                null,

            'comentario_revision' =>
                null,

            'fecha_revision' =>
                null,
        ]);

        return redirect()
            ->route('solicitudes.index')
            ->with(
                'success',
                'Solicitud registrada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR SOLICITUD
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Solicitud $solicitud
    ) {
        $this->verificarPropietario(
            $solicitud
        );

        if (
            $solicitud->estado
            !== 'PENDIENTE'
        ) {

            return back()->with(
                'error',
                'Solo puedes editar solicitudes pendientes.'
            );
        }

        $data = $this->validarSolicitud(
            $request
        );

        $this->validarHoras(
            $data
        );

        $archivo =
            $solicitud->archivo;

        if ($request->hasFile('archivo')) {

            if (
                $archivo
                &&
                Storage::disk('public')
                    ->exists($archivo)
            ) {

                Storage::disk('public')
                    ->delete($archivo);
            }

            $archivo = $request
                ->file('archivo')
                ->store(
                    'solicitudes',
                    'public'
                );
        }

        $asistenciaId =
            $this->obtenerAsistenciaRelacionada(
                $solicitud->trabajador_id,
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null
            );

        $solicitud->update([
            'tipo' =>
                $data['tipo'],

            'fecha_inicio' =>
                $data['fecha_inicio'],

            'fecha_fin' =>
                $data['fecha_fin'] ?? null,

            'hora_inicio' =>
                $data['hora_inicio'] ?? null,

            'hora_fin' =>
                $data['hora_fin'] ?? null,

            'motivo' =>
                $data['motivo'],

            'archivo' =>
                $archivo,

            'asistencia_id' =>
                $asistenciaId,
        ]);

        return redirect()
            ->route('solicitudes.index')
            ->with(
                'success',
                'Solicitud actualizada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CANCELAR POR EL TRABAJADOR
    |--------------------------------------------------------------------------
    */
    public function cancelar(
        Solicitud $solicitud
    ) {
        $this->verificarPropietario(
            $solicitud
        );

        if (
            $solicitud->estado
            !== 'PENDIENTE'
        ) {

            return back()->with(
                'error',
                'Solo puedes cancelar solicitudes pendientes.'
            );
        }

        $solicitud->update([
            'estado' => 'CANCELADA',
        ]);

        return redirect()
            ->route('solicitudes.index')
            ->with(
                'success',
                'Solicitud cancelada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | REVISAR POR EL ADMINISTRADOR
    |--------------------------------------------------------------------------
    */
    public function revisar(
        Request $request,
        Solicitud $solicitud
    ) {
        $this->verificarAdministrador();

        if (
            $solicitud->estado
            !== 'PENDIENTE'
        ) {

            return back()->with(
                'error',
                'Esta solicitud ya fue revisada o cancelada.'
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
         * Si se rechaza, exigimos comentario.
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

        $solicitud->update([
            'estado' =>
                $data['decision'],

            'revisado_por' =>
                Auth::id(),

            'comentario_revision' =>
                $data['comentario_revision']
                ?? null,

            'fecha_revision' =>
                now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE
        |--------------------------------------------------------------------------
        |
        | Todavía NO modificamos la tabla asistencias.
        |
        | Cuando terminemos Vacaciones integraremos:
        |
        | TARDANZA aprobada          -> JUSTIFICADO
        | FALTA aprobada             -> JUSTIFICADO
        | PERMISO aprobado           -> PERMISO
        | OMISION_MARCACION aprobada -> JUSTIFICADO / regularización
        |
        */

        return redirect()
            ->route('solicitudes.index')
            ->with(
                'success',
                $data['decision']
                    === 'APROBADA'
                    ? 'Solicitud aprobada correctamente.'
                    : 'Solicitud rechazada correctamente.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN GENERAL
    |--------------------------------------------------------------------------
    */
    private function validarSolicitud(
        Request $request
    ): array {

        return $request->validate([
            'tipo' => [
                'required',
                Rule::in([
                    'TARDANZA',
                    'FALTA',
                    'PERMISO',
                    'OMISION_MARCACION',
                    'OTRO',
                ]),
            ],

            'fecha_inicio' => [
                'required',
                'date',
            ],

            'fecha_fin' => [
                'nullable',
                'date',
                'after_or_equal:fecha_inicio',
            ],

            'hora_inicio' => [
                'nullable',
                'date_format:H:i',
                'required_with:hora_fin',
            ],

            'hora_fin' => [
                'nullable',
                'date_format:H:i',
                'required_with:hora_inicio',
            ],

            'motivo' => [
                'required',
                'string',
                'max:2000',
            ],

            'archivo' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ], [
            'tipo.required' =>
                'Selecciona el tipo de solicitud.',

            'fecha_inicio.required' =>
                'Selecciona la fecha de inicio.',

            'fecha_fin.after_or_equal' =>
                'La fecha de fin no puede ser anterior a la fecha de inicio.',

            'motivo.required' =>
                'Debes indicar el motivo de la solicitud.',

            'archivo.mimes' =>
                'El sustento debe ser PDF, JPG, JPEG o PNG.',

            'archivo.max' =>
                'El archivo no puede superar los 5 MB.',

            'hora_inicio.required_with' =>
                'Debes indicar también la hora de inicio.',

            'hora_fin.required_with' =>
                'Debes indicar también la hora de fin.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR RANGO DE HORAS
    |--------------------------------------------------------------------------
    */
    private function validarHoras(
        array $data
    ): void {

        if (
            !empty($data['hora_inicio'])
            &&
            !empty($data['hora_fin'])
            &&
            $data['hora_fin']
                < $data['hora_inicio']
        ) {

            throw ValidationException::withMessages([
                'hora_fin' =>
                    'La hora de fin no puede ser anterior a la hora de inicio.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR ASISTENCIA DEL DÍA
    |--------------------------------------------------------------------------
    */
    private function obtenerAsistenciaRelacionada(
        int $trabajadorId,
        string $fechaInicio,
        ?string $fechaFin
    ): ?int {

        /*
         * Si tiene rango de varios días,
         * no vinculamos una sola asistencia.
         */
        if (
            $fechaFin
            &&
            $fechaFin !== $fechaInicio
        ) {
            return null;
        }

        return Asistencia::where(
            'trabajador_id',
            $trabajadorId
        )
            ->whereDate(
                'fecha',
                $fechaInicio
            )
            ->value('id');
    }


    /*
    |--------------------------------------------------------------------------
    | PROPIETARIO
    |--------------------------------------------------------------------------
    */
    private function verificarPropietario(
        Solicitud $solicitud
    ): void {

        $usuario = Auth::user();

        abort_unless(
            $usuario
            &&
            $usuario->trabajador_id
                === $solicitud->trabajador_id,
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