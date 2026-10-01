<?php

namespace App\Http\Controllers;

use App\Models\Marcacion;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MarcacionController extends Controller
{

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
                $q->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('codigo_biometrico', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->fecha_hasta);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('origen')) {
            $query->where('origen', $request->origen);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'ACTIVA') {
                $query->where('anulada', false);
            } elseif ($request->estado === 'ANULADA') {
                $query->where('anulada', true);
            }
        }

        $marcaciones = $query
            ->orderByDesc('fecha_hora')
            ->paginate(15)
            ->withQueryString();

        $trabajadores = Trabajador::where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return view('marcaciones.index', compact(
            'marcaciones',
            'trabajadores'
        ));
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
            'trabajador_id.required' =>
                'Selecciona un trabajador.',
            'fecha_hora.required' =>
                'Ingresa la fecha y hora de la marcación.',
            'tipo.required' =>
                'Selecciona el tipo de marcación.',
            'motivo_manual.required' =>
                'Debes indicar el motivo del registro manual.',
        ]);

        Marcacion::create([
            'trabajador_id' => $data['trabajador_id'],
            'fecha_hora' => $data['fecha_hora'],
            'tipo' => $data['tipo'],
            'origen' => 'MANUAL',
            'registrado_por' => Auth::id(),
            'motivo_manual' => $data['motivo_manual'],
            'anulada' => false,
        ]);

        return redirect()
            ->route('marcaciones.index')
            ->with(
                'success',
                'Marcación manual registrada correctamente.'
            );
    }

    /**
     * Anular marcación.
     */
    public function anular(Request $request, Marcacion $marcacion)
    {
        $this->verificarAdministrador();

        if ($marcacion->anulada) {
            return back()->with(
                'error',
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
            'motivo_anulacion.required' =>
                'Debes indicar el motivo de la anulación.',
        ]);

        $marcacion->update([
            'anulada' => true,
            'anulada_por' => Auth::id(),
            'motivo_anulacion' => $data['motivo_anulacion'],
            'fecha_anulacion' => now(),
        ]);

        return redirect()
            ->route('marcaciones.index')
            ->with(
                'success',
                'Marcación anulada correctamente.'
            );
    }

    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check()
                && Auth::user()->rol?->nombre === 'ADMINISTRADOR',
            403
        );
    }
}