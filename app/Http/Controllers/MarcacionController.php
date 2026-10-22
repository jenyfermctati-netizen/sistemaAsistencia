<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Marcacion;
use App\Models\Trabajador;
use App\Services\AsistenciaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MarcacionController extends Controller
{
    public function __construct(
        private readonly AsistenciaService $asistenciaService
    ) {}

    public function index(Request $request)
    {
        $this->verificarAdministrador();

        $query = Marcacion::with([
            'trabajador.area',
            'registradoPor',
            'anuladaPor',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->whereHas('trabajador', function ($q) use ($buscar) {
                $q
                    ->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere(
                        'codigo_biometrico',
                        'like',
                        "%{$buscar}%"
                    );
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate(
                'fecha_hora',
                '>=',
                $request->fecha_desde
            );
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate(
                'fecha_hora',
                '<=',
                $request->fecha_hasta
            );
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('origen')) {
            $query->where('origen', $request->origen);
        }

        if ($request->estado === 'ACTIVA') {
            $query->where('anulada', false);
        } elseif ($request->estado === 'ANULADA') {
            $query->where('anulada', true);
        }

        $marcaciones = $query
            ->orderByDesc('fecha_hora')
            ->paginate(15)
            ->withQueryString();

        return view(
            'marcaciones.index',
            compact('marcaciones')
        );
    }

    public function manual(Request $request)
    {
        $this->verificarAdministrador();

        $trabajadores = Trabajador::with([
            'area',
            'horarioActual.horario.dias',
        ])
            ->where('estado', 'ACTIVO')
            ->when(
                $request->filled('buscar'),
                function ($q) use ($request) {
                    $buscar = trim($request->buscar);

                    $q->where(function ($sub) use ($buscar) {
                        $sub
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
                    });
                }
            )
            ->when(
                $request->filled('area_id'),
                fn ($q) => $q->where(
                    'area_id',
                    $request->area_id
                )
            )
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $marcacionesHoy = Marcacion::whereIn(
            'trabajador_id',
            $trabajadores->pluck('id')
        )
            ->whereDate('fecha_hora', today())
            ->where('anulada', false)
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy('trabajador_id');

        $areas = Area::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view(
            'marcaciones.manual',
            compact(
                'trabajadores',
                'marcacionesHoy',
                'areas'
            )
        );
    }

    public function marcarAhora(
        Request $request,
        Trabajador $trabajador
    ) {
        $this->verificarAdministrador();

        abort_unless(
            $trabajador->estado === 'ACTIVO',
            422,
            'El trabajador no está activo.'
        );

        $trabajador->load('horarioActual.horario.dias');

        $marcaciones = Marcacion::where(
            'trabajador_id',
            $trabajador->id
        )
            ->whereDate('fecha_hora', today())
            ->where('anulada', false)
            ->orderBy('fecha_hora')
            ->get();

        $diaHorario = $trabajador->horarioActual?->horario?->dias?->firstWhere(
            'dia_semana',
            today()->isoWeekday()
        );
        $esJornadaCompleta = $trabajador->horarioActual?->tipo_control === 'OBLIGATORIO'
            && filled($diaHorario?->hora_salida_refrigerio)
            && filled($diaHorario?->hora_retorno_refrigerio);
        $totalEsperado = $esJornadaCompleta ? 4 : 2;
        $numeroMarca = $marcaciones->count();

        if ($numeroMarca >= $totalEsperado) {
            return $this->error(
                $request,
                'El trabajador ya completó las marcaciones requeridas para hoy.'
            );
        }

        $tipo = $numeroMarca % 2 === 0 ? 'ENTRADA' : 'SALIDA';
        $etiqueta = $esJornadaCompleta
            ? ['entrada de la mañana', 'salida a refrigerio', 'retorno del refrigerio', 'salida final'][$numeroMarca]
            : ['entrada', 'salida'][$numeroMarca];

        $marcacion = Marcacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_hora' => now(),
            'tipo' => $tipo,
            'origen' => 'MANUAL',
            'registrado_por' => Auth::id(),
            'motivo_manual' => 'Marcación manual rápida registrada desde el panel administrativo.',
            'anulada' => false,
        ]);

        $this->reprocesarAsistencia(
            $trabajador,
            $marcacion->fecha_hora
        );

        $mensaje = ucfirst($etiqueta).' registrada correctamente.';

        return $this->respuesta(
            $request,
            $mensaje,
            route('marcaciones.manual')
        );
    }

    public function store(Request $request)
    {
        $this->verificarAdministrador();

        $data = $request->validate([
            'trabajador_id' => [
                'required',
                'exists:trabajadores,id',
            ],
            'fecha_hora' => [
                'required',
                'date',
            ],
            'tipo' => [
                'required',
                Rule::in(['ENTRADA', 'SALIDA']),
            ],
            'motivo_manual' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'trabajador_id.required' => 'Selecciona un trabajador.',
            'fecha_hora.required' => 'Ingresa la fecha y hora.',
            'tipo.required' => 'Selecciona el tipo de marcación.',
            'motivo_manual.required' => 'Debes indicar el motivo del registro especial.',
        ]);

        $trabajador = Trabajador::findOrFail(
            $data['trabajador_id']
        );

        $marcacion = Marcacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_hora' => $data['fecha_hora'],
            'tipo' => $data['tipo'],
            'origen' => 'MANUAL',
            'registrado_por' => Auth::id(),
            'motivo_manual' => $data['motivo_manual'],
            'anulada' => false,
        ]);

        $this->reprocesarAsistencia(
            $trabajador,
            $marcacion->fecha_hora
        );

        return $this->respuesta(
            $request,
            'Marcación especial registrada correctamente.',
            route('marcaciones.manual')
        );
    }

    public function anular(Request $request, Marcacion $marcacion)
    {
        $this->verificarAdministrador();

        if ($marcacion->anulada) {
            return $this->error(
                $request,
                'Esta marcación ya se encuentra anulada.'
            );
        }

        $data = $request->validate([
            'motivo_anulacion' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'motivo_anulacion.required' => 'Debes indicar el motivo de la anulación.',
        ]);

        $marcacion->update([
            'anulada' => true,
            'anulada_por' => Auth::id(),
            'motivo_anulacion' => $data['motivo_anulacion'],
            'fecha_anulacion' => now(),
        ]);

        $marcacion->load('trabajador');

        $this->reprocesarAsistencia(
            $marcacion->trabajador,
            $marcacion->fecha_hora
        );

        return $this->respuesta(
            $request,
            'Marcación anulada correctamente y asistencia recalculada.',
            route('marcaciones.index')
        );
    }

    private function reprocesarAsistencia(Trabajador $trabajador, Carbon|string $fechaHora): void
    {
        $fecha = $fechaHora instanceof Carbon
            ? $fechaHora->copy()
            : Carbon::parse($fechaHora);

        $this->asistenciaService->procesarTrabajador(
            $trabajador,
            $fecha->startOfDay()
        );
    }

    private function respuesta(Request $request, string $mensaje, string $url)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensaje,
                'refresh_url' => $url,
            ]);
        }

        return redirect($url)
            ->with('success', $mensaje);
    }

    private function error(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensaje,
            ], 422);
        }

        return back()->with('error', $mensaje);
    }

    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() &&
                Auth::user()->rol?->nombre === 'ADMINISTRADOR',
            403
        );
    }
}
