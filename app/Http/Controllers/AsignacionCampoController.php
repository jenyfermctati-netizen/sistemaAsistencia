<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AsignacionCampo;
use App\Models\Marcacion;
use App\Models\MarcacionCampo;
use App\Models\ReporteAvance;
use App\Models\Trabajador;
use App\Services\ClasificadorMarcacionService;
use App\Support\EmpresaRules;
use App\Support\EmpresaContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AsignacionCampoController extends Controller
{
    public function __construct(
        private readonly ClasificadorMarcacionService $clasificadorMarcacionService
    ) {}

    // Listado principal con filtros
    public function index(Request $request): View
    {
        $rol = $this->rolActual();
        $esAdministrador = $rol === 'ADMINISTRADOR';
        $esTrabajador = $rol === 'TRABAJADOR';

        abort_unless($esAdministrador || $esTrabajador, 403);
        $this->actualizarEstados();

        $query = AsignacionCampo::with([
            'avances' => fn ($q) => $q->with('reporte')->orderByDesc('created_at')->orderByDesc('id'),
            'trabajador.area',
            'autorizadoPor.rol',
            'marcacionesRemotas' => fn ($marcaciones) => $marcaciones
                ->with(['revisadoPor', 'marcacion'])
                ->orderByDesc('fecha_hora'),
        ]);

        if ($esTrabajador) {
            $trabajadorId = Auth::user()?->trabajador_id;
            $trabajadorId
                ? $query->where('trabajador_id', $trabajadorId)
                : $query->whereRaw('1 = 0');
        }

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

        if ($esAdministrador && $request->filled('area_id')) {
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

        $soloAsignacionesHoy = $esTrabajador
            && ! $request->boolean('todas')
            && ! $request->filled('buscar')
            && ! $request->filled('fecha')
            && ! $request->filled('estado');

        if ($esTrabajador) {
            $query->whereIn('estado', ['ACTIVA', 'PROGRAMADA']);
        }

        if ($soloAsignacionesHoy) {
            $query->whereDate('fecha_inicio', '<=', today())
                ->whereDate('fecha_fin', '>=', today())
                ->where('estado', '!=', 'CANCELADA');
        }

        $asignaciones = $query
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $trabajadores = $esAdministrador
            ? Trabajador::with('area')
                ->where('estado', 'ACTIVO')
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get()
            : collect();

        $areas = $esAdministrador
            ? Area::where('estado', true)->orderBy('nombre')->get()
            : collect();

        $marcacionesPendientes = $esAdministrador
            ? MarcacionCampo::with(['asignacion', 'trabajador.area', 'registradoPor'])
                ->where('estado', 'PENDIENTE')
                ->orderBy('fecha_hora')
                ->get()
            : collect();

        $diasMarcaciones = null;
        $resumenCampo = null;
        $marcacionesPorFecha = collect();
        $diasSinAprobar = null;
        $marcacionesSinAprobar = collect();

        if ($esTrabajador) {
            $baseMarcaciones = MarcacionCampo::where('trabajador_id', Auth::user()?->trabajador_id);
            $resumenCampo = [
                'total' => (clone $baseMarcaciones)->count(),
                'evidencias' => (clone $baseMarcaciones)->whereNotNull('evidencia_path')->where('evidencia_path', '!=', '')->count(),
                'pendientes' => (clone $baseMarcaciones)->where('estado', 'PENDIENTE')->count(),
            ];
            $diasSinAprobar = (clone $baseMarcaciones)->where('estado', '!=', 'APROBADA')
                ->selectRaw('DATE(fecha_hora) as fecha')->groupByRaw('DATE(fecha_hora)')
                ->orderByDesc('fecha')->paginate(7, ['*'], 'pendientes_page')->withQueryString();
            $marcacionesSinAprobar = (clone $baseMarcaciones)->where('estado', '!=', 'APROBADA')
                ->with(['asignacion', 'revisadoPor', 'marcacion'])
                ->whereIn(DB::raw('DATE(fecha_hora)'), $diasSinAprobar->pluck('fecha'))
                ->orderByDesc('fecha_hora')->orderByDesc('id')->get()
                ->groupBy(fn ($marca) => $marca->fecha_hora->toDateString());
            $baseMarcaciones->where('estado', 'APROBADA');
            // Marcaciones muestra exclusivamente registros aprobados.
            $resumenCampo['total'] = (clone $baseMarcaciones)->count();
            $resumenCampo['evidencias'] = (clone $baseMarcaciones)->whereNotNull('evidencia_path')->where('evidencia_path', '!=', '')->count();
            // Paginar fechas completas: nunca cortar las marcaciones de un día.
            $diasMarcaciones = (clone $baseMarcaciones)
                ->selectRaw('DATE(fecha_hora) as fecha')
                ->groupByRaw('DATE(fecha_hora)')
                ->orderByDesc('fecha')
                ->paginate(7, ['*'], 'historial_page')
                ->withQueryString();
            $fechas = $diasMarcaciones->pluck('fecha');
            $marcacionesPorFecha = (clone $baseMarcaciones)
                ->with(['asignacion', 'revisadoPor', 'marcacion'])
                ->whereIn(DB::raw('DATE(fecha_hora)'), $fechas)
                ->orderByDesc('fecha_hora')
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn ($marca) => $marca->fecha_hora->toDateString());
        }

        $marcacionesRecientes = $esAdministrador ? MarcacionCampo::with([
            'asignacion',
            'trabajador.area',
            'registradoPor',
            'revisadoPor',
            'marcacion',
        ])
            ->when(
                $esTrabajador,
                fn ($q) => $q->where('trabajador_id', Auth::user()?->trabajador_id)
            )
            ->orderByDesc('fecha_hora')
            ->limit(30)
            ->get() : collect();

        return view('trabajadores.asignaciones-campo', compact(
            'diasSinAprobar',
            'marcacionesSinAprobar',
            'soloAsignacionesHoy',
            'asignaciones',
            'trabajadores',
            'areas',
            'marcacionesPendientes',
            'marcacionesRecientes',
            'diasMarcaciones',
            'marcacionesPorFecha',
            'resumenCampo',
            'esAdministrador',
            'esTrabajador'
        ));
    }

    public function registrarAvance(Request $request, AsignacionCampo $asignacion): RedirectResponse
    {
        $this->verificarTrabajador();
        $trabajador = $this->trabajadorActual();
        abort_unless((int) $asignacion->trabajador_id === (int) $trabajador->id, 403);
        $data = $request->validate([
            'descripcion_avance' => ['required', 'string', 'max:500'],
            'archivo_avance' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $ruta = null;
        try {
            DB::transaction(function () use ($request, $asignacion, $trabajador, $data, &$ruta) {
                Trabajador::whereKey($trabajador->id)->lockForUpdate()->firstOrFail();
                $asignacion = AsignacionCampo::whereKey($asignacion->id)->lockForUpdate()->firstOrFail();
                if ($asignacion->estado === 'CANCELADA'
                    || ! today()->betweenIncluded($asignacion->fecha_inicio, $asignacion->fecha_fin)) {
                    throw ValidationException::withMessages(['descripcion_avance' => 'Solo puedes registrar avances durante el periodo de una asignación vigente.']);
                }
                $reporte = ReporteAvance::where('trabajador_id', $trabajador->id)
                    ->whereDate('fecha_presentacion', today())->first();
                if (! $reporte) {
                    $reporte = ReporteAvance::create([
                        'trabajador_id' => $trabajador->id,
                        'fecha_presentacion' => today(),
                        'estado' => 'PENDIENTE',
                    ]);
                }

                $ruta = $request->file('archivo_avance')?->store(
                    'empresas/'.app(EmpresaContext::class)->id().'/reportes-avance/actividades',
                    'local'
                );
                $reporte->actividades()->create([
                    'asignacion_campo_id' => $asignacion->id,
                    'descripcion' => trim($data['descripcion_avance']),
                    'archivo' => $ruta,
                ]);
                $reporte->update([
                    'estado' => 'PENDIENTE', 'revisado_por' => null,
                    'comentario_revision' => null, 'fecha_revision' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($ruta) {
                Storage::disk('local')->delete($ruta);
            }
            throw $exception;
        }

        return redirect()->route('asignaciones-campo.index', ['vista' => 'asignaciones'])
            ->with('success', 'Avance vinculado a la asignación y agregado a tu reporte de hoy.');
    }

    // Registrar asignación
    public function store(Request $request): RedirectResponse
    {
        $esAdministrador = $this->rolActual() === 'ADMINISTRADOR';

        if (! $esAdministrador) {
            $this->verificarTrabajador();
        }

        $data = $this->validarAsignacion($request, $esAdministrador);
        $trabajador = $esAdministrador
            ? Trabajador::where('estado', 'ACTIVO')->findOrFail($data['trabajador_id'])
            : $this->trabajadorActual();

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

        $mensaje = $esAdministrador
            ? 'Asignación de campo registrada correctamente.'
            : 'Tu trabajo de campo fue registrado. Ya puedes marcar desde la web durante el periodo indicado.';

        return redirect()
            ->route('asignaciones-campo.index')
            ->with('success', $mensaje);
    }

    // Actualizar asignación
    public function update(Request $request, AsignacionCampo $asignacion): RedirectResponse
    {
        $this->verificarAdministrador();

        if ($asignacion->estado === 'CANCELADA') {
            return back()->with('error', 'No puedes editar una asignación cancelada.');
        }

        if ($asignacion->marcacionesRemotas()->exists()) {
            return back()->with('error', 'No puedes editar una asignación que ya tiene marcaciones remotas.');
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
            ->route('asignaciones-campo.index')
            ->with('success', 'Asignación actualizada correctamente.');
    }

    // Cancelar asignación
    public function cancelar(Request $request, AsignacionCampo $asignacion): RedirectResponse
    {
        $this->verificarAdministrador();

        if ($asignacion->estado === 'CANCELADA') {
            return back()->with('error', 'La asignación ya se encuentra cancelada.');
        }

        if ($asignacion->marcacionesRemotas()->exists()) {
            return back()->with('error', 'No puedes cancelar una asignación que ya tiene marcaciones remotas.');
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
            ->route('asignaciones-campo.index')
            ->with('success', 'Asignación cancelada correctamente.');
    }

    // Registrar desde la web una marcación propia de trabajo en campo.
    public function marcarRemoto(
        Request $request,
        AsignacionCampo $asignacion
    ): RedirectResponse {
        $this->verificarTrabajador();
        $trabajador = $this->trabajadorActual();
        $ahora = now();

        abort_unless((int) $asignacion->trabajador_id === (int) $trabajador->id, 403);

        if (
            $asignacion->estado === 'CANCELADA'
            || ! $ahora->betweenIncluded(
                $asignacion->fecha_inicio->copy()->startOfDay(),
                $asignacion->fecha_fin->copy()->endOfDay()
            )
        ) {
            return back()->with('error', 'Esta asignación no está vigente para marcar en este momento.');
        }

        $data = $request->validate([
            'evidencia' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:8192',
            ],
        ], [
            'evidencia.required' => 'Debes tomar o seleccionar una foto como evidencia.',
            'evidencia.image' => 'La evidencia debe ser una imagen válida.',
            'evidencia.mimes' => 'La foto debe estar en formato JPG, PNG o WebP.',
            'evidencia.max' => 'La foto no puede pesar más de 8 MB.',
        ]);

        $archivo = $data['evidencia'];
        $extension = strtolower(
            $archivo->guessExtension()
            ?: $archivo->getClientOriginalExtension()
            ?: 'jpg'
        );
        $directorio = 'empresas/'.app(EmpresaContext::class)->id().'/marcaciones-campo/'
            .$trabajador->id.'/'
            .$ahora->format('Y/m/d');
        $evidenciaPath = $archivo->storeAs(
            $directorio,
            Str::uuid().'.'.$extension,
            'local'
        );

        if (! $evidenciaPath) {
            throw ValidationException::withMessages([
                'evidencia' => 'No se pudo guardar la foto. Inténtalo nuevamente.',
            ]);
        }

        try {
            $marcacionCampo = DB::transaction(function () use (
                $asignacion,
                $trabajador,
                $ahora,
                $evidenciaPath
            ) {
                $pendientes = MarcacionCampo::where('trabajador_id', $trabajador->id)
                    ->whereDate('fecha_hora', $ahora->toDateString())
                    ->where('estado', 'PENDIENTE')
                    ->lockForUpdate()
                    ->orderBy('fecha_hora')
                    ->get();

                $marcacionesTemporales = $pendientes->map(
                    fn (MarcacionCampo $pendiente) => new Marcacion([
                        'trabajador_id' => $pendiente->trabajador_id,
                        'fecha_hora' => $pendiente->fecha_hora,
                        'tipo' => $pendiente->tipo,
                        'anulada' => false,
                    ])
                );

                $clasificacion = $this->clasificadorMarcacionService
                    ->clasificarNuevaMarcacion($trabajador, $ahora, $marcacionesTemporales);

                if (! $clasificacion) {
                    throw ValidationException::withMessages([
                        'marcacion' => 'Ya completaste las marcaciones requeridas para hoy.',
                    ]);
                }

                return MarcacionCampo::create([
                    'asignacion_campo_id' => $asignacion->id,
                    'trabajador_id' => $trabajador->id,
                    'fecha_hora' => $ahora,
                    'tipo' => $clasificacion['tipo'],
                    'estado' => 'PENDIENTE',
                    'registrado_por' => Auth::id(),
                    'evidencia_path' => $evidenciaPath,
                ])->setAttribute('etiqueta_momento', $clasificacion['etiqueta']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($evidenciaPath);

            throw $exception;
        }

        return redirect()
            ->route('asignaciones-campo.index')
            ->with(
                'success',
                ucfirst((string) $marcacionCampo->etiqueta_momento)
                .' registrada a las '.$marcacionCampo->fecha_hora->format('H:i')
                .'. Quedó pendiente de aprobación.'
            );
    }

    public function verEvidencia(MarcacionCampo $marcacionCampo)
    {
        $rol = $this->rolActual();
        $esPropietario = $rol === 'TRABAJADOR'
            && (int) Auth::user()?->trabajador_id === (int) $marcacionCampo->trabajador_id;

        abort_unless($rol === 'ADMINISTRADOR' || $esPropietario, 403);
        abort_unless(
            $marcacionCampo->evidencia_path
            && Storage::disk('local')->exists($marcacionCampo->evidencia_path),
            404
        );

        $ruta = Storage::disk('local')->path($marcacionCampo->evidencia_path);
        $mime = Storage::disk('local')->mimeType($marcacionCampo->evidencia_path)
            ?: 'application/octet-stream';
        $extension = pathinfo($marcacionCampo->evidencia_path, PATHINFO_EXTENSION);

        return response()->file($ruta, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="evidencia-campo-'
                .$marcacionCampo->id.'.'.$extension.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // Aprobar o rechazar una marcación remota. Solo al aprobar se crea la marcación efectiva.
    public function revisarMarcacion(
        Request $request,
        MarcacionCampo $marcacionCampo
    ): RedirectResponse {
        $this->verificarAdministrador();

        $data = $request->validate([
            'decision' => ['required', Rule::in(['APROBAR', 'RECHAZAR'])],
            'observacion_revision' => [
                'nullable',
                'required_if:decision,RECHAZAR',
                'string',
                'max:500',
            ],
        ], [
            'decision.required' => 'Selecciona una decisión.',
            'observacion_revision.required_if' => 'Indica el motivo del rechazo.',
        ]);

        $estado = $data['decision'] === 'APROBAR' ? 'APROBADA' : 'RECHAZADA';

        DB::transaction(function () use ($marcacionCampo, $data, $estado) {
            $registro = MarcacionCampo::whereKey($marcacionCampo->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($registro->estado !== 'PENDIENTE') {
                throw ValidationException::withMessages([
                    'decision' => 'Esta marcación ya fue revisada.',
                ]);
            }

            $marcacionId = null;

            if ($estado === 'APROBADA') {
                $registro->loadMissing('asignacion');

                $marcacion = Marcacion::create([
                    'trabajador_id' => $registro->trabajador_id,
                    'fecha_hora' => $registro->fecha_hora,
                    'tipo' => $registro->tipo,
                    'origen' => 'WEB_CAMPO',
                    'registrado_por' => $registro->registrado_por,
                    'motivo_manual' => Str::limit(
                        'Marcación web de trabajo en campo. Actividad: '
                        .($registro->asignacion?->actividad ?? 'No especificada'),
                        500
                    ),
                    'anulada' => false,
                ]);

                $marcacionId = $marcacion->id;
            }

            $registro->update([
                'estado' => $estado,
                'revisado_por' => Auth::id(),
                'fecha_revision' => now(),
                'observacion_revision' => filled($data['observacion_revision'] ?? null)
                    ? trim($data['observacion_revision'])
                    : null,
                'marcacion_id' => $marcacionId,
            ]);
        });

        return redirect()
            ->route('asignaciones-campo.index')
            ->with(
                'success',
                $estado === 'APROBADA'
                    ? 'Marcación aprobada y asistencia recalculada correctamente.'
                    : 'Marcación rechazada correctamente.'
            );
    }

    // Validación de entrada
    private function validarAsignacion(Request $request, bool $esAdministrador = true): array
    {
        $rules = [
            'fecha_inicio' => $esAdministrador
                ? ['required', 'date']
                : ['required', 'date', 'after_or_equal:today'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'lugar' => ['nullable', 'string', 'max:250'],
            'actividad' => ['required', 'string', 'max:2000'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ];

        if ($esAdministrador) {
            $rules['trabajador_id'] = ['required', EmpresaRules::exists('trabajadores')];
        }

        return $request->validate($rules, [
            'trabajador_id.required' => 'Selecciona un trabajador.',
            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_inicio.after_or_equal' => 'No puedes autoasignarte trabajo de campo en una fecha pasada.',
            'fecha_fin.required' => 'Selecciona la fecha de fin.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'actividad.required' => 'Describe la actividad que se realizará.',
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

    private function trabajadorActual(): Trabajador
    {
        $trabajador = Auth::user()?->trabajador;

        abort_unless($trabajador && $trabajador->estado === 'ACTIVO', 403);

        return $trabajador;
    }

    private function rolActual(): string
    {
        return Auth::user()?->roleCode() ?? '';
    }

    private function verificarTrabajador(): void
    {
        abort_unless(
            Auth::check() && $this->rolActual() === 'TRABAJADOR',
            403
        );
    }

    // Control de acceso para administradores
    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() && $this->rolActual() === 'ADMINISTRADOR',
            403
        );
    }
}
