<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AsignacionCampo;
use App\Models\Trabajador;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AsignacionCampoController extends Controller
{
    // Listado principal con filtros
    public function index(Request $request): View
    {
        $this->verificarAdministrador();
        $this->actualizarEstados();

        $query = AsignacionCampo::with([
            'trabajador.area',
            'autorizadoPor',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q
                    ->where('lugar', 'like', "%{$buscar}%")
                    ->orWhere('actividad', 'like', "%{$buscar}%")
                    ->orWhereHas('trabajador', function ($trabajadorQuery) use ($buscar) {
                        $trabajadorQuery
                            ->where('dni', 'like', "%{$buscar}%")
                            ->orWhere('nombres', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('area_id')) {
            $query->whereHas('trabajador', function ($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        if ($request->filled('fecha')) {
            $fecha = $request->fecha;

            $query
                ->whereDate('fecha_inicio', '<=', $fecha)
                ->whereDate('fecha_fin', '>=', $fecha);
        }

        $asignaciones = $query
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $trabajadores = Trabajador::with('area')
            ->where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $areas = Area::orderBy('nombre')->get();

        return view('trabajadores.asignaciones-campo', compact('asignaciones', 'trabajadores', 'areas'));
    }

    // Registrar asignación
    public function store(Request $request): RedirectResponse
    {
        $this->verificarAdministrador();

        $data = $this->validarAsignacion($request);
        $trabajador = Trabajador::findOrFail($data['trabajador_id']);

        $this->validarCruceFechas(
            $trabajador->id,
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        $estado = $this->determinarEstado(
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        AsignacionCampo::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'lugar' => $data['lugar'] ?? null,
            'actividad' => $data['actividad'],
            'estado' => $estado,
            'autorizado_por' => Auth::id(),
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()
            ->route('asignaciones-campo.index')
            ->with('success', 'Asignación de campo registrada correctamente.');
    }

    // Actualizar asignación
    public function update(Request $request, AsignacionCampo $asignacion): RedirectResponse
    {
        $this->verificarAdministrador();

        if ($asignacion->estado === 'CANCELADA') {
            return back()->with('error', 'No puedes editar una asignación cancelada.');
        }

        $data = $this->validarAsignacion($request);

        $this->validarCruceFechas(
            $data['trabajador_id'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $asignacion->id
        );

        $estado = $this->determinarEstado(
            $data['fecha_inicio'],
            $data['fecha_fin']
        );

        $asignacion->update([
            'trabajador_id' => $data['trabajador_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'lugar' => $data['lugar'] ?? null,
            'actividad' => $data['actividad'],
            'estado' => $estado,
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()
            ->route('trabajadores.asignancion-campo')
            ->with('success', 'Asignación actualizada correctamente.');
    }

    // Cancelar asignación
    public function cancelar(Request $request, AsignacionCampo $asignacion): RedirectResponse
    {
        $this->verificarAdministrador();

        if ($asignacion->estado === 'CANCELADA') {
            return back()->with('error', 'La asignación ya se encuentra cancelada.');
        }

        $data = $request->validate([
            'motivo_cancelacion' => ['required', 'string', 'max:500'],
        ], [
            'motivo_cancelacion.required' => 'Debes indicar el motivo de cancelación.',
        ]);

        $observacionActual = trim($asignacion->observacion ?? '');
        $nuevaObservacion = $observacionActual !== ''
            ? "{$observacionActual}\nCancelación: {$data['motivo_cancelacion']}"
            : "Cancelación: {$data['motivo_cancelacion']}";

        $asignacion->update([
            'estado' => 'CANCELADA',
            'observacion' => $nuevaObservacion,
        ]);

        return redirect()
            ->route('trabajadores.asignancion-campo')
            ->with('success', 'Asignación cancelada correctamente.');
    }

    // Validación de entrada
    private function validarAsignacion(Request $request): array
    {
        return $request->validate([
            'trabajador_id' => ['required', 'exists:trabajadores,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'lugar' => ['nullable', 'string', 'max:250'],
            'actividad' => ['required', 'string'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ], [
            'trabajador_id.required' => 'Selecciona un trabajador.',
            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_fin.required' => 'Selecciona la fecha de fin.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'actividad.required' => 'Describe la actividad que realizará el trabajador.',
        ]);
    }

    // Evitar asignaciones superpuestas
    private function validarCruceFechas(
        int $trabajadorId,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarId = null
    ): void {
        $query = AsignacionCampo::where('trabajador_id', $trabajadorId)
            ->where('estado', '!=', 'CANCELADA')
            ->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q
                    ->whereDate('fecha_inicio', '<=', $fechaFin)
                    ->whereDate('fecha_fin', '>=', $fechaInicio);
            });

        if ($ignorarId) {
            $query->where('id', '!=', $ignorarId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'trabajador_id' => 'El trabajador ya tiene una asignación de campo que se cruza con esas fechas.',
            ]);
        }
    }

    // Determinar estado según las fechas
    private function determinarEstado(string $fechaInicio, string $fechaFin): string
    {
        $hoy = today();
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        if ($hoy->lt($inicio)) {
            return 'PROGRAMADA';
        }

        if ($hoy->betweenIncluded($inicio, $fin)) {
            return 'ACTIVA';
        }

        return 'FINALIZADA';
    }

    // Sincronizar estados automáticamente
    private function actualizarEstados(): void
    {
        $hoy = today()->toDateString();

        // Futuras
        AsignacionCampo::where('estado', '!=', 'CANCELADA')
            ->whereDate('fecha_inicio', '>', $hoy)
            ->update(['estado' => 'PROGRAMADA']);

        // Vigentes
        AsignacionCampo::where('estado', '!=', 'CANCELADA')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->update(['estado' => 'ACTIVA']);

        // Terminadas
        AsignacionCampo::where('estado', '!=', 'CANCELADA')
            ->whereDate('fecha_fin', '<', $hoy)
            ->update(['estado' => 'FINALIZADA']);
    }

    // Control de acceso para administradores
    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->rol?->nombre === 'ADMINISTRADOR',
            403
        );
    }
}
