<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TrabajadorController extends Controller
{
    /**
     * Listado y filtros de trabajadores.
     */
    public function index(Request $request)
    {
        $query = Trabajador::with([
            'area',
            'user.rol',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('dni', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('codigo_biometrico', 'like', "%{$buscar}%")
                    ->orWhereHas('user', function ($userQuery) use ($buscar) {
                        $userQuery->where('email', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('tipo_vinculo')) {
            $query->where('tipo_vinculo', $request->tipo_vinculo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $trabajadores = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        $areas = Area::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $roles = Rol::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('trabajadores.index', compact(
            'trabajadores',
            'areas',
            'roles'
        ));
    }

    /**
     * Registrar trabajador y usuario.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'area_id' => [
                'required',
                'exists:areas,id',
            ],
            'codigo_biometrico' => [
                'nullable',
                'string',
                'max:50',
                'unique:trabajadores,codigo_biometrico',
            ],
            'dni' => [
                'required',
                'string',
                'max:15',
                'unique:trabajadores,dni',
            ],
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],
            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],
            'tipo_vinculo' => [
                'required',
                Rule::in(['CONTRATADO', 'LOCADOR']),
            ],
            'cargo' => [
                'nullable',
                'string',
                'max:150',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],
            'fecha_ingreso' => [
                'nullable',
                'date',
            ],
            'fecha_fin' => [
                'nullable',
                'date',
                'after_or_equal:fecha_ingreso',
            ],
            'estado' => [
                'required',
                Rule::in(['ACTIVO', 'INACTIVO']),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'rol_id' => [
                'required',
                'exists:roles,id',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        DB::transaction(function () use ($data) {
            $trabajador = Trabajador::create([
                'area_id' => $data['area_id'],
                'codigo_biometrico' => $data['codigo_biometrico'] ?? null,
                'dni' => $data['dni'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'tipo_vinculo' => $data['tipo_vinculo'],
                'cargo' => $data['cargo'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'fecha_ingreso' => $data['fecha_ingreso'] ?? null,
                'fecha_fin' => $data['fecha_fin'] ?? null,
                'estado' => $data['estado'],
            ]);

            User::create([
                'trabajador_id' => $trabajador->id,
                'rol_id' => $data['rol_id'],
                'name' => trim(
                    "{$trabajador->nombres} {$trabajador->apellidos}"
                ),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'estado' => $trabajador->estado === 'ACTIVO',
            ]);
        });

        return redirect()
            ->route('trabajadores.index')
            ->with(
                'success',
                'Trabajador y usuario registrados correctamente.'
            );
    }

    /**
     * Actualizar trabajador y usuario.
     */
    public function update(Request $request, Trabajador $trabajador)
    {
        $usuario = $trabajador->user;

        $data = $request->validate([
            'area_id' => [
                'required',
                'exists:areas,id',
            ],
            'codigo_biometrico' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(
                    'trabajadores',
                    'codigo_biometrico'
                )->ignore($trabajador->id),
            ],
            'dni' => [
                'required',
                'string',
                'max:15',
                Rule::unique(
                    'trabajadores',
                    'dni'
                )->ignore($trabajador->id),
            ],
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],
            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],
            'tipo_vinculo' => [
                'required',
                Rule::in(['CONTRATADO', 'LOCADOR']),
            ],
            'cargo' => [
                'nullable',
                'string',
                'max:150',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],
            'fecha_ingreso' => [
                'nullable',
                'date',
            ],
            'fecha_fin' => [
                'nullable',
                'date',
                'after_or_equal:fecha_ingreso',
            ],
            'estado' => [
                'required',
                Rule::in(['ACTIVO', 'INACTIVO']),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore($usuario?->id),
            ],
            'rol_id' => [
                'required',
                'exists:roles,id',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        DB::transaction(function () use (
            $trabajador,
            $usuario,
            $data
        ) {
            $trabajador->update([
                'area_id' => $data['area_id'],
                'codigo_biometrico' => $data['codigo_biometrico'] ?? null,
                'dni' => $data['dni'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'tipo_vinculo' => $data['tipo_vinculo'],
                'cargo' => $data['cargo'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'fecha_ingreso' => $data['fecha_ingreso'] ?? null,
                'fecha_fin' => $data['fecha_fin'] ?? null,
                'estado' => $data['estado'],
            ]);

            $usuarioActual = $usuario;

            if (!$usuarioActual) {
                $usuarioActual = new User();
                $usuarioActual->trabajador_id = $trabajador->id;
            }

            $usuarioActual->rol_id = $data['rol_id'];
            $usuarioActual->name = trim(
                "{$trabajador->nombres} {$trabajador->apellidos}"
            );
            $usuarioActual->email = $data['email'];
            $usuarioActual->estado = $trabajador->estado === 'ACTIVO';

            if (!empty($data['password'])) {
                $usuarioActual->password = Hash::make($data['password']);
            }

            if (
                !$usuarioActual->exists &&
                empty($data['password'])
            ) {
                throw new \Exception(
                    'Se necesita una contraseña para crear el usuario.'
                );
            }

            $usuarioActual->save();
        });

        return redirect()
            ->route('trabajadores.index')
            ->with(
                'success',
                'Trabajador actualizado correctamente.'
            );
    }

    /**
     * Activar o desactivar trabajador.
     */
    public function cambiarEstado(Trabajador $trabajador)
    {
        DB::transaction(function () use ($trabajador) {
            $nuevoEstado = $trabajador->estado === 'ACTIVO'
                ? 'INACTIVO'
                : 'ACTIVO';

            $trabajador->update([
                'estado' => $nuevoEstado,
            ]);

            if ($trabajador->user) {
                $trabajador->user->update([
                    'estado' => $nuevoEstado === 'ACTIVO',
                ]);
            }
        });

        return redirect()
            ->route('trabajadores.index')
            ->with(
                'success',
                'Estado del trabajador actualizado correctamente.'
            );
    }
}