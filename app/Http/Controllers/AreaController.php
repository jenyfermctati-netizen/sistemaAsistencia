<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    // Listado y filtros de áreas
    public function index(Request $request)
    {
        $query = Area::query()
            ->withCount('trabajadores');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado === '1');
        }

        $areas = $query
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('areas.index', compact('areas'));
    }

    // Registrar área
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150', 'unique:areas,nombre'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estado' => ['required', 'boolean'],
        ]);

        Area::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $data['estado'],
        ]);

        return redirect()
            ->route('areas.index')
            ->with('success', 'Área registrada correctamente.');
    }

    // Actualizar área
    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('areas', 'nombre')->ignore($area->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estado' => ['required', 'boolean'],
        ]);

        $area->update([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $data['estado'],
        ]);

        return redirect()
            ->route('areas.index')
            ->with('success', 'Área actualizada correctamente.');
    }

    // Cambiar estado activo/inactivo
    public function cambiarEstado(Area $area)
    {
        if ($area->estado) {
            $trabajadoresActivos = $area
                ->trabajadores()
                ->where('estado', 'ACTIVO')
                ->exists();

            if ($trabajadoresActivos) {
                return redirect()
                    ->route('areas.index')
                    ->with('error', 'No puedes desactivar un área que tiene trabajadores activos.');
            }
        }

        $area->estado = !$area->estado;
        $area->save();

        return redirect()
            ->route('areas.index')
            ->with('success', 'Estado del área actualizado correctamente.');
    }
}
