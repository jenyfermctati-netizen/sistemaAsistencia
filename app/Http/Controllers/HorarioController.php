<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Services\HorarioEmpresaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HorarioController extends Controller
{
    public function __construct(
        private readonly HorarioEmpresaService $horarioEmpresaService
    ) {}

    /**
     * La empresa maneja una sola configuracion horaria.
     */
    public function index()
    {
        $horario = Horario::with('dias')
            ->orderByDesc('estado')
            ->orderBy('id')
            ->first();

        return view('trabajadores.horarios', compact('horario'));
    }

    public function store(Request $request)
    {
        $horarioExistente = Horario::orderByDesc('estado')
            ->orderBy('id')
            ->first();

        if ($horarioExistente) {
            return $this->guardar($request, $horarioExistente);
        }

        $data = $this->validarHorario($request);

        DB::transaction(function () use ($data) {
            $horario = Horario::create([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'formato_hora' => $data['formato_hora'],
                'estado' => true,
            ]);

            $this->guardarDias($horario, $data['dias']);
            $this->horarioEmpresaService->asignarATodos($horario);
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with('success', 'Horario general registrado y asignado a todo el personal activo.');
    }

    public function update(Request $request, Horario $horario)
    {
        return $this->guardar($request, $horario);
    }

    private function guardar(Request $request, Horario $horario)
    {
        $data = $this->validarHorario($request, $horario->id);

        DB::transaction(function () use ($data, $horario) {
            Horario::whereKeyNot($horario->id)->update(['estado' => false]);

            $horario->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'formato_hora' => $data['formato_hora'],
                'estado' => true,
            ]);

            $this->guardarDias($horario, $data['dias']);
            $this->horarioEmpresaService->asignarATodos($horario);
        });

        return redirect()
            ->route('trabajadores.horarios')
            ->with('success', 'Horario general actualizado correctamente.');
    }

    private function validarHorario(Request $request, ?int $horarioId = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('horarios', 'nombre')->ignore($horarioId),
            ],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'formato_hora' => ['required', Rule::in(['12', '24'])],
            'dias' => ['required', 'array', 'size:7'],
            'dias.*.dia_semana' => ['required', 'integer', 'between:1,7'],
            'dias.*.es_laborable' => ['nullable'],
            'dias.*.hora_entrada' => ['nullable', 'date_format:H:i'],
            'dias.*.hora_salida' => ['nullable', 'date_format:H:i'],
            'dias.*.tolerancia_minutos' => ['nullable', 'integer', 'min:0', 'max:180'],
        ], [
            'formato_hora.required' => 'Selecciona el formato de hora.',
            'formato_hora.in' => 'El formato de hora debe ser de 12 o 24 horas.',
        ]);
    }

    private function guardarDias(Horario $horario, array $dias): void
    {
        $diasRecibidos = collect($dias)->pluck('dia_semana')->map(fn ($dia) => (int) $dia);

        if ($diasRecibidos->unique()->count() !== 7) {
            throw ValidationException::withMessages([
                'dias' => 'La configuracion debe incluir los siete dias de la semana.',
            ]);
        }

        foreach ($dias as $dia) {
            $esLaborable = isset($dia['es_laborable'])
                && (string) $dia['es_laborable'] === '1';

            if ($esLaborable && (empty($dia['hora_entrada']) || empty($dia['hora_salida']))) {
                throw ValidationException::withMessages([
                    'dias' => 'Los dias laborables deben tener hora de entrada y salida.',
                ]);
            }

            if ($esLaborable && $dia['hora_salida'] <= $dia['hora_entrada']) {
                throw ValidationException::withMessages([
                    'dias' => 'La hora de salida debe ser posterior a la hora de entrada.',
                ]);
            }

            $horario->dias()->updateOrCreate(
                ['dia_semana' => $dia['dia_semana']],
                [
                    'es_laborable' => $esLaborable,
                    'hora_entrada' => $esLaborable ? $dia['hora_entrada'] : null,
                    'hora_salida' => $esLaborable ? $dia['hora_salida'] : null,
                    'tolerancia_minutos' => $esLaborable
                        ? ($dia['tolerancia_minutos'] ?? 0)
                        : 0,
                ]
            );
        }
    }
}
