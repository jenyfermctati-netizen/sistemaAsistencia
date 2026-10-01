<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feriado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeriadoController extends Controller
{
    public function index(Request $request)
    {
        $this->verificarAdministrador();

        $query = Feriado::with('area');

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where(
                'tipo',
                $request->tipo
            );
        }

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado === 'ACTIVO'
            );
        }

        if ($request->filled('area_id')) {
            if ($request->area_id === 'GENERAL') {
                $query->whereNull('area_id');
            } else {
                $query->where(
                    'area_id',
                    $request->area_id
                );
            }
        }

        $feriados = $query
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        $areas = Area::orderBy('nombre')
            ->get();

        return view(
            'feriados.index',
            compact(
                'feriados',
                'areas'
            )
        );
    }


    public function store(Request $request)
    {
        $this->verificarAdministrador();

        $data = $request->validate([
            'area_id' => [
                'nullable',
                'exists:areas,id',
            ],

            'fecha' => [
                'required',
                'date',
            ],

            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'tipo' => [
                'required',
                Rule::in([
                    'NACIONAL',
                    'REGIONAL',
                    'LOCAL',
                    'INSTITUCIONAL',
                ]),
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        Feriado::create([
            'area_id' => $data['area_id'] ?? null,
            'fecha' => $data['fecha'],
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => true,
        ]);

        return redirect()
            ->route('feriados.index')
            ->with(
                'success',
                'Feriado registrado correctamente.'
            );
    }


    public function update(
        Request $request,
        Feriado $feriado
    ) {
        $this->verificarAdministrador();

        $data = $request->validate([
            'area_id' => [
                'nullable',
                'exists:areas,id',
            ],

            'fecha' => [
                'required',
                'date',
            ],

            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'tipo' => [
                'required',
                Rule::in([
                    'NACIONAL',
                    'REGIONAL',
                    'LOCAL',
                    'INSTITUCIONAL',
                ]),
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $feriado->update([
            'area_id' => $data['area_id'] ?? null,
            'fecha' => $data['fecha'],
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        return redirect()
            ->route('feriados.index')
            ->with(
                'success',
                'Feriado actualizado correctamente.'
            );
    }


    public function cambiarEstado(Feriado $feriado)
    {
        $this->verificarAdministrador();

        $feriado->update([
            'estado' => !$feriado->estado,
        ]);

        return redirect()
            ->route('feriados.index')
            ->with(
                'success',
                $feriado->estado
                    ? 'Feriado activado correctamente.'
                    : 'Feriado desactivado correctamente.'
            );
    }


    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check()
            &&
            Auth::user()->rol?->nombre
                === 'ADMINISTRADOR',
            403
        );
    }
}