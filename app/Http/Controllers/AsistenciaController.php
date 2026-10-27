<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Services\AsistenciaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenciaController extends Controller
{
    public function __construct(private readonly AsistenciaService $asistenciaService) {}

    public function index(Request $request)
    {
        $this->verificarConsulta();

        $puedeProcesar = $this->rolActual() === 'ADMINISTRADOR';
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
            $request->estado === 'INCIDENCIAS'
                ? $query->whereIn('estado', ['TARDANZA', 'FALTA', 'SIN_REGISTRO'])
                : $query->where('estado', $request->estado);
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
