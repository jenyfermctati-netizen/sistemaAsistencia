<?php

namespace App\Http\Controllers;

use App\Models\ActividadReporte;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReporteAvanceController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();

        $query = ReporteAvance::with([
            'trabajador.area',
            'revisor',
            'actividades',
            'pendientes.resueltoPor',
        ]);

        if ($usuario->rol?->nombre === 'TRABAJADOR') {
            $query->where('trabajador_id', $usuario->trabajador_id);
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->whereHas('trabajador', function ($trabajador) use ($buscar) {
                    $trabajador
                        ->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('dni', 'like', "%{$buscar}%");
                })->orWhereHas('actividades', function ($actividad) use ($buscar) {
                    $actividad->where('descripcion', 'like', "%{$buscar}%");
                })->orWhereHas('pendientes', function ($pendiente) use ($buscar) {
                    $pendiente
                        ->where('descripcion', 'like', "%{$buscar}%")
                        ->orWhere('detalle', 'like', "%{$buscar}%");
                });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_presentacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_presentacion', '<=', $request->fecha_hasta);
        }

        $reportes = $query
            ->orderByDesc('fecha_presentacion')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $pendientesArea = collect();

        if ($usuario->trabajador?->area_id) {
            $pendientesArea = PendienteAvance::with([
                'reporte.trabajador.area',
                'resueltoPor',
            ])
                ->where('estado', 'PENDIENTE')
                ->whereHas('reporte.trabajador', function ($q) use ($usuario) {
                    $q->where('area_id', $usuario->trabajador->area_id);
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $reporteHoy = null;

        if ($usuario->trabajador_id) {
            $reporteHoy = ReporteAvance::where('trabajador_id', $usuario->trabajador_id)
                ->whereDate('fecha_presentacion', today())
                ->first();
        }

        return view('reportesAvance.index', compact('reportes', 'pendientesArea', 'reporteHoy'));
    }

    public function store(Request $request)
    {
        $usuario = Auth::user();

        if (!$usuario->trabajador_id) {
            return back()->with('error', 'Este usuario no está asociado a un trabajador.');
        }

        if (ReporteAvance::where('trabajador_id', $usuario->trabajador_id)
                ->whereDate('fecha_presentacion', today())
                ->exists()) {
            return back()->with('error', 'Ya registraste tu reporte de avance de hoy.');
        }

        $data = $this->validarReporte($request);

        DB::transaction(function () use ($request, $data, $usuario) {
            $reporte = ReporteAvance::create([
                'trabajador_id' => $usuario->trabajador_id,
                'fecha_presentacion' => today(),
                'periodo_inicio' => null,
                'periodo_fin' => null,
                'descripcion' => null,
                'porcentaje_avance' => null,
                'archivo' => null,
                'estado' => 'PENDIENTE',
            ]);

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
                if (blank($pendiente['descripcion'] ?? null)) {
                    continue;
                }

                $reporte->pendientes()->create([
                    'descripcion' => $pendiente['descripcion'],
                    'detalle' => $pendiente['detalle'] ?? null,
                    'ruta_archivos' => $pendiente['ruta_archivos'] ?? null,
                    'estado' => 'PENDIENTE',
                ]);
            }
        });

        return redirect()
            ->route('reportesAvance.index')
            ->with('success', 'Reporte diario registrado correctamente.');
    }

    public function update(Request $request, ReporteAvance $reporte)
    {
        $usuario = Auth::user();

        if ($reporte->trabajador_id !== $usuario->trabajador_id) {
            abort(403);
        }

        if ($reporte->estado === 'REVISADO') {
            return back()->with('error', 'Un reporte revisado ya no puede modificarse.');
        }

        $data = $this->validarReporte($request, true);

        DB::transaction(function () use ($request, $data, $reporte) {
            $this->sincronizarActividades($request, $reporte, $data['actividades']);
            $this->sincronizarPendientes($reporte, $data['pendientes'] ?? []);

            $fueObservado = $reporte->estado === 'OBSERVADO';

            $reporte->update([
                'estado' => $fueObservado ? 'PENDIENTE' : $reporte->estado,
                'revisado_por' => $fueObservado ? null : $reporte->revisado_por,
                'comentario_revision' => $fueObservado ? null : $reporte->comentario_revision,
                'fecha_revision' => $fueObservado ? null : $reporte->fecha_revision,
            ]);
        });

        return redirect()
            ->route('reportesAvance.index')
            ->with('success', 'Reporte diario actualizado correctamente.');
    }

    public function revisar(Request $request, ReporteAvance $reporte)
    {
        $usuario = Auth::user();

        abort_unless(
            in_array($usuario->rol?->nombre, ['ADMINISTRADOR', 'GERENTE'], true),
            403
        );

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
            ->route('reportesAvance.index')
            ->with('success', 'Reporte revisado correctamente.');
    }

    public function resolverPendiente(PendienteAvance $pendiente)
    {
        $usuario = Auth::user();

        if (!$usuario->trabajador_id || !$usuario->trabajador?->area_id) {
            abort(403);
        }

        $pendiente->load('reporte.trabajador');

        if ($pendiente->reporte?->trabajador?->area_id !== $usuario->trabajador->area_id) {
            abort(403);
        }

        if ($pendiente->estado === 'RESUELTO') {
            return back()->with('error', 'Este pendiente ya fue marcado como resuelto.');
        }

        $pendiente->update([
            'estado' => 'RESUELTO',
            'resuelto_por' => $usuario->trabajador_id,
            'fecha_resolucion' => now(),
        ]);

        return redirect()
            ->route('reportesAvance.index')
            ->with('success', 'Pendiente marcado como resuelto.');
    }

    private function validarReporte(Request $request, bool $actualizando = false): array
    {
        return $request->validate([
            'actividades' => ['required', 'array', 'min:1'],
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
            'pendientes.*.descripcion' => ['nullable', 'string', 'max:500'],
            'pendientes.*.detalle' => ['nullable', 'string', 'max:2000'],
            'pendientes.*.ruta_archivos' => ['nullable', 'string', 'max:500'],
        ], [
            'actividades.required' => 'Debes registrar al menos una actividad realizada.',
            'actividades.min' => 'Debes registrar al menos una actividad realizada.',
            'actividades.*.descripcion.required' => 'Cada actividad debe tener una descripción.',
            'actividades.*.archivo.max' => 'Cada archivo debe pesar como máximo 5 MB.',
        ]);
    }

    private function sincronizarActividades(Request $request, ReporteAvance $reporte, array $actividades): void
    {
        $idsRecibidos = collect($actividades)->pluck('id')->filter()->map(fn($id) => (int) $id);

        $reporte
            ->actividades()
            ->whereNotIn('id', $idsRecibidos)
            ->get()
            ->each(function ($actividad) {
                if ($actividad->archivo && Storage::disk('public')->exists($actividad->archivo)) {
                    Storage::disk('public')->delete($actividad->archivo);
                }

                $actividad->delete();
            });

        foreach ($actividades as $indice => $data) {
            $actividad = isset($data['id'])
                ? $reporte->actividades()->findOrFail($data['id'])
                : new ActividadReporte(['reporte_avance_id' => $reporte->id]);

            $ruta = $actividad->archivo;
            $archivoNuevo = $request->file("actividades.{$indice}.archivo");

            if ($archivoNuevo) {
                if ($ruta && Storage::disk('public')->exists($ruta)) {
                    Storage::disk('public')->delete($ruta);
                }

                $ruta = $archivoNuevo->store('reportes-avance/actividades', 'public');
            }

            $actividad->descripcion = $data['descripcion'];
            $actividad->archivo = $ruta;
            $actividad->reporte_avance_id = $reporte->id;
            $actividad->save();
        }
    }

    private function sincronizarPendientes(ReporteAvance $reporte, array $pendientes): void
    {
        $idsRecibidos = collect($pendientes)->pluck('id')->filter()->map(fn($id) => (int) $id);

        $reporte
            ->pendientes()
            ->where('estado', 'PENDIENTE')
            ->whereNotIn('id', $idsRecibidos)
            ->delete();

        foreach ($pendientes as $data) {
            if (blank($data['descripcion'] ?? null)) {
                continue;
            }

            $pendiente = isset($data['id'])
                ? $reporte->pendientes()->where('estado', 'PENDIENTE')->findOrFail($data['id'])
                : new PendienteAvance([
                    'reporte_avance_id' => $reporte->id,
                    'estado' => 'PENDIENTE',
                ]);

            $pendiente->descripcion = $data['descripcion'];
            $pendiente->detalle = $data['detalle'] ?? null;
            $pendiente->ruta_archivos = $data['ruta_archivos'] ?? null;
            $pendiente->reporte_avance_id = $reporte->id;
            $pendiente->save();
        }
    }
}
