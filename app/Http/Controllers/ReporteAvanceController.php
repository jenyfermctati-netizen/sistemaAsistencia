<?php

namespace App\Http\Controllers;

use App\Models\ReporteAvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        /*
         * Un trabajador ve solo sus reportes.
         * Administrador y gerente pueden ver todos.
         */
        if ($usuario->rol?->nombre === 'TRABAJADOR') {
            $query->where(
                'trabajador_id',
                $usuario->trabajador_id
            );
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhereHas('trabajador', function ($trabajadorQuery) use ($buscar) {
                        $trabajadorQuery
                            ->where('nombres', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%")
                            ->orWhere('dni', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate(
                'fecha_presentacion',
                '>=',
                $request->fecha_desde
            );
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate(
                'fecha_presentacion',
                '<=',
                $request->fecha_hasta
            );
        }

        $reportes = $query
            ->orderByDesc('fecha_presentacion')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view(
            'reportesAvance.index',
            compact('reportes')
        );
    }


    public function store(Request $request)
    {
        $usuario = Auth::user();

        if (!$usuario->trabajador_id) {
            return back()->with(
                'error',
                'Este usuario no está asociado a un trabajador.'
            );
        }

        $data = $request->validate([
            'periodo_inicio' => [
                'required',
                'date',
            ],

            'periodo_fin' => [
                'required',
                'date',
                'after_or_equal:periodo_inicio',
            ],

            'descripcion' => [
                'required',
                'string',
            ],

            'porcentaje_avance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'archivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $rutaArchivo = null;

        if ($request->hasFile('archivo')) {
            $rutaArchivo = $request
                ->file('archivo')
                ->store(
                    'reportes-avance',
                    'public'
                );
        }

        ReporteAvance::create([
            'trabajador_id' => $usuario->trabajador_id,

            'periodo_inicio' => $data['periodo_inicio'],

            'periodo_fin' => $data['periodo_fin'],

            'fecha_presentacion' => now()->toDateString(),

            'descripcion' => $data['descripcion'],

            'porcentaje_avance' =>
                $data['porcentaje_avance'] ?? null,

            'archivo' => $rutaArchivo,

            'estado' => 'PENDIENTE',
        ]);

        return redirect()
            ->route('reportesAvance.index')
            ->with(
                'success',
                'Reporte de avance registrado correctamente.'
            );
    }


    public function update(
        Request $request,
        ReporteAvance $reporte
    ) {
        $usuario = Auth::user();

        /*
         * Solo el propietario puede editar.
         */
        if (
            $reporte->trabajador_id
            !== $usuario->trabajador_id
        ) {
            abort(403);
        }

        /*
         * Un reporte revisado ya no se modifica.
         */
        if ($reporte->estado === 'REVISADO') {
            return back()->with(
                'error',
                'Un reporte revisado ya no puede modificarse.'
            );
        }

        $data = $request->validate([
            'periodo_inicio' => [
                'required',
                'date',
            ],

            'periodo_fin' => [
                'required',
                'date',
                'after_or_equal:periodo_inicio',
            ],

            'descripcion' => [
                'required',
                'string',
            ],

            'porcentaje_avance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'archivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $rutaArchivo = $reporte->archivo;

        if ($request->hasFile('archivo')) {

            if (
                $rutaArchivo
                && Storage::disk('public')->exists($rutaArchivo)
            ) {
                Storage::disk('public')->delete($rutaArchivo);
            }

            $rutaArchivo = $request
                ->file('archivo')
                ->store(
                    'reportes-avance',
                    'public'
                );
        }

        $reporte->update([
            'periodo_inicio' => $data['periodo_inicio'],

            'periodo_fin' => $data['periodo_fin'],

            'descripcion' => $data['descripcion'],

            'porcentaje_avance' =>
                $data['porcentaje_avance'] ?? null,

            'archivo' => $rutaArchivo,

            /*
             * Si estaba observado y se corrige,
             * vuelve a pendiente para revisión.
             */
            'estado' =>
                $reporte->estado === 'OBSERVADO'
                    ? 'PENDIENTE'
                    : $reporte->estado,

            'revisado_por' =>
                $reporte->estado === 'OBSERVADO'
                    ? null
                    : $reporte->revisado_por,

            'comentario_revision' =>
                $reporte->estado === 'OBSERVADO'
                    ? null
                    : $reporte->comentario_revision,

            'fecha_revision' =>
                $reporte->estado === 'OBSERVADO'
                    ? null
                    : $reporte->fecha_revision,
        ]);

        return redirect()
            ->route('reportesAvance.index')
            ->with(
                'success',
                'Reporte actualizado correctamente.'
            );
    }


    public function revisar(
        Request $request,
        ReporteAvance $reporte
    ) {
        $usuario = Auth::user();

        if (
            !in_array(
                $usuario->rol?->nombre,
                ['ADMINISTRADOR', 'GERENTE']
            )
        ) {
            abort(403);
        }

        $data = $request->validate([
            'estado' => [
                'required',
                Rule::in([
                    'REVISADO',
                    'OBSERVADO',
                ]),
            ],

            'comentario_revision' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if (
            $data['estado'] === 'OBSERVADO'
            && empty($data['comentario_revision'])
        ) {
            return back()
                ->withErrors([
                    'comentario_revision' =>
                        'Debes indicar la observación del reporte.',
                ]);
        }

        $reporte->update([
            'estado' => $data['estado'],

            'revisado_por' => $usuario->id,

            'comentario_revision' =>
                $data['comentario_revision'] ?? null,

            'fecha_revision' => now(),
        ]);

        return redirect()
            ->route('reportesAvance.index')
            ->with(
                'success',
                'Reporte revisado correctamente.'
            );
    }
}