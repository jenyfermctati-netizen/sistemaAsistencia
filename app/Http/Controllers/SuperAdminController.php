<?php

namespace App\Http\Controllers;

use App\Models\DispositivoBiometrico;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\EmpresaSuspendida;
use App\Notifications\InvitacionAdministradorEmpresa;
use App\Rules\RucPeru;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SuperAdminController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function dashboard(): View
    {
        $inicioPeriodo = now()->startOfDay()->subDays(6);
        $empresasPorDia = Empresa::where('created_at', '>=', $inicioPeriodo)
            ->get(['created_at'])
            ->groupBy(fn (Empresa $empresa) => $empresa->created_at->toDateString());
        $tendenciaEmpresas = collect(range(6, 0))
            ->map(function (int $diasAtras) use ($empresasPorDia) {
                $fecha = now()->startOfDay()->subDays($diasAtras);

                return [
                    'label' => $fecha->locale('es')->translatedFormat('D'),
                    'fecha' => $fecha->toDateString(),
                    'total' => $empresasPorDia->get($fecha->toDateString(), collect())->count(),
                ];
            });

        $rolAdministradorId = $this->rolAdministrador()->id;
        $metricas = [
            'empresas' => Empresa::count(),
            'activas' => Empresa::where('estado', Empresa::ACTIVA)->count(),
            'suspendidas' => Empresa::where('estado', Empresa::SUSPENDIDA)->count(),
            'inactivas' => Empresa::where('estado', Empresa::INACTIVA)->count(),
            'nuevas' => Empresa::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $empresasRecientes = Empresa::withCount([
            'users as administradores_count' => fn ($users) => $users->where('rol_id', $rolAdministradorId),
        ])
            ->latest()->limit(8)->get();

        return view('superadmin.dashboard', compact('metricas', 'empresasRecientes', 'tendenciaEmpresas'));
    }

    public function empresas(Request $request): View
    {
        $rolAdministradorId = $this->rolAdministrador()->id;
        $query = Empresa::query()->withCount([
            'users as administradores_count' => fn ($users) => $users->where('rol_id', $rolAdministradorId),
        ]);

        $query->when($request->filled('buscar'), function ($query) use ($request) {
            $buscar = trim((string) $request->input('buscar'));
            $query->where(fn ($q) => $q->where('razon_social', 'like', "%{$buscar}%")
                ->orWhere('nombre_comercial', 'like', "%{$buscar}%")
                ->orWhere('ruc', 'like', "%{$buscar}%"));
        });
        $query->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')));

        $empresas = $query->latest()->paginate(15)->withQueryString();

        return view('superadmin.empresas.index', compact('empresas'));
    }

    public function storeEmpresa(Request $request): RedirectResponse
    {
        $request->mergeIfMissing(['color_turquesa' => '#00A9C7']);
        $data = $request->validate($this->reglasEmpresa());
        $empresa = Empresa::create($this->datosEmpresa($data));
        $this->auditoria->modelo('CREAR_EMPRESA', $empresa);

        return redirect(route('superadmin.empresas.show', $empresa).'#accesos')
            ->with('success', 'Empresa creada. Añade sus administradores para continuar.');
    }

    public function showEmpresa(Empresa $empresa): View
    {
        $administradores = $this->administradoresQuery($empresa)->orderBy('name')->get();

        return view('superadmin.empresas.show', compact('empresa', 'administradores'));
    }

    public function updateEmpresa(Request $request, Empresa $empresa): RedirectResponse
    {
        $request->mergeIfMissing(['color_turquesa' => $empresa->color_turquesa ?: '#00A9C7']);
        $data = $request->validate($this->reglasEmpresa($empresa));
        $anteriores = $empresa->getAttributes();
        $empresa->update($this->datosEmpresa($data));
        $this->auditoria->modelo('ACTUALIZAR_EMPRESA', $empresa, $anteriores);

        return back()->with('success', 'Información empresarial actualizada.');
    }

    public function cambiarEstado(Request $request, Empresa $empresa): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in([Empresa::ACTIVA, Empresa::SUSPENDIDA, Empresa::INACTIVA])],
        ]);
        $anterior = $empresa->estado;
        $empresa->update(['estado' => $data['estado']]);
        $this->auditoria->modelo('CAMBIAR_ESTADO_EMPRESA', $empresa, ['estado' => $anterior]);

        $fallos = 0;
        if ($data['estado'] === Empresa::SUSPENDIDA) {
            foreach ($this->administradoresQuery($empresa)->where('estado', true)->get() as $admin) {
                try {
                    $admin->notify(new EmpresaSuspendida($empresa));
                } catch (Throwable $e) {
                    report($e);
                    $fallos++;
                }
            }
        }

        return back()->with($fallos ? 'warning' : 'success', $fallos
            ? 'Estado actualizado, pero una o más notificaciones no pudieron enviarse.'
            : 'Estado de la empresa actualizado correctamente.');
    }

    public function administradores(Request $request): View
    {
        $rolId = $this->rolAdministrador()->id;
        $query = Empresa::query()->withCount([
            'users as administradores_count' => fn ($users) => $users->where('rol_id', $rolId),
        ]);
        $query->when($request->filled('buscar'), function ($q) use ($request) {
            $buscar = trim((string) $request->input('buscar'));
            $q->where(fn ($f) => $f->where('razon_social', 'like', "%{$buscar}%")
                ->orWhere('nombre_comercial', 'like', "%{$buscar}%")
                ->orWhere('ruc', 'like', "%{$buscar}%"));
        });
        $empresas = $query->orderBy('razon_social')->paginate(20)->withQueryString();

        return view('superadmin.administradores.index', compact('empresas'));
    }

    public function storeAdministrador(Request $request, Empresa $empresa): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $administrador = User::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id,
            'trabajador_id' => null,
            'rol_id' => $this->rolAdministrador()->id,
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Str::random(64),
            'estado' => true,
        ]);
        $this->auditoria->modelo('CREAR_ADMIN_EMPRESA', $administrador);
        $enviado = $this->enviarInvitacion($administrador, $empresa);

        return back()->with($enviado ? 'success' : 'warning', $enviado
            ? 'Administrador creado e invitación enviada.'
            : 'Administrador creado, pero no se pudo enviar la invitación.');
    }

    public function reenviarInvitacion(Empresa $empresa, User $administrador): RedirectResponse
    {
        abort_unless((int) $administrador->empresa_id === (int) $empresa->id, 404);
        abort_unless($administrador->roleCode() === 'ADMINISTRADOR', 404);

        $enviado = $this->enviarInvitacion($administrador, $empresa);

        return back()->with($enviado ? 'success' : 'error',
            'La invitación '.($enviado ? 'fue enviada.' : 'no pudo enviarse.'));
    }

    public function cambiarEstadoAdministrador(Empresa $empresa, User $administrador): RedirectResponse
    {
        abort_unless((int) $administrador->empresa_id === (int) $empresa->id, 404);
        abort_unless($administrador->roleCode() === 'ADMINISTRADOR', 404);

        if ($administrador->estado && $this->administradoresQuery($empresa)->where('estado', true)->count() <= 1) {
            return back()->with('error', 'La empresa no puede quedar sin un administrador activo.');
        }

        $administrador->update(['estado' => ! $administrador->estado]);
        $this->auditoria->modelo('CAMBIAR_ESTADO_ADMIN_EMPRESA', $administrador);

        return back()->with('success', 'Estado de la cuenta administrativa actualizado.');
    }

    public function dispositivos(Request $request): View
    {
        $query = DispositivoBiometrico::withoutGlobalScopes()->with('empresa');
        $query->when($request->filled('empresa_id'), fn ($q) => $q->where('empresa_id', $request->integer('empresa_id')));
        $query->when($request->filled('buscar'), function ($q) use ($request) {
            $buscar = trim((string) $request->input('buscar'));
            $q->where(fn ($f) => $f->where('nombre', 'like', "%{$buscar}%")->orWhere('serial', 'like', "%{$buscar}%"));
        });
        $dispositivos = $query->latest()->paginate(20)->withQueryString();
        $empresas = Empresa::orderBy('razon_social')->get(['id', 'razon_social']);

        return view('superadmin.dispositivos.index', compact('dispositivos', 'empresas'));
    }

    public function storeDispositivo(Request $request, ?Empresa $empresa = null): RedirectResponse
    {
        $data = $request->validate([
            'empresa_id' => [$empresa ? 'nullable' : 'required', 'integer', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'serial' => ['required', 'string', 'max:100', 'unique:dispositivos_biometricos,serial'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'generar_secreto' => ['sometimes', 'boolean'],
        ]);
        $empresaId = $empresa?->id ?? (int) $data['empresa_id'];
        $secreto = $request->boolean('generar_secreto') ? Str::random(48) : null;

        $dispositivo = DispositivoBiometrico::withoutGlobalScopes()->create([
            'empresa_id' => $empresaId,
            'nombre' => $data['nombre'],
            'serial' => trim($data['serial']),
            'modelo' => $data['modelo'] ?? null,
            'estado' => true,
            'secreto_hash' => $secreto ? Hash::make($secreto) : null,
        ]);
        $this->auditoria->modelo('CREAR_DISPOSITIVO', $dispositivo);

        $respuesta = back()->with('success', 'Dispositivo registrado y asignado a la empresa.');
        if ($secreto) {
            $respuesta->with('secreto_dispositivo', $secreto);
        }

        return $respuesta;
    }

    public function cambiarEstadoDispositivo(DispositivoBiometrico $dispositivo): RedirectResponse
    {
        $dispositivo->update(['estado' => ! $dispositivo->estado]);
        $this->auditoria->modelo('CAMBIAR_ESTADO_DISPOSITIVO', $dispositivo);

        return back()->with('success', 'Estado del dispositivo actualizado.');
    }

    public function auditoria(Request $request): View
    {
        $rolId = $this->rolAdministrador()->id;
        $query = User::withoutGlobalScopes()->with('empresa')
            ->where('rol_id', $rolId)
            ->whereNotNull('empresa_id');
        $query->when($request->filled('buscar'), function ($users) use ($request) {
            $buscar = trim((string) $request->input('buscar'));
            $users->where(fn ($filter) => $filter
                ->where('name', 'like', "%{$buscar}%")
                ->orWhere('email', 'like', "%{$buscar}%")
                ->orWhereHas('empresa', fn ($empresa) => $empresa->where('razon_social', 'like', "%{$buscar}%")));
        });
        $administradores = $query->orderByDesc('ingresos_count')->orderBy('name')->paginate(30)->withQueryString();

        return view('superadmin.auditoria.index', compact('administradores'));
    }

    private function reglasEmpresa(?Empresa $empresa = null): array
    {
        $reglasRuc = [
            'required',
            'digits:11',
            Rule::unique('empresas', 'ruc')->ignore($empresa?->id),
        ];

        // Keep an imported legacy identifier editable alongside other company
        // details. Only validate its check digit when the RUC itself is changed.
        if (! $empresa || request()->input('ruc') !== $empresa->ruc) {
            $reglasRuc[] = new RucPeru;
        }

        return [
            'razon_social' => ['required', 'string', 'max:200'],
            'nombre_comercial' => ['nullable', 'string', 'max:200'],
            'ruc' => $reglasRuc,
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'zona_horaria' => ['required', 'timezone:all'],
            'color_turquesa' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
        ];
    }

    private function datosEmpresa(array $data): array
    {
        return collect($data)->only([
            'razon_social', 'nombre_comercial', 'ruc', 'email', 'telefono', 'direccion', 'zona_horaria', 'color_turquesa',
        ])->all();
    }

    private function rolAdministrador(): Rol
    {
        return Rol::where('codigo', 'ADMINISTRADOR')->where('ambito', 'EMPRESA')->where('estado', true)->firstOrFail();
    }

    private function administradoresQuery(Empresa $empresa)
    {
        return User::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('rol_id', $this->rolAdministrador()->id);
    }

    private function enviarInvitacion(User $administrador, Empresa $empresa): bool
    {
        try {
            $token = Password::broker()->createToken($administrador);
            $administrador->notify(new InvitacionAdministradorEmpresa($empresa, $token));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
