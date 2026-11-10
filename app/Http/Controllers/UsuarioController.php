<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use App\Support\EmpresaRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Listado y filtros de usuarios.
     */
    public function index(Request $request)
    {
        $query = User::with([
            'rol',
            'trabajador',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = Rol::where('estado', true)
            ->where('ambito', 'EMPRESA')
            ->orderBy('nombre')
            ->get();

        $trabajadores = Trabajador::where('estado', 'ACTIVO')
            ->whereDoesntHave('user')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return view('usuarios.index', compact(
            'usuarios',
            'roles',
            'trabajadores'
        ));
    }

    /**
     * Registrar usuario.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'trabajador_id' => [
                'nullable',
                EmpresaRules::exists('trabajadores'),
                'unique:users,trabajador_id',
            ],
            'rol_id' => [
                'required',
                Rule::exists('roles', 'id')->where('ambito', 'EMPRESA')->where('estado', true),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'estado' => [
                'required',
                'boolean',
            ],
        ]);

        User::create([
            'trabajador_id' => $data['trabajador_id'] ?? null,
            'rol_id' => $data['rol_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'estado' => $data['estado'],
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario creado correctamente.'
            );
    }

    /**
     * Actualizar usuario.
     */
    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'trabajador_id' => [
                'nullable',
                EmpresaRules::exists('trabajadores'),
                Rule::unique('users', 'trabajador_id')
                    ->ignore($usuario->id),
            ],
            'rol_id' => [
                'required',
                Rule::exists('roles', 'id')->where('ambito', 'EMPRESA')->where('estado', true),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($usuario->id),
            ],
            'estado' => [
                'required',
                'boolean',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $usuario->trabajador_id = $data['trabajador_id'] ?? null;
        $usuario->rol_id = $data['rol_id'];
        $usuario->name = $data['name'];
        $usuario->email = $data['email'];
        $usuario->estado = $data['estado'];

        if (!empty($data['password'])) {
            $usuario->password = Hash::make($data['password']);
        }

        $usuario->save();

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario actualizado correctamente.'
            );
    }

    /**
     * Eliminar usuario.
     */
    public function destroy(User $usuario)
    {
        if (auth()->id() === $usuario->id) {
            return redirect()
                ->route('usuarios.index')
                ->with(
                    'error',
                    'No puedes eliminar tu propia cuenta.'
                );
        }

        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario eliminado correctamente.'
            );
    }
}
