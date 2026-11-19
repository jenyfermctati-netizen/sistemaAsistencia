<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Services\AsistenciaService;
use App\Services\ReporteAsistenciaMensualService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenciaController extends Controller
{
    public function __construct(private readonly AsistenciaService $asistenciaService) {}

    public function index(Request $request)
    {
        $this->verificarConsulta();

        $fechas = $request->validate([
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $hoy = now();
        $desdeReferencia = ! empty($fechas['fecha_desde'])
            ? Carbon::parse($fechas['fecha_desde'])->startOfDay()
            : (! empty($fechas['fecha_hasta'])
                ? Carbon::parse($fechas['fecha_hasta'])->startOfMonth()
                : $hoy->copy()->startOfMonth());
        $hastaReferencia = ! empty($fechas['fecha_hasta'])
            ? Carbon::parse($fechas['fecha_hasta'])->endOfDay()
            : (! empty($fechas['fecha_desde'])
                ? Carbon::parse($fechas['fecha_desde'])->endOfDay()
                : $hoy->copy()->endOfMonth());

        if ($desdeReferencia->gt($hastaReferencia)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fecha_hasta' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            ]);
        }

        $desdeReporte = $desdeReferencia->copy()->startOfMonth();
        $hastaReporte = $hastaReferencia->copy()->endOfMonth();
        $areaIdReporte = $this->rolActual() === 'GERENTE'
            ? (int) (Auth::user()?->trabajador?->area_id ?? 0)
            : null;

        $puedeProcesar = Auth::user()->tienePermiso('asistencias.gestionar');
        $query = Asistencia::with([
            'trabajador.area',
            'horario',
            'marcacionEntrada',
            'marcacionSalidaRefrigerio',
            'marcacionRetornoRefrigerio',
            'marcacionSalida',
        ]);

        if ($this->rolActual() === 'GERENTE') {
            $areaId = Auth::user()?->trabajador?->area_id;

            $areaId
                ? $query->whereHas('trabajador', fn ($trabajador) => $trabajador->where('area_id', $areaId))
                : $query->whereRaw('1 = 0');
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->whereHas('trabajador', function ($trabajador) use ($buscar) {
                $trabajador->where('dni', 'like', "%{$buscar}%")
                    ->orWhere(fn ($nombres) => $nombres->nameContainsAllTerms($buscar));
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('tipo_control')) {
            $query->where('tipo_control_aplicado', $request->tipo_control);
        }

        // El resumen conserva los filtros de persona, periodo y tipo de control,
        // pero no el estado. Así permite comparar la distribución y cambiar de
        // estado sin que las demás métricas desaparezcan.
        $resumen = [
            'total' => (clone $query)->count(),
            'presentes' => (clone $query)->where('estado', 'PRESENTE')->count(),
            'incidencias' => (clone $query)
                ->whereIn('estado', ['TARDANZA', 'FALTA', 'SIN_REGISTRO'])
                ->count(),
            'excepciones' => (clone $query)
                ->whereIn('estado', [
                    'JUSTIFICADO',
                    'FJ',
                    'TJ',
                    'CAMPO',
                    'VACACIONES',
                    'PERMISO',
                    'FERIADO',
                    'NO_LABORABLE',
                ])
                ->count(),
        ];

        if ($request->filled('estado')) {
            match ($request->estado) {
                'INCIDENCIAS' => $query->whereIn('estado', ['TARDANZA', 'FALTA', 'SIN_REGISTRO']),
                'EXCEPCIONES' => $query->whereIn('estado', [
                    'JUSTIFICADO',
                    'FJ',
                    'TJ',
                    'CAMPO',
                    'VACACIONES',
                    'PERMISO',
                    'FERIADO',
                    'NO_LABORABLE',
                ]),
                default => $query->where('estado', $request->estado),
            };
        }

        $query->orderByDesc('fecha')->orderBy('trabajador_id')->orderBy('id');

        $formato = $request->query('descargar');
        if (in_array($formato, ['xlsx', 'pdf'], true)) {
            return app(ReporteAsistenciaMensualService::class)->descargar(
                $formato,
                $desdeReporte,
                $hastaReporte,
                $request->only(['estado', 'tipo_control']),
                $areaIdReporte,
            );
        }

        $asistencias = $query->paginate(15)->withQueryString();

        return view('asistencias.index', compact('asistencias', 'puedeProcesar', 'resumen'));
    }

    public function procesar(Request $request)
    {
        $this->verificarAdministrador();

        $data = $request->validate([
            'fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($data['fecha'])->startOfDay();
        $procesados = $this->asistenciaService->procesarFecha($fecha);

        return redirect()
            ->route('asistencias.index', [
                'fecha_desde' => $fecha->toDateString(),
                'fecha_hasta' => $fecha->toDateString(),
            ])
            ->with('success', "Se reprocesaron {$procesados} asistencias para la fecha seleccionada.");
    }

    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->tienePermiso('asistencias.gestionar'),
            403
        );
    }

    private function verificarConsulta(): void
    {
        abort_unless(
            Auth::check() && in_array($this->rolActual(), ['ADMINISTRADOR', 'RECURSOS_HUMANOS', 'GERENTE'], true),
            403
        );
    }

    private function rolActual(): string
    {
        return Auth::user()?->roleCode() ?? '';
    }
}
