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
    public function index(Request $request)
    {
        $this->verificarAccesoVacaciones();

        $usuario = Auth::user();
        $esAdministrador = $usuario->rol?->nombre === 'ADMINISTRADOR';

        $this->actualizarEstadosAutomaticos();

        $query = Vacaciones::with([
            'trabajador.area',
            'registradoPor',
            'revisadoPor',
            'reprogramaciones.reprogramadoPor',
        ])->whereHas('trabajador', fn ($q) =>
            $q->where('tipo_vinculo', 'CONTRATADO')
        );

        if (!$esAdministrador) {
            $query->where('trabajador_id', $usuario->trabajador_id);
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->whereHas('trabajador', function ($q) use ($buscar) {
                $q->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_inicio', '<=', $request->fecha)
                ->whereDate('fecha_fin', '>=', $request->fecha);
        }

        if ($request->filled('anio')) {
            $query->whereYear('fecha_inicio', $request->anio);
        }

        $vacaciones = $query
            ->orderByRaw("
                CASE
                    WHEN estado = 'PENDIENTE' THEN 1
                    WHEN estado = 'EN_CURSO' THEN 2
                    WHEN estado = 'PROGRAMADA' THEN 3
                    WHEN estado = 'APROBADA' THEN 4
                    WHEN estado = 'GOZADA' THEN 5
                    WHEN estado = 'RECHAZADA' THEN 6
                    ELSE 7
                END
            ")
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('trabajadores.vacaciones', compact('vacaciones', 'esAdministrador'));
    }

    public function store(Request $request)
    {
        $this->verificarAccesoVacaciones();

        $usuario = Auth::user();

        if ($usuario->rol?->nombre === 'ADMINISTRADOR') {
            abort(403);
        }

        if (!$usuario->trabajador_id) {
            return back()->with('error', 'Tu cuenta no está vinculada a un trabajador.');
        }

        $data = $this->validarVacaciones($request);

        $this->validarCruceFechas(
            $usuario->trabajador_id,
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        Vacaciones::create([
            'trabajador_id' => $usuario->trabajador_id,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'dias_solicitados' => $this->calcularDias($data['fecha_inicio'], $data['fecha_fin']),
            'estado' => 'PENDIENTE',
            'observacion' => $data['observacion'] ?? null,
            'registrado_por' => Auth::id(),
            'revisado_por' => null,
            'comentario_revision' => null,
            'fecha_revision' => null,
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with('success', 'Solicitud de vacaciones registrada correctamente.');
    }

    public function update(Request $request, Vacaciones $vacacion)
    {
        $this->verificarAccesoVacaciones();
        $this->verificarVacacionContratado($vacacion);
        $this->verificarPropietario($vacacion);

        if ($vacacion->estado !== 'PENDIENTE') {
            return back()->with('error', 'Solo puedes editar vacaciones pendientes.');
        }

        $data = $this->validarVacaciones($request);

        $this->validarCruceFechas(
            $vacacion->trabajador_id,
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $vacacion->id
        );

        $vacacion->update([
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'dias_solicitados' => $this->calcularDias($data['fecha_inicio'], $data['fecha_fin']),
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with('success', 'Solicitud de vacaciones actualizada correctamente.');
    }

    public function revisar(Request $request, Vacaciones $vacacion)
    {
        $this->verificarAdministrador();
        $this->verificarVacacionContratado($vacacion);

        if ($vacacion->estado !== 'PENDIENTE') {
            return back()->with('error', 'Estas vacaciones ya fueron revisadas.');
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['APROBADA', 'RECHAZADA'])],
            'comentario_revision' => ['nullable', 'string', 'max:500'],
        ]);

        if (
            $data['decision'] === 'RECHAZADA' &&
            blank($data['comentario_revision'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'comentario_revision' => 'Debes indicar el motivo del rechazo.',
            ]);
        }

        $estado = $data['decision'] === 'APROBADA'
            ? $this->determinarEstadoAprobado($vacacion->fecha_inicio, $vacacion->fecha_fin)
            : 'RECHAZADA';

        $vacacion->update([
            'estado' => $estado,
            'revisado_por' => Auth::id(),
            'comentario_revision' => $data['comentario_revision'] ?? null,
            'fecha_revision' => now(),
        ]);

        return redirect()
            ->route('vacaciones.index')
            ->with(
                'success',
                $data['decision'] === 'APROBADA'
                    ? 'Vacaciones aprobadas correctamente.'
                    : 'Vacaciones rechazadas correctamente.'
            );
    }

    public function cancelar(Vacaciones $vacacion)
    {
        $this->verificarAccesoVacaciones();
        $this->verificarVacacionContratado($vacacion);

        $usuario = Auth::user();
        $esAdministrador = $usuario->rol?->nombre === 'ADMINISTRADOR';

        if ($esAdministrador) {
            if (!in_array($vacacion->estado, ['PENDIENTE', 'APROBADA', 'PROGRAMADA'], true)) {
                return back()->with('error', 'Estas vacaciones ya no pueden cancelarse.');
            }
        } else {
            $this->verificarPropietario($vacacion);

            if ($vacacion->estado !== 'PENDIENTE') {
                return back()->with('error', 'Solo puedes cancelar solicitudes pendientes.');
            }
        }

        $vacacion->update(['estado' => 'CANCELADA']);

        return redirect()
            ->route('vacaciones.index')
            ->with('success', 'Vacaciones canceladas correctamente.');
    }

    public function reprogramar(Request $request, Vacaciones $vacacion)
    {
        $this->verificarAdministrador();
        $this->verificarVacacionContratado($vacacion);

        if (!in_array($vacacion->estado, ['APROBADA', 'PROGRAMADA'], true)) {
            return back()->with(
                'error',
                'Solo se pueden reprogramar vacaciones aprobadas o programadas.'
            );
        }

        $data = $request->validate([
            'fecha_inicio_nueva' => ['required', 'date'],
            'fecha_fin_nueva' => ['required', 'date', 'after_or_equal:fecha_inicio_nueva'],
            'motivo' => ['required', 'string', 'max:2000'],
        ], [
            'fecha_inicio_nueva.required' => 'Selecciona la nueva fecha de inicio.',
            'fecha_fin_nueva.required' => 'Selecciona la nueva fecha de fin.',
            'fecha_fin_nueva.after_or_equal' => 'La nueva fecha de fin no puede ser anterior al inicio.',
            'motivo.required' => 'Debes indicar el motivo de la reprogramación.',
        ]);

        $this->validarCruceFechas(
            $vacacion->trabajador_id,
            $data['fecha_inicio_nueva'],
            $data['fecha_fin_nueva'],
            $vacacion->id
        );

        $diasNuevos = $this->calcularDias(
            $data['fecha_inicio_nueva'],
            $data['fecha_fin_nueva']
        );

        DB::transaction(function () use ($vacacion, $data, $diasNuevos) {
            ReprogramacionVacacion::create([
                'vacaciones_id' => $vacacion->id,
                'fecha_inicio_anterior' => $vacacion->fecha_inicio->toDateString(),
                'fecha_fin_anterior' => $vacacion->fecha_fin->toDateString(),
                'fecha_inicio_nueva' => $data['fecha_inicio_nueva'],
                'fecha_fin_nueva' => $data['fecha_fin_nueva'],
                'dias_nuevos' => $diasNuevos,
                'motivo' => $data['motivo'],
                'reprogramado_por' => Auth::id(),
            ]);

            $vacacion->update([
                'fecha_inicio' => $data['fecha_inicio_nueva'],
                'fecha_fin' => $data['fecha_fin_nueva'],
                'dias_solicitados' => $diasNuevos,
                'estado' => $this->determinarEstadoAprobado(
                    $data['fecha_inicio_nueva'],
                    $data['fecha_fin_nueva']
                ),
            ]);
        });

        return redirect()
            ->route('vacaciones.index')
            ->with('success', 'Vacaciones reprogramadas correctamente.');
    }

    private function validarVacaciones(Request $request): array
    {
        return $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ], [
            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_fin.required' => 'Selecciona la fecha de fin.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ]);
    }

    private function calcularDias(string $fechaInicio, string $fechaFin): int
    {
        return Carbon::parse($fechaInicio)
            ->startOfDay()
            ->diffInDays(Carbon::parse($fechaFin)->startOfDay()) + 1;
    }

    private function validarCruceFechas(
        int $trabajadorId,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarId = null
    ): void {
        $query = Vacaciones::where('trabajador_id', $trabajadorId)
            ->whereNotIn('estado', ['RECHAZADA', 'CANCELADA'])
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio);

        if ($ignorarId) {
            $query->where('id', '!=', $ignorarId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El trabajador ya tiene vacaciones registradas que se cruzan con ese periodo.',
            ]);
        }
    }

    private function determinarEstadoAprobado($fechaInicio, $fechaFin): string
    {
        $hoy = today();
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        if ($hoy->lt($inicio)) {
            return 'PROGRAMADA';
        }

        if ($hoy->betweenIncluded($inicio, $fin)) {
            return 'EN_CURSO';
        }

        return 'GOZADA';
    }

    private function actualizarEstadosAutomaticos(): void
    {
        $hoy = today()->toDateString();

        $base = fn () => Vacaciones::whereHas('trabajador', fn ($q) =>
            $q->where('tipo_vinculo', 'CONTRATADO')
        )->whereNotIn('estado', ['PENDIENTE', 'RECHAZADA', 'CANCELADA']);

        $base()
            ->whereDate('fecha_inicio', '>', $hoy)
            ->update(['estado' => 'PROGRAMADA']);

        $base()
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->update(['estado' => 'EN_CURSO']);

        $base()
            ->whereDate('fecha_fin', '<', $hoy)
            ->update(['estado' => 'GOZADA']);
    }

    private function verificarPropietario(Vacaciones $vacacion): void
    {
        abort_unless(
            Auth::user()?->trabajador_id === $vacacion->trabajador_id,
            403
        );
    }

    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() &&
            Auth::user()->rol?->nombre === 'ADMINISTRADOR',
            403
        );
    }

    private function verificarAccesoVacaciones(): void
    {
        $usuario = Auth::user();

        if ($usuario->rol?->nombre === 'ADMINISTRADOR') {
            return;
        }

        abort_unless(
            $usuario->trabajador &&
            $usuario->trabajador->tipo_vinculo === 'CONTRATADO',
            403,
            'El módulo de vacaciones está disponible únicamente para trabajadores contratados.'
        );
    }

    private function verificarVacacionContratado(Vacaciones $vacacion): void
    {
        $vacacion->loadMissing('trabajador');

        abort_unless(
            $vacacion->trabajador &&
            $vacacion->trabajador->tipo_vinculo === 'CONTRATADO',
            403,
            'Las vacaciones solo pueden gestionarse para trabajadores contratados.'
        );
    }
}
