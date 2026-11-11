<?php

namespace App\Http\Controllers;

use App\Models\ActividadReporte;
use App\Models\Area;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\ReporteActividadesExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteAvanceController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $rol = $usuario->rol?->nombre;
        $esTrabajador = $rol === 'TRABAJADOR';
        $esEncargadoArea = $esTrabajador
            && (bool) $usuario->trabajador?->esEncargadoDeSuArea();
        $vistaEquipo = $esEncargadoArea && $request->query('vista') === 'equipo';
        $esVistaPersonal = $esTrabajador && ! $vistaEquipo;
        $puedeGestionar = $rol === 'ADMINISTRADOR';
        $puedeRevisar = in_array($rol, ['ADMINISTRADOR', 'GERENTE'], true);
        $areaSupervisorId = match (true) {
            $rol === 'GERENTE' => $usuario->trabajador?->area_id,
            $vistaEquipo => $usuario->trabajador?->area_id,
            default => null,
        };
        $parametrosVista = $vistaEquipo ? ['vista' => 'equipo'] : [];
        $pestanaActiva = in_array($request->query('pestana'), ['avances', 'pendientes'], true)
            ? $request->query('pestana')
            : 'avances';

        $query = ReporteAvance::with([
            'trabajador.area',
            'revisor',
            'actividades.asignacionCampo',
            'pendientes.resueltoPor',
            'pendientes.revisadoPor',
            'pendientes.area',
        ]);

        if ($esVistaPersonal) {
            $query->where('trabajador_id', $usuario->trabajador_id);
        } elseif ($rol === 'GERENTE' || $vistaEquipo) {
            $areaSupervisorId
                ? $query->whereHas('trabajador', fn ($trabajador) => $trabajador->where('area_id', $areaSupervisorId))
                    ->where('trabajador_id', '!=', $usuario->trabajador_id)
                : $query->whereRaw('1 = 0');
        }

        $formato = $request->query('descargar');
        $esDescarga = $pestanaActiva === 'avances' && in_array($formato, ['csv', 'xlsx', 'pdf'], true);
        $diasConReportes = $pestanaActiva === 'avances' && ! $esDescarga
            ? (clone $query)->withoutEagerLoads()->select('fecha_presentacion')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) AS por_revisar', ['PENDIENTE'])
                ->groupBy('fecha_presentacion')
                ->orderByDesc('fecha_presentacion')
                ->get()
            : collect();

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('trabajador', function ($trabajador) use ($buscar) {
                    $trabajador
                        ->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('dni', 'like', "%{$buscar}%");
                })->orWhereHas('actividades', fn ($actividad) => $actividad->where('descripcion', 'like', "%{$buscar}%"))->orWhereHas('pendientes', function ($pendiente) use ($buscar) {
                    $pendiente
                        ->where('descripcion', 'like', "%{$buscar}%")
                        ->orWhere('detalle', 'like', "%{$buscar}%");
                });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($rol === 'ADMINISTRADOR' && $request->filled('area_id')) {
            $areaId = $request->integer('area_id');
            $query->whereHas('trabajador', fn ($trabajador) => $trabajador->where('area_id', $areaId));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_presentacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_presentacion', '<=', $request->fecha_hasta);
        }

        $soloHoy =
            ! $esVistaPersonal
            && ! $request->boolean('historial')
            && ! $request->filled('estado')
            && ! $request->filled('fecha_desde')
            && ! $request->filled('fecha_hasta');

        if ($soloHoy) {
            $query->whereDate('fecha_presentacion', today());
        }

        $query->orderByDesc('fecha_presentacion')->orderByDesc('id');

        if ($esDescarga) {
            if ($formato === 'csv') {
                return $this->descargarActividades($query);
            }

            $desde = $soloHoy ? today()->toDateString() : $request->query('fecha_desde');
            $hasta = $soloHoy ? today()->toDateString() : $request->query('fecha_hasta');

            return app(ReporteActividadesExportService::class)->descargar(
                $formato,
                $query,
                $desde ?: null,
                $hasta ?: null,
            );
        }

        /*
         * El trabajador mantiene paginación porque consulta su propio historial.
         * Administración necesita la colección completa para agrupar:
         * Área > Trabajador > Reportes.
         */
        $reportes = $esVistaPersonal
            ? $query->paginate(10)->withQueryString()
            : $query->get();

        $areas = ! $esVistaPersonal
            ? Area::where('estado', true)
                ->when($rol === 'GERENTE' || $vistaEquipo, fn ($query) => $areaSupervisorId
                    ? $query->whereKey($areaSupervisorId)
                    : $query->whereRaw('1 = 0'))
                ->with(['trabajadores' => function ($q) use ($vistaEquipo, $usuario) {
                    $q
                        ->where('estado', 'ACTIVO')
                        ->when($vistaEquipo, fn ($trabajadores) => $trabajadores->where('id', '!=', $usuario->trabajador_id))
                        ->orderBy('apellidos')
                        ->orderBy('nombres');
                }])
                ->orderBy('nombre')
                ->get()
            : collect();

        $pendientesQuery = PendienteAvance::with([
            'area',
            'creadoPor.trabajador',
            'reporte.trabajador.area',
            'resueltoPor',
            'revisadoPor',
        ])->abiertosOEnRevision();

        if (! $puedeGestionar) {
            $areaId = $usuario->trabajador?->area_id;

            if (! $areaId) {
                $pendientesQuery->whereRaw('1 = 0');
            } else {
                $pendientesQuery->deArea($areaId);
            }
        } elseif ($request->filled('area_id')) {
            $areaId = $request->integer('area_id');
            $pendientesQuery->deArea($areaId);
        }

        $pendientesArea = $pendientesQuery->orderByDesc('created_at')->get();

        $pendientesPorRevisar = $puedeGestionar
            ? $pendientesArea->where('estado_revision', 'POR_REVISAR')->values()
            : collect();

        $reporteHoy = $usuario->trabajador_id
            ? ReporteAvance::with(['actividades', 'pendientes'])
                ->where('trabajador_id', $usuario->trabajador_id)
                ->whereDate('fecha_presentacion', today())
                ->first()
            : null;

        $vista = $esVistaPersonal
            ? 'reportesAvance.trabajador.index'
            : 'reportesAvance.admin.index';

        return view($vista, compact(
            'reportes',
            'diasConReportes',
            'pendientesArea',
            'reporteHoy',
            'areas',
            'puedeGestionar',
            'puedeRevisar',
            'pestanaActiva',
            'pendientesPorRevisar',
            'esEncargadoArea',
            'vistaEquipo',
            'parametrosVista'
        ));
    }

    private function descargarActividades(Builder $reportes): StreamedResponse
    {
        return response()->streamDownload(function () use ($reportes) {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF");
            fputcsv($archivo, ['Fecha', 'Área', 'Trabajador', 'Reporte', 'Estado', 'Actividad'], ';', '"', '');

            foreach ($reportes->withOnly(['trabajador.area', 'actividades'])->lazy() as $reporte) {
                foreach ($reporte->actividades as $actividad) {
                    $fila = [
                        $reporte->fecha_presentacion->format('d/m/Y'),
                        $reporte->trabajador?->area?->nombre ?? '',
                        trim(($reporte->trabajador?->nombres ?? '').' '.($reporte->trabajador?->apellidos ?? '')),
                        (string) $reporte->id,
                        $reporte->estado === 'PENDIENTE' ? 'Por revisar' : $reporte->estado,
                        $actividad->descripcion,
                    ];

                    $fila = array_map(
                        fn (string $valor) => preg_match('/^[\s\x00-\x1F]*[=+@-]|^[\t\r\n]/', $valor) ? "'".$valor : $valor,
                        $fila
                    );
                    fputcsv($archivo, $fila, ';', '"', '');
                }
            }

            fclose($archivo);
        }, 'actividades-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request)
    {
        $usuario = Auth::user();

        if (! $usuario->trabajador_id) {
            return back()->with('error', 'Este usuario no está asociado a un trabajador.');
        }

        if (ReporteAvance::where('trabajador_id', $usuario->trabajador_id)
            ->whereDate('fecha_presentacion', today())
            ->exists()) {
            return back()->with('error', 'Ya registraste tu reporte de avance de hoy.');
        }

        $data = $this->validarReporte($request);

        $this->crearReporte(
            $request,
            $data,
            $usuario->trabajador,
            $usuario,
            'TRABAJADOR'
        );

        return redirect()
            ->route('reportesAvance.index')
            ->with('success', 'Reporte diario registrado correctamente.');
    }

    public function storeParaTrabajador(Request $request, Trabajador $trabajador)
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol?->nombre === 'ADMINISTRADOR', 403);

        if ($trabajador->estado !== 'ACTIVO') {
            return back()->with('error', 'Solo puedes registrar avances para trabajadores activos.');
        }

        if (ReporteAvance::where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_presentacion', today())
            ->exists()) {
            return back()->with(
                'error',
                "{$trabajador->nombres} {$trabajador->apellidos} ya tiene un reporte registrado hoy."
            );
        }

        $data = $this->validarReporte($request, 'avanceTrabajador');

        $this->crearReporte(
            $request,
            $data,
            $trabajador,
            $usuario,
            'ADMINISTRADOR'
        );

        return redirect()
            ->route('trabajadores.index')
            ->with(
                'success',
                "Avance de {$trabajador->nombres} {$trabajador->apellidos} registrado correctamente."
            );
    }

    public function update(Request $request, ReporteAvance $reporte)
    {
        $usuario = Auth::user();
        abort_unless($reporte->trabajador_id === $usuario->trabajador_id, 403);

        if ($reporte->estado === 'REVISADO') {
            return back()->with('error', 'Un reporte revisado ya no puede modificarse.');
        }

        $data = $this->validarReporte($request);

        DB::transaction(function () use ($request, $data, $reporte) {
            $this->sincronizarActividades($request, $reporte, $data['actividades'] ?? []);
            $this->sincronizarPendientes($reporte, $data['pendientes'] ?? []);

            if ($reporte->estado === 'OBSERVADO') {
                $reporte->update([
                    'estado' => 'PENDIENTE',
                    'revisado_por' => null,
                    'comentario_revision' => null,
                    'fecha_revision' => null,
                ]);
            }
        });

        return redirect()
            ->route('reportesAvance.index')
            ->with('success', 'Reporte diario actualizado correctamente.');
    }

    public function agregarAvance(Request $request, ReporteAvance $reporte)
    {
        $usuario = Auth::user();

        abort_unless(
            $usuario->trabajador_id &&
                $reporte->trabajador_id === $usuario->trabajador_id,
            403
        );

        if (! $reporte->fecha_presentacion?->isToday()) {
            return back()->with('error', 'Solo puedes agregar avances al reporte del día actual.');
        }

        $data = $this->validarContenidoNuevo($request, 'agregarAvance');

        DB::transaction(function () use ($request, $data, $reporte, $usuario) {
            foreach ($data['actividades'] as $indice => $actividad) {
                $archivo = $request->file("actividades.{$indice}.archivo");
                $ruta = $archivo
                    ? $archivo->store('reportes-avance/actividades', 'public')
                    : null;

                $reporte->actividades()->create([
                    'descripcion' => $actividad['descripcion'],
                    'archivo' => $ruta,
                ]);
            }

            foreach ($data['pendientes'] ?? [] as $pendiente) {
                $reporte->pendientes()->create([
                    'area_id' => $usuario->trabajador->area_id,
                    'creado_por' => $usuario->id,
                    'origen' => 'TRABAJADOR',
                    'descripcion' => $pendiente['descripcion'],
                    'detalle' => $pendiente['detalle'] ?? null,
                    'ruta_archivos' => $pendiente['ruta_archivos'] ?? null,
                    'estado' => 'PENDIENTE',
                ]);
            }

            $reporte->update([
                'estado' => 'PENDIENTE',
                'revisado_por' => null,
                'comentario_revision' => null,
                'fecha_revision' => null,
            ]);
        });

        return redirect()
            ->route('reportesAvance.index')
            ->with('success', 'El nuevo avance se agregó al reporte de hoy.');
    }

    public function revisar(Request $request, ReporteAvance $reporte)
    {
        $usuario = $this->autorizarRevisor();

        $this->autorizarSupervisorDelReporte($reporte);

        $data = $request->validate([
            'estado' => ['required', Rule::in(['REVISADO', 'OBSERVADO'])],
            'comentario_revision' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['estado'] === 'OBSERVADO' && blank($data['comentario_revision'] ?? null)) {
            return back()->withErrors([
                'comentario_revision' => 'Debes indicar la observación del reporte.',
            ]);
        }

        $reporte->update([
            'estado' => $data['estado'],
            'revisado_por' => $usuario->id,
            'comentario_revision' => $data['comentario_revision'] ?? null,
            'fecha_revision' => now(),
        ]);

        return redirect()
            ->route('reportesAvance.index', $this->parametrosRetornoRevision($request))
            ->with('success', 'Reporte revisado correctamente.');
    }

    public function revisarMasivo(Request $request)
    {
        $usuario = $this->autorizarRevisor();

        $data = $request->validate([
            'reporte_ids' => ['required', 'array', 'min:1'],
            'reporte_ids.*' => ['required', 'integer', 'distinct', 'exists:reportes_avance,id'],
        ], [
            'reporte_ids.required' => 'Selecciona al menos un reporte para aprobar.',
            'reporte_ids.min' => 'Selecciona al menos un reporte para aprobar.',
        ]);

        $resultado = DB::transaction(function () use ($data, $usuario) {
            $reportes = ReporteAvance::with('trabajador')
                ->whereIn('id', $data['reporte_ids'])
                ->lockForUpdate()
                ->get();

            foreach ($reportes as $reporte) {
                $this->autorizarSupervisorDelReporte($reporte);
            }

            $porAprobar = $reportes->where('estado', '!=', 'REVISADO');

            if ($porAprobar->isEmpty()) {
                return ['reportes' => 0, 'actividades' => 0];
            }

            $idsAprobar = $porAprobar->pluck('id');

            ReporteAvance::whereIn('id', $idsAprobar)->update([
                'estado' => 'REVISADO',
                'revisado_por' => $usuario->id,
                'comentario_revision' => null,
                'fecha_revision' => now(),
            ]);

            $actividadesAprobadas = ActividadReporte::whereIn('reporte_avance_id', $idsAprobar)->count();

            return [
                'reportes' => $idsAprobar->count(),
                'actividades' => $actividadesAprobadas,
            ];
        });

        $parametros = $this->parametrosRetornoRevision($request);

        if ($resultado['reportes'] === 0) {
            return redirect()
                ->route('reportesAvance.index', $parametros)
                ->with('error', 'Los reportes seleccionados ya habían sido aprobados.');
        }

        $etiquetaReportes = $resultado['reportes'] === 1 ? 'reporte' : 'reportes';
        $etiquetaActividades = $resultado['actividades'] === 1 ? 'actividad' : 'actividades';

        return redirect()
            ->route('reportesAvance.index', $parametros)
            ->with(
                'success',
                "Se aprobaron {$resultado['reportes']} {$etiquetaReportes} ({$resultado['actividades']} {$etiquetaActividades}) correctamente."
            );
    }

    public function storePendiente(Request $request)
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol?->nombre === 'ADMINISTRADOR', 403);

        $data = $request->validateWithBag('pendienteArea', [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'descripcion' => ['required', 'string', 'max:500'],
            'detalle' => ['nullable', 'string', 'max:2000'],
            'ruta_archivos' => ['nullable', 'string', 'max:500'],
        ], [
            'area_id.required' => 'Selecciona el área responsable.',
            'descripcion.required' => 'Describe la tarea pendiente.',
        ]);

        if (! Area::whereKey($data['area_id'])->where('estado', true)->exists()) {
            return back()->withErrors([
                'area_id' => 'El área seleccionada no está activa.',
            ], 'pendienteArea')->withInput();
        }

        PendienteAvance::create([
            'reporte_avance_id' => null,
            'area_id' => $data['area_id'],
            'creado_por' => $usuario->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => $data['descripcion'],
            'detalle' => $data['detalle'] ?? null,
            'ruta_archivos' => $data['ruta_archivos'] ?? null,
            'estado' => 'PENDIENTE',
        ]);

        return redirect()
            ->route('reportesAvance.index', ['pestana' => 'pendientes'])
            ->with('success', 'Tarea pendiente asignada al área correctamente.');
    }

    public function storePendienteTrabajador(Request $request)
    {
        $usuario = Auth::user();
        $trabajador = $usuario->trabajador;

        abort_unless(
            $usuario->rol?->nombre === 'TRABAJADOR' &&
                $trabajador?->area_id,
            403
        );

        if (! $trabajador->area()->where('estado', true)->exists()) {
            return back()->with('error', 'Tu área no se encuentra activa.');
        }

        $data = $request->validateWithBag('pendienteTrabajador', [
            'descripcion' => ['required', 'string', 'max:500'],
            'detalle' => ['nullable', 'string', 'max:2000'],
            'ruta_archivos' => ['nullable', 'string', 'max:500'],
        ], [
            'descripcion.required' => 'Describe la tarea que quedó pendiente.',
        ]);

        PendienteAvance::create([
            'reporte_avance_id' => null,
            'area_id' => $trabajador->area_id,
            'creado_por' => $usuario->id,
            'origen' => 'TRABAJADOR',
            'descripcion' => $data['descripcion'],
            'detalle' => $data['detalle'] ?? null,
            'ruta_archivos' => $data['ruta_archivos'] ?? null,
            'estado' => 'PENDIENTE',
        ]);

        return redirect()
            ->route('reportesAvance.index', ['pestana' => 'pendientes'])
            ->with('success', 'Pendiente registrado y compartido con tu área.');
    }

    public function resolverPendiente(PendienteAvance $pendiente)
    {
        $usuario = Auth::user();

        if (! $usuario->trabajador_id || ! $usuario->trabajador?->area_id) {
            abort(403);
        }

        $pendiente->load('reporte.trabajador', 'area');

        $areaPendiente = $pendiente->area_id
            ?? $pendiente->reporte?->trabajador?->area_id;

        abort_unless(
            $areaPendiente === $usuario->trabajador->area_id,
            403
        );

        if ($pendiente->estado === 'RESUELTO') {
            return back()->with('error', 'Este pendiente ya fue aprobado como resuelto.');
        }

        if ($pendiente->estado_revision === 'POR_REVISAR') {
            return back()->with('error', 'Este pendiente ya fue enviado para revisión.');
        }

        $pendiente->update([
            'estado' => 'RESUELTO',
            'estado_revision' => 'POR_REVISAR',
            'observacion_revision' => null,
            'revisado_por' => null,
            'fecha_revision' => null,
            'resuelto_por' => $usuario->trabajador_id,
            'fecha_resolucion' => now(),
        ]);

        return redirect()
            ->route('reportesAvance.index', ['pestana' => 'pendientes'])
            ->with('success', 'Pendiente marcado como finalizado y enviado al administrador para revisión.');
    }

    public function actualizarPendienteTrabajador(Request $request, PendienteAvance $pendiente)
    {
        $usuario = Auth::user();

        abort_unless(
            $usuario->rol?->nombre === 'TRABAJADOR' &&
                $usuario->trabajador_id &&
                $usuario->trabajador?->area_id,
            403
        );

        $pendiente->load('reporte.trabajador', 'area');

        $areaPendiente = $pendiente->area_id
            ?? $pendiente->reporte?->trabajador?->area_id;

        abort_unless(
            $areaPendiente === $usuario->trabajador->area_id,
            403
        );

        if (
            $pendiente->estado === 'RESUELTO' ||
            $pendiente->estado_revision === 'POR_REVISAR'
        ) {
            return back()->with(
                'error',
                'El pendiente ya fue enviado para revisión y no puede editarse.'
            );
        }

        $data = $request->validateWithBag('editarPendiente', [
            'descripcion' => ['required', 'string', 'max:500'],
            'detalle' => ['nullable', 'string', 'max:2000'],
            'ruta_archivos' => ['nullable', 'string', 'max:500'],
        ], [
            'descripcion.required' => 'Describe la actividad pendiente.',
        ]);

        $pendiente->update([
            'descripcion' => $data['descripcion'],
            'detalle' => $data['detalle'] ?? null,
            'ruta_archivos' => $data['ruta_archivos'] ?? null,
            'estado' => 'PENDIENTE',
            'estado_revision' => null,
            'observacion_revision' => null,
            'revisado_por' => null,
            'fecha_revision' => null,
            'resuelto_por' => null,
            'fecha_resolucion' => null,
        ]);

        return redirect()
            ->route('reportesAvance.index', ['pestana' => 'pendientes'])
            ->with('success', 'Pendiente actualizado correctamente. Ya puedes marcarlo como finalizado.');
    }

    public function revisarPendiente(Request $request, PendienteAvance $pendiente)
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol?->nombre === 'ADMINISTRADOR', 403);

        if ($pendiente->estado_revision !== 'POR_REVISAR') {
            return back()->with('error', 'Este pendiente no está esperando revisión.');
        }

        $data = $request->validateWithBag('revisionPendiente', [
            'decision' => ['required', Rule::in(['APROBADO', 'RECHAZADO', 'OBSERVADO'])],
            'observacion_revision' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['decision'] === 'OBSERVADO' && blank($data['observacion_revision'] ?? null)) {
            return back()->withErrors([
                'observacion_revision' => 'Debes escribir la observación del pendiente.',
            ], 'revisionPendiente')->withInput();
        }

        $aprobado = $data['decision'] === 'APROBADO';

        $pendiente->update([
            'estado' => $aprobado ? 'RESUELTO' : 'PENDIENTE',
            'estado_revision' => $data['decision'],
            'observacion_revision' => $data['decision'] === 'OBSERVADO'
                ? $data['observacion_revision']
                : null,
            'revisado_por' => $usuario->id,
            'fecha_revision' => now(),
            'fecha_resolucion' => $aprobado
                ? $pendiente->fecha_resolucion
                : null,
        ]);

        $mensajes = [
            'APROBADO' => 'Pendiente aprobado como completado.',
            'RECHAZADO' => 'Pendiente marcado como no completado.',
            'OBSERVADO' => 'Observación registrada en el pendiente.',
        ];

        return redirect()
            ->route('reportesAvance.index', ['pestana' => 'pendientes'])
            ->with('success', $mensajes[$data['decision']]);
    }

    private function validarContenidoNuevo(Request $request, string $errorBag): array
    {
        return $request->validateWithBag($errorBag, [
            'actividades' => ['required', 'array', 'min:1'],
            'actividades.*.descripcion' => ['required', 'string', 'max:500'],
            'actividades.*.archivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:5120',
            ],
            'pendientes' => ['nullable', 'array'],
            'pendientes.*.descripcion' => ['required', 'string', 'max:500'],
            'pendientes.*.detalle' => ['nullable', 'string', 'max:2000'],
            'pendientes.*.ruta_archivos' => ['nullable', 'string', 'max:500'],
        ], [
            'actividades.required' => 'Debes registrar al menos una actividad realizada.',
            'actividades.min' => 'Debes registrar al menos una actividad realizada.',
            'actividades.*.descripcion.required' => 'Cada actividad debe tener una descripción.',
            'actividades.*.archivo.max' => 'Cada archivo debe pesar como máximo 5 MB.',
            'pendientes.*.descripcion.required' => 'Cada pendiente debe tener una descripción.',
        ]);
    }

    private function validarReporte(Request $request, ?string $errorBag = null): array
    {
        $reglas = [
            'actividades' => ['nullable', 'array'],
            'actividades.*.id' => ['nullable', 'integer', 'exists:actividades_reporte,id'],
            'actividades.*.descripcion' => ['required', 'string', 'max:500'],
            'actividades.*.archivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:5120',
            ],
            'pendientes' => ['nullable', 'array'],
            'pendientes.*.id' => ['nullable', 'integer', 'exists:pendientes_avance,id'],
            'pendientes.*.descripcion' => ['required', 'string', 'max:500'],
            'pendientes.*.detalle' => ['nullable', 'string', 'max:2000'],
            'pendientes.*.ruta_archivos' => ['nullable', 'string', 'max:500'],
        ];

        $mensajes = [
            'actividades.*.descripcion.required' => 'Cada actividad debe tener una descripción.',
            'actividades.*.archivo.max' => 'Cada archivo debe pesar como máximo 5 MB.',
            'pendientes.*.descripcion.required' => 'Cada pendiente debe tener una descripción.',
        ];

        return $errorBag
            ? $request->validateWithBag($errorBag, $reglas, $mensajes)
            : $request->validate($reglas, $mensajes);
    }

    private function crearReporte(
        Request $request,
        array $data,
        Trabajador $trabajador,
        User $usuario,
        string $origenPendientes
    ): ReporteAvance {
        return DB::transaction(function () use (
            $request,
            $data,
            $trabajador,
            $usuario,
            $origenPendientes
        ) {
            $reporte = ReporteAvance::create([
                'trabajador_id' => $trabajador->id,
                'fecha_presentacion' => today(),
                'periodo_inicio' => null,
                'periodo_fin' => null,
                'descripcion' => null,
                'porcentaje_avance' => null,
                'archivo' => null,
                'estado' => 'PENDIENTE',
            ]);

            foreach ($data['actividades'] ?? [] as $indice => $actividad) {
                $archivo = $request->file("actividades.{$indice}.archivo");

                $reporte->actividades()->create([
                    'descripcion' => $actividad['descripcion'],
                    'archivo' => $archivo
                        ? $archivo->store('reportes-avance/actividades', 'public')
                        : null,
                ]);
            }

            foreach ($data['pendientes'] ?? [] as $pendiente) {
                if (blank($pendiente['descripcion'] ?? null)) {
                    continue;
                }

                $reporte->pendientes()->create([
                    'area_id' => $trabajador->area_id,
                    'creado_por' => $usuario->id,
                    'origen' => $origenPendientes,
                    'descripcion' => $pendiente['descripcion'],
                    'detalle' => $pendiente['detalle'] ?? null,
                    'ruta_archivos' => $pendiente['ruta_archivos'] ?? null,
                    'estado' => 'PENDIENTE',
                ]);
            }

            return $reporte;
        });
    }

    private function sincronizarActividades(
        Request $request,
        ReporteAvance $reporte,
        array $actividades
    ): void {
        $ids = collect($actividades)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        $reporte
            ->actividades()
            ->whereNotIn('id', $ids)
            ->get()
            ->each(function ($actividad) {
                if (
                    $actividad->archivo &&
                    Storage::disk('public')->exists($actividad->archivo)
                ) {
                    Storage::disk('public')->delete($actividad->archivo);
                }

                $actividad->delete();
            });

        foreach ($actividades as $indice => $data) {
            $actividad = isset($data['id'])
                ? $reporte->actividades()->findOrFail($data['id'])
                : new ActividadReporte([
                    'reporte_avance_id' => $reporte->id,
                ]);

            $ruta = $actividad->archivo;
            $archivoNuevo = $request->file("actividades.{$indice}.archivo");

            if ($archivoNuevo) {
                if ($ruta && Storage::disk('public')->exists($ruta)) {
                    Storage::disk('public')->delete($ruta);
                }

                $ruta = $archivoNuevo->store(
                    'reportes-avance/actividades',
                    'public'
                );
            }

            $actividad->descripcion = $data['descripcion'];
            $actividad->archivo = $ruta;
            $actividad->reporte_avance_id = $reporte->id;
            $actividad->save();
        }
    }

    private function sincronizarPendientes(
        ReporteAvance $reporte,
        array $pendientes
    ): void {
        $ids = collect($pendientes)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        $reporte
            ->pendientes()
            ->where('estado', 'PENDIENTE')
            ->whereNotIn('id', $ids)
            ->delete();

        foreach ($pendientes as $data) {
            if (blank($data['descripcion'] ?? null)) {
                continue;
            }

            $pendiente = isset($data['id'])
                ? $reporte
                    ->pendientes()
                    ->where('estado', 'PENDIENTE')
                    ->findOrFail($data['id'])
                : new PendienteAvance([
                    'reporte_avance_id' => $reporte->id,
                    'area_id' => $reporte->trabajador->area_id,
                    'creado_por' => Auth::id(),
                    'origen' => 'TRABAJADOR',
                    'estado' => 'PENDIENTE',
                ]);

            $pendiente->descripcion = $data['descripcion'];
            $pendiente->detalle = $data['detalle'] ?? null;
            $pendiente->ruta_archivos = $data['ruta_archivos'] ?? null;
            $pendiente->reporte_avance_id = $reporte->id;
            $pendiente->area_id = $reporte->trabajador->area_id;
            $pendiente->save();
        }
    }

    public function verArchivoActividad(ActividadReporte $actividad)
    {
        $this->autorizarArchivoActividad($actividad);

        if (! $actividad->archivo || ! Storage::disk('public')->exists($actividad->archivo)) {
            abort(404, 'El archivo no se encuentra disponible.');
        }

        $ruta = Storage::disk('public')->path($actividad->archivo);
        $mime = Storage::disk('public')->mimeType($actividad->archivo) ?: 'application/octet-stream';

        return response()->file($ruta, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($actividad->archivo).'"',
        ]);
    }

    public function descargarArchivoActividad(ActividadReporte $actividad)
    {
        $this->autorizarArchivoActividad($actividad);

        if (! $actividad->archivo || ! Storage::disk('public')->exists($actividad->archivo)) {
            abort(404, 'El archivo no se encuentra disponible.');
        }

        return Storage::disk('public')->download(
            $actividad->archivo,
            basename($actividad->archivo)
        );
    }

    private function autorizarArchivoActividad(ActividadReporte $actividad): void
    {
        $usuario = Auth::user();

        $actividad->loadMissing('reporte.trabajador');

        $esAdministrador = $usuario->rol?->nombre === 'ADMINISTRADOR';

        $esGerenteDelArea = $usuario->rol?->nombre === 'GERENTE'
            && $usuario->trabajador?->area_id
            && $actividad->reporte?->trabajador?->area_id === $usuario->trabajador->area_id;

        $esEncargadoDelArea = $usuario->rol?->nombre === 'TRABAJADOR'
            && $usuario->trabajador?->esEncargadoDeSuArea()
            && $actividad->reporte?->trabajador?->area_id === $usuario->trabajador->area_id;

        $esPropietario = $usuario->trabajador_id &&
            $actividad->reporte?->trabajador_id === $usuario->trabajador_id;

        abort_unless(
            $esAdministrador || $esGerenteDelArea || $esEncargadoDelArea || $esPropietario,
            403
        );
    }

    private function autorizarRevisor(): User
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        abort_unless(
            in_array($usuario->rol?->nombre, ['ADMINISTRADOR', 'GERENTE'], true),
            403
        );

        return $usuario;
    }

    private function parametrosRetornoRevision(Request $request): array
    {
        $claves = ['pestana', 'buscar', 'estado', 'area_id', 'fecha_desde', 'fecha_hasta', 'historial', 'vista'];
        $retorno = $request->input('retorno', []);

        if (! is_array($retorno)) {
            return [];
        }

        $parametros = [];

        foreach ($claves as $clave) {
            $valor = $retorno[$clave] ?? null;
            if (is_scalar($valor) && (string) $valor !== '') {
                $parametros[$clave] = (string) $valor;
            }
        }

        return $parametros;
    }

    private function autorizarSupervisorDelReporte(ReporteAvance $reporte): void
    {
        $usuario = Auth::user();

        if ($usuario->rol?->nombre === 'ADMINISTRADOR') {
            return;
        }

        $reporte->loadMissing('trabajador');

        abort_unless(
            $usuario->rol?->nombre === 'GERENTE'
                && $usuario->trabajador?->area_id
                && $reporte->trabajador?->area_id === $usuario->trabajador->area_id
                && $reporte->trabajador_id !== $usuario->trabajador_id,
            403
        );
    }
}
