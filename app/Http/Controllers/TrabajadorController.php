<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaEncargado;
use App\Models\Horario;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\HorarioEmpresaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrabajadorController extends Controller
{
    public function __construct(
        private readonly HorarioEmpresaService $horarioEmpresaService
    ) {}

    /**
     * Listado y filtros de trabajadores.
     */
    public function index(Request $request)
    {
        $this->autorizarAdministrador();

        $query = Trabajador::with([
            'area',
            'user.rol',
            'encargaturasActivas',
        ])->withExists([
            'reportesAvance as reporte_hoy' => function ($reporte) {
                $reporte->whereDate('fecha_presentacion', today());
            },
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
        $this->autorizarAdministrador();

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
            'encargado_area' => [
                'sometimes',
                'boolean',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $this->validarDesignacionEncargado($data);

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

            $this->sincronizarEncargatura(
                $trabajador,
                (bool) ($data['encargado_area'] ?? false)
            );

            $horario = Horario::where('estado', true)->orderBy('id')->first();

            if ($trabajador->estado === 'ACTIVO' && $horario) {
                $this->horarioEmpresaService->asignar(
                    $trabajador,
                    $horario,
                    $trabajador->fecha_ingreso?->startOfDay() ?? now()->startOfDay()
                );
            }
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
        $this->autorizarAdministrador();

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
            'encargado_area' => [
                'sometimes',
                'boolean',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $this->validarDesignacionEncargado($data);

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

            if (! $usuarioActual) {
                $usuarioActual = new User;
                $usuarioActual->trabajador_id = $trabajador->id;
            }

            $usuarioActual->rol_id = $data['rol_id'];
            $usuarioActual->name = trim(
                "{$trabajador->nombres} {$trabajador->apellidos}"
            );
            $usuarioActual->email = $data['email'];
            $usuarioActual->estado = $trabajador->estado === 'ACTIVO';

            if (! empty($data['password'])) {
                $usuarioActual->password = Hash::make($data['password']);
            }

            if (
                ! $usuarioActual->exists &&
                empty($data['password'])
            ) {
                throw new \Exception(
                    'Se necesita una contraseña para crear el usuario.'
                );
            }

            $usuarioActual->save();

            $this->sincronizarEncargatura(
                $trabajador,
                (bool) ($data['encargado_area'] ?? false)
            );

            $horario = Horario::where('estado', true)->orderBy('id')->first();

            if ($trabajador->estado === 'ACTIVO' && $horario) {
                $this->horarioEmpresaService->asignar($trabajador, $horario, now()->startOfDay());
            }
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
        $this->autorizarAdministrador();

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

            if ($nuevoEstado === 'ACTIVO') {
                $horario = Horario::where('estado', true)->orderBy('id')->first();

                if ($horario) {
                    $this->horarioEmpresaService->asignar($trabajador, $horario, now()->startOfDay());
                }
            } else {
                $this->cerrarEncargaturas(
                    AreaEncargado::vigentes()->where('trabajador_id', $trabajador->id)
                );
            }
        });

        return redirect()
            ->route('trabajadores.index')
            ->with(
                'success',
                'Estado del trabajador actualizado correctamente.'
            );
    }

    private function validarDesignacionEncargado(array $data): void
    {
        if (! ($data['encargado_area'] ?? false)) {
            return;
        }

        $rol = Rol::find($data['rol_id']);

        if (strtoupper(trim((string) $rol?->nombre)) !== 'TRABAJADOR') {
            throw ValidationException::withMessages([
                'encargado_area' => 'La designación de encargado solo está disponible para el rol TRABAJADOR.',
            ]);
        }

        if ($data['estado'] !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'encargado_area' => 'El encargado del área debe ser un trabajador activo.',
            ]);
        }
    }

    private function sincronizarEncargatura(Trabajador $trabajador, bool $esEncargado): void
    {
        $encargaturasDelTrabajador = AreaEncargado::vigentes()
            ->where('trabajador_id', $trabajador->id);

        if (! $esEncargado) {
            $this->cerrarEncargaturas($encargaturasDelTrabajador);

            return;
        }

        $this->cerrarEncargaturas(
            AreaEncargado::vigentes()
                ->where('area_id', $trabajador->area_id)
                ->where('trabajador_id', '!=', $trabajador->id)
        );

        $this->cerrarEncargaturas(
            (clone $encargaturasDelTrabajador)->where('area_id', '!=', $trabajador->area_id)
        );

        if (! (clone $encargaturasDelTrabajador)
            ->where('area_id', $trabajador->area_id)
            ->exists()) {
            AreaEncargado::create([
                'area_id' => $trabajador->area_id,
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => today(),
                'estado' => true,
            ]);
        }
    }

    private function cerrarEncargaturas(Builder $query): void
    {
        $query->update([
            'estado' => false,
            'fecha_fin' => today(),
        ]);
    }

    private function autorizarAdministrador(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->rol?->nombre === 'ADMINISTRADOR',
            403
        );
    }
}
