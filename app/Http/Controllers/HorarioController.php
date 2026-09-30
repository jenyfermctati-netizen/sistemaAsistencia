<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Trabajador;
use App\Models\TrabajadorHorario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Horario::with('dias')
            ->withCount('trabajadorHorarios');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado === '1'
            );
        }

        $horarios = $query
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $trabajadores = Trabajador::with([
            'area',
            'horarioActual.horario',
        ])
            ->where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $horariosActivos = Horario::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $asignaciones = TrabajadorHorario::with([
            'trabajador.area',
            'horario',
        ])
            ->orderByDesc('fecha_inicio')
            ->paginate(
                10,
                ['*'],
                'asignaciones_page'
            );

        return view(
            'trabajadores.horarios',
            compact(
                'horarios',
                'trabajadores',
                'horariosActivos',
                'asignaciones'
            )
        );
    }


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
                'Horario creado correctamente.'
            );
    }


    public function update(
        Request $request,
        Horario $horario
    ) {
        $data = $this->validarHorario(
            $request,
            $horario
        );

        DB::transaction(function () use (
            $horario,
            $data
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


    public function cambiarEstado(
        Horario $horario
    ) {
        if ($horario->estado) {

            $tieneAsignacionesActivas =
                TrabajadorHorario::where(
                    'horario_id',
                    $horario->id
                )
                    ->where('estado', true)
                    ->exists();

            if ($tieneAsignacionesActivas) {
                return back()->with(
                    'error',
                    'No puedes desactivar este horario porque tiene trabajadores asignados.'
                );
            }
        }

        $horario->estado = !$horario->estado;
        $horario->save();

        return redirect()
            ->route('trabajadores.horarios')
            ->with(
                'success',
                'Estado del horario actualizado correctamente.'
            );
    }


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
        ]);

        $trabajador = Trabajador::findOrFail(
            $data['trabajador_id']
        );

        $horario = Horario::where(
            'id',
            $data['horario_id']
        )
            ->where('estado', true)
            ->firstOrFail();

        $tipoControl =
            $trabajador->tipo_vinculo === 'CONTRATADO'
                ? 'OBLIGATORIO'
                : 'REFERENCIAL';

        $fechaInicio = Carbon::parse(
            $data['fecha_inicio']
        );

        DB::transaction(function () use (
            $trabajador,
            $horario,
            $tipoControl,
            $fechaInicio
        ) {

            $asignacionActual =
                TrabajadorHorario::where(
                    'trabajador_id',
                    $trabajador->id
                )
                    ->where('estado', true)
                    ->first();

            if ($asignacionActual) {

                if (
                    $fechaInicio->lte(
                        $asignacionActual->fecha_inicio
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fecha_inicio' =>
                            'La nueva fecha debe ser posterior al inicio del horario actual.',
                    ]);
                }

                $asignacionActual->update([
                    'fecha_fin' =>
                        $fechaInicio
                            ->copy()
                            ->subDay()
                            ->toDateString(),

                    'estado' => false,
                ]);
            }

            TrabajadorHorario::create([
                'trabajador_id' =>
                    $trabajador->id,

                'horario_id' =>
                    $horario->id,

                'tipo_control' =>
                    $tipoControl,

                'fecha_inicio' =>
                    $fechaInicio->toDateString(),

                'fecha_fin' =>
                    null,

                'estado' =>
                    true,
            ]);
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with(
                'success',
                'Horario asignado correctamente.'
            );
    }


    private function validarHorario(
        Request $request,
        ?Horario $horario = null
    ): array {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'horarios',
                    'nombre'
                )->ignore($horario?->id),
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
                'required',
                'boolean',
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

        $data = $request->all();

        foreach ($data['dias'] as $index => $dia) {

            $laborable =
                (bool) $dia['es_laborable'];

            if (!$laborable) {
                $data['dias'][$index]['hora_entrada'] =
                    null;

                $data['dias'][$index]['hora_salida'] =
                    null;

                $data['dias'][$index]['tolerancia_minutos'] =
                    0;

                continue;
            }

            if (
                empty($dia['hora_entrada'])
                ||
                empty($dia['hora_salida'])
            ) {
                throw ValidationException::withMessages([
                    "dias.$index.hora_entrada" =>
                        'Los días laborables deben tener hora de entrada y salida.',
                ]);
            }

            if (
                $dia['hora_salida']
                <=
                $dia['hora_entrada']
            ) {
                throw ValidationException::withMessages([
                    "dias.$index.hora_salida" =>
                        'La hora de salida debe ser posterior a la hora de entrada.',
                ]);
            }
        }

        return $data;
    }


    private function guardarDias(
        Horario $horario,
        array $dias
    ): void {
        foreach ($dias as $dia) {

            $horario->dias()->updateOrCreate(
                [
                    'dia_semana' =>
                        $dia['dia_semana'],
                ],
                [
                    'es_laborable' =>
                        (bool) $dia['es_laborable'],

                    'hora_entrada' =>
                        $dia['hora_entrada'] ?? null,

                    'hora_salida' =>
                        $dia['hora_salida'] ?? null,

                    'tolerancia_minutos' =>
                        $dia['tolerancia_minutos'] ?? 0,
                ]
            );
        }
    }
}