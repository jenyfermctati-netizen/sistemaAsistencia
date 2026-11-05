<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Solicitud;
use App\Services\ReporteTablaExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $rol = strtoupper(trim((string) $usuario->rol?->nombre));
        $puedeGestionar = in_array($rol, ['ADMINISTRADOR', 'GERENTE'], true);
        $puedeSolicitar = (bool) $usuario->trabajador_id;
        $seccion = $puedeGestionar
            ? ($request->query('seccion') === 'mis' ? 'mis' : 'bandeja')
            : 'mis';
        $estadoFiltro = $request->input('estado', $puedeGestionar && $seccion === 'bandeja' ? 'PENDIENTE' : '');

        $query = Solicitud::with(['trabajador.area', 'asistencia', 'revisadoPor']);

        if ($rol === 'GERENTE') {
            $areaId = $usuario->trabajador?->area_id;
            if ($seccion === 'mis') {
                $query->where('trabajador_id', $usuario->trabajador_id ?? 0);
            } elseif ($areaId) {
                $query->whereHas('trabajador', fn ($trabajador) => $trabajador->where('area_id', $areaId))
                    ->where('trabajador_id', '!=', $usuario->trabajador_id ?? 0);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($rol === 'ADMINISTRADOR') {
            if ($seccion === 'mis') {
                $query->where('trabajador_id', $usuario->trabajador_id ?? 0);
            } elseif ($usuario->trabajador_id) {
                $query->where('trabajador_id', '!=', $usuario->trabajador_id);
            }
        } elseif ($rol !== 'ADMINISTRADOR') {
            if (!$usuario->trabajador_id)
                $query->whereRaw('1 = 0');
            else
                $query->where('trabajador_id', $usuario->trabajador_id);
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q
                    ->where('motivo', 'like', "%{$buscar}%")
                    ->orWhereHas('trabajador', function ($trabajador) use ($buscar) {
                        $trabajador
                            ->where('dni', 'like', "%{$buscar}%")
                            ->orWhere('nombres', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($request->filled('tipo'))
            $query->where('tipo', $request->tipo);
        if ($estadoFiltro !== '')
            $query->where('estado', $estadoFiltro);

        $fechas = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $desde = $fechas['fecha_desde'] ?? $fechas['fecha'] ?? null;
        $hasta = $fechas['fecha_hasta'] ?? $fechas['fecha'] ?? null;

        if ($hasta) {
            $query->whereDate('fecha_inicio', '<=', $hasta);
        }
        if ($desde) {
            $query->where(function ($rango) use ($desde) {
                $rango->whereDate('fecha_fin', '>=', $desde)
                    ->orWhere(fn ($dia) => $dia->whereNull('fecha_fin')->whereDate('fecha_inicio', '>=', $desde));
            });
        }

        $query->orderByRaw("CASE WHEN estado='PENDIENTE' THEN 1 WHEN estado='APROBADA' THEN 2 WHEN estado='RECHAZADA' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $formato = $request->query('descargar');
        if (in_array($formato, ['xlsx', 'pdf'], true)) {
            $filas = $query->withOnly('trabajador.area')->lazy()->map(fn (Solicitud $solicitud) => [
                $solicitud->fecha_inicio?->format('d/m/Y'),
                $solicitud->fecha_fin?->format('d/m/Y'),
                $solicitud->trabajador?->area?->nombre,
                trim($solicitud->trabajador?->nombres.' '.$solicitud->trabajador?->apellidos),
                $solicitud->tipo,
                $solicitud->estado,
                $solicitud->motivo,
            ]);

            return app(ReporteTablaExportService::class)->descargar(
                $formato, 'solicitudes', 'Reporte de solicitudes',
                ['Desde', 'Hasta', 'Área', 'Trabajador', 'Tipo', 'Estado', 'Motivo'],
                ['A' => 14, 'B' => 14, 'C' => 25, 'D' => 28, 'E' => 22, 'F' => 16, 'G' => 60],
                $filas, $desde, $hasta,
            );
        }

        $solicitudes = $query->paginate(15)->withQueryString();

        $misSolicitudesCount = $puedeGestionar && $usuario->trabajador_id
            ? Solicitud::where('trabajador_id', $usuario->trabajador_id)->count()
            : 0;
        $bandejaCount = 0;
        if ($puedeGestionar) {
            $pendientes = Solicitud::where('estado', 'PENDIENTE');
            if ($rol === 'GERENTE') {
                $areaId = $usuario->trabajador?->area_id;
                if ($areaId) {
                    $pendientes->whereHas('trabajador', fn ($trabajador) => $trabajador->where('area_id', $areaId));
                } else {
                    $pendientes->whereRaw('1 = 0');
                }
            }
            if ($usuario->trabajador_id) $pendientes->where('trabajador_id', '!=', $usuario->trabajador_id);
            $bandejaCount = $pendientes->count();
        }

        return view('trabajadores.solicitudes', compact('solicitudes', 'puedeGestionar', 'puedeSolicitar', 'seccion', 'misSolicitudesCount', 'bandejaCount', 'estadoFiltro'));
    }

    public function store(Request $request)
    {
        $usuario = Auth::user();
        if (!$usuario->trabajador_id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu cuenta no está asociada a un trabajador.'], 422);
            }
            return back()->with('error', 'Tu cuenta no está asociada a un trabajador.');
        }

        $data = $this->validarSolicitud($request);
        $this->validarHoras($data);

        $archivo = $request->hasFile('archivo')
            ? $request->file('archivo')->store('solicitudes', 'public')
            : null;

        $asistenciaId = $this->obtenerAsistenciaRelacionada(
            $usuario->trabajador_id,
            $data['fecha_inicio'],
            $data['fecha_fin'] ?? null
        );

        Solicitud::create([
            'trabajador_id' => $usuario->trabajador_id,
            'asistencia_id' => $asistenciaId,
            'tipo' => $data['tipo'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'motivo' => $data['motivo'],
            'archivo' => $archivo,
            'estado' => 'PENDIENTE',
            'revisado_por' => null,
            'comentario_revision' => null,
            'fecha_revision' => null,
        ]);

        return $this->respuesta($request, 'Solicitud registrada correctamente.', 201);
    }

    public function update(Request $request, Solicitud $solicitud)
    {
        $this->verificarPropietario($solicitud);

        if ($solicitud->estado !== 'PENDIENTE') {
            return $this->error($request, 'Solo puedes editar solicitudes pendientes.');
        }

        $data = $this->validarSolicitud($request);
        $this->validarHoras($data);

        $archivo = $solicitud->archivo;

        if ($request->hasFile('archivo')) {
            if ($archivo && Storage::disk('public')->exists($archivo)) {
                Storage::disk('public')->delete($archivo);
            }
            $archivo = $request->file('archivo')->store('solicitudes', 'public');
        }

        $asistenciaId = $this->obtenerAsistenciaRelacionada(
            $solicitud->trabajador_id,
            $data['fecha_inicio'],
            $data['fecha_fin'] ?? null
        );

        $solicitud->update([
            'tipo' => $data['tipo'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'hora_inicio' => $data['hora_inicio'] ?? $solicitud->hora_inicio,
            'hora_fin' => $data['hora_fin'] ?? $solicitud->hora_fin,
            'motivo' => $data['motivo'],
            'archivo' => $archivo,
            'asistencia_id' => $asistenciaId,
        ]);

        return $this->respuesta($request, 'Solicitud actualizada correctamente.');
    }

    public function cancelar(Request $request, Solicitud $solicitud)
    {
        $this->verificarPropietario($solicitud);

        if ($solicitud->estado !== 'PENDIENTE') {
            return $this->error($request, 'Solo puedes cancelar solicitudes pendientes.');
        }

        $solicitud->update(['estado' => 'CANCELADA']);

        return $this->respuesta($request, 'Solicitud cancelada correctamente.');
    }

    public function revisar(Request $request, Solicitud $solicitud)
    {
        $this->verificarSupervisor($solicitud);

        if ($solicitud->estado !== 'PENDIENTE') {
            return $this->error($request, 'Esta solicitud ya fue revisada o cancelada.');
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['APROBADA', 'RECHAZADA'])],
            'comentario_revision' => ['nullable', 'string', 'max:500'],
        ], [
            'decision.required' => 'Selecciona una decisión.',
            'decision.in' => 'La decisión seleccionada no es válida.',
            'comentario_revision.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        if ($data['decision'] === 'RECHAZADA' && blank($data['comentario_revision'] ?? null)) {
            throw ValidationException::withMessages([
                'comentario_revision' => 'Debes indicar el motivo del rechazo.',
            ]);
        }

        DB::transaction(function () use ($solicitud, $data) {
            $solicitud->update([
                'estado' => $data['decision'],
                'revisado_por' => Auth::id(),
                'comentario_revision' => $data['decision'] === 'RECHAZADA'
                    ? trim($data['comentario_revision'])
                    : null,
                'fecha_revision' => now(),
            ]);

            if ($data['decision'] === 'APROBADA') {
                $this->aplicarJustificacionEnAsistencias($solicitud);
            }
        });

        $mensaje = $data['decision'] === 'APROBADA'
            ? 'Solicitud aprobada correctamente.'
            : 'Solicitud rechazada correctamente.';

        return $this->respuesta($request, $mensaje);
    }

    public function verArchivo(Solicitud $solicitud)
    {
        $this->verificarAccesoArchivo($solicitud);
        $this->verificarExistenciaArchivo($solicitud);

        $ruta = Storage::disk('public')->path($solicitud->archivo);
        $mime = Storage::disk('public')->mimeType($solicitud->archivo) ?: 'application/octet-stream';

        return response()->file($ruta, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($solicitud->archivo) . '"',
        ]);
    }

    public function descargarArchivo(Solicitud $solicitud)
    {
        $this->verificarAccesoArchivo($solicitud);
        $this->verificarExistenciaArchivo($solicitud);

        return Storage::disk('public')->download(
            $solicitud->archivo,
            basename($solicitud->archivo)
        );
    }

    private function validarSolicitud(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', Rule::in(['TARDANZA', 'FALTA', 'PERMISO', 'OMISION_MARCACION', 'OTRO'])],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio', 'prohibited_unless:tipo,FALTA,PERMISO'],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio'],
            'motivo' => ['required', 'string', 'max:2000'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'tipo.required' => 'Selecciona el tipo de solicitud.',
            'fecha_inicio.required' => 'Selecciona la fecha de inicio.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'motivo.required' => 'Debes indicar el motivo de la solicitud.',
            'archivo.mimes' => 'El sustento debe ser PDF, JPG, JPEG o PNG.',
            'archivo.max' => 'El archivo no puede superar los 5 MB.',
            'hora_inicio.required_with' => 'Debes indicar también la hora de inicio.',
            'hora_fin.required_with' => 'Debes indicar también la hora de fin.',
        ]);
    }

    private function validarHoras(array $data): void
    {
        if (!empty($data['hora_inicio']) && !empty($data['hora_fin']) && $data['hora_fin'] < $data['hora_inicio']) {
            throw ValidationException::withMessages([
                'hora_fin' => 'La hora de fin no puede ser anterior a la hora de inicio.',
            ]);
        }
    }

    private function obtenerAsistenciaRelacionada(int $trabajadorId, string $fechaInicio, ?string $fechaFin): ?int
    {
        if ($fechaFin && $fechaFin !== $fechaInicio)
            return null;

        return Asistencia::where('trabajador_id', $trabajadorId)
            ->whereDate('fecha', $fechaInicio)
            ->value('id');
    }

    private function aplicarJustificacionEnAsistencias(Solicitud $solicitud): void
    {
        if ($solicitud->tipo === 'PERMISO') {
            $asistencias = Asistencia::where('trabajador_id', $solicitud->trabajador_id)
                ->whereNotIn('estado', ['FERIADO', 'VACACIONES', 'CAMPO'])
                ->whereDate('fecha', '>=', $solicitud->fecha_inicio)
                ->whereDate('fecha', '<=', $solicitud->fecha_fin ?? $solicitud->fecha_inicio)
                ->get();

            foreach ($asistencias as $asistencia) {
                $detalle = 'Solicitud aprobada (PERMISO)';
                if ($solicitud->motivo) {
                    $detalle .= ': ' . mb_substr($solicitud->motivo, 0, 350);
                }

                $asistencia->update([
                    'estado' => 'PERMISO',
                    'observacion' => $detalle,
                ]);
            }

            return;
        }

        $estadoOrigen = match ($solicitud->tipo) {
            'FALTA' => 'FALTA',
            'TARDANZA' => 'TARDANZA',
            default => null,
        };

        if (! $estadoOrigen) {
            return;
        }

        $estadoJustificado = $solicitud->tipo === 'FALTA' ? 'FJ' : 'TJ';
        $asistencias = Asistencia::where('trabajador_id', $solicitud->trabajador_id)
            ->where(function ($query) use ($estadoOrigen, $solicitud) {
                $query->where('estado', $estadoOrigen);

                if ($solicitud->tipo === 'FALTA') {
                    $query->orWhere(function ($sinMarcaciones) {
                        $sinMarcaciones->where('estado', 'SIN_REGISTRO')
                            ->whereNull('marcacion_entrada_id')
                            ->whereNull('marcacion_salida_refrigerio_id')
                            ->whereNull('marcacion_retorno_refrigerio_id')
                            ->whereNull('marcacion_salida_id');
                    });
                }
            })
            ->whereDate('fecha', '>=', $solicitud->fecha_inicio)
            ->whereDate('fecha', '<=', $solicitud->fecha_fin ?? $solicitud->fecha_inicio)
            ->get();

        foreach ($asistencias as $asistencia) {
            $detalle = "Solicitud aprobada ({$solicitud->tipo})";
            if ($solicitud->motivo) {
                $detalle .= ': ' . mb_substr($solicitud->motivo, 0, 350);
            }

            $asistencia->update([
                'estado' => $estadoJustificado,
                'observacion' => $detalle,
            ]);
        }
    }

    private function verificarPropietario(Solicitud $solicitud): void
    {
        $usuario = Auth::user();
        abort_unless($usuario && $usuario->trabajador_id === $solicitud->trabajador_id, 403);
    }

    private function verificarSupervisor(Solicitud $solicitud): void
    {
        $usuario = Auth::user();

        abort_if(
            $usuario?->trabajador_id === $solicitud->trabajador_id,
            403,
            'No puedes revisar tu propia solicitud.'
        );

        if ($usuario?->rol?->nombre === 'ADMINISTRADOR') {
            return;
        }

        $solicitud->loadMissing('trabajador');

        abort_unless(
            $usuario?->rol?->nombre === 'GERENTE'
                && $usuario->trabajador?->area_id
                && $solicitud->trabajador?->area_id === $usuario->trabajador->area_id
                && $solicitud->trabajador_id !== $usuario->trabajador_id,
            403
        );
    }

    private function verificarAccesoArchivo(Solicitud $solicitud): void
    {
        $usuario = Auth::user();
        $solicitud->loadMissing('trabajador');

        $esGerenteDelArea = $usuario?->rol?->nombre === 'GERENTE'
            && $usuario->trabajador?->area_id
            && $solicitud->trabajador?->area_id === $usuario->trabajador->area_id;

        abort_unless(
            $usuario && (
                $usuario->rol?->nombre === 'ADMINISTRADOR' ||
                $esGerenteDelArea ||
                $usuario->trabajador_id === $solicitud->trabajador_id
            ),
            403
        );
    }

    private function verificarExistenciaArchivo(Solicitud $solicitud): void
    {
        abort_unless(
            $solicitud->archivo &&
                Storage::disk('public')->exists($solicitud->archivo),
            404
        );
    }

    private function respuesta(Request $request, string $mensaje, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensaje,
                'refresh_url' => route('solicitudes.index'),
            ], $status);
        }

        return redirect()
            ->route('solicitudes.index')
            ->with('success', $mensaje);
    }

    private function error(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje], 422);
        }

        return back()->with('error', $mensaje);
    }
}
