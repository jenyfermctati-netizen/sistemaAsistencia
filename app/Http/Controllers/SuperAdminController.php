<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\DispositivoBiometrico;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use App\Notifications\EmpresaSuspendida;
use App\Notifications\InvitacionAdministradorEmpresa;
use App\Rules\RucPeru;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $metricas = [
            'empresas' => Empresa::count(),
            'activas' => Empresa::where('estado', Empresa::ACTIVA)->count(),
            'suspendidas' => Empresa::where('estado', Empresa::SUSPENDIDA)->count(),
            'inactivas' => Empresa::where('estado', Empresa::INACTIVA)->count(),
            'trabajadores' => Trabajador::withoutGlobalScopes()->count(),
            'usuarios' => User::withoutGlobalScopes()->whereNotNull('empresa_id')->count(),
            'dispositivos' => DispositivoBiometrico::withoutGlobalScopes()->count(),
            'nuevas' => Empresa::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $empresasRecientes = Empresa::withCount(['trabajadores', 'users', 'dispositivosBiometricos'])
            ->latest()->limit(8)->get();

        $inicio = now()->startOfMonth()->subMonths(5);
        $altas = Empresa::where('created_at', '>=', $inicio)
            ->where('created_at', '<', now()->startOfMonth()->addMonth())
            ->get(['created_at'])->countBy(fn ($empresa) => $empresa->created_at->format('Y-m'));
        $registrosMensuales = collect(range(0, 5))->map(function ($indice) use ($inicio, $altas) {
            $mes = $inicio->copy()->addMonths($indice);

            return [
                'etiqueta' => $mes->locale('es')->translatedFormat('M Y'),
                'total' => (int) $altas->get($mes->format('Y-m'), 0),
            ];
        });

        return view('superadmin.dashboard', compact('metricas', 'empresasRecientes', 'registrosMensuales'));
    }

    public function empresas(Request $request): View
    {
        $query = Empresa::query()->withCount(['trabajadores', 'users', 'dispositivosBiometricos']);

        $query->when($request->filled('buscar'), function ($query) use ($request) {
            $buscar = trim((string) $request->input('buscar'));
            $query->where(fn ($q) => $q->where('razon_social', 'like', "%{$buscar}%")
                ->orWhere('nombre_comercial', 'like', "%{$buscar}%")
                ->orWhere('ruc', 'like', "%{$buscar}%"));
        });
        $query->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')));

        $orden = in_array($request->input('orden'), ['nombre', 'recientes'], true) ? $request->input('orden') : 'nombre';
        $orden === 'recientes' ? $query->orderByDesc('created_at')->orderByDesc('id') : $query->orderBy('razon_social')->orderBy('id');
        $empresas = $query->paginate(12)->withQueryString();

        return view('superadmin.empresas.index', compact('empresas'));
    }

    public function storeEmpresa(Request $request): RedirectResponse
    {
        $data = $request->validate($this->reglasEmpresa() + [
            'admin_nombre' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $rolAdministrador = $this->rolAdministrador();

        [$empresa, $administrador] = DB::transaction(function () use ($data, $rolAdministrador) {
            $empresa = Empresa::create($this->datosEmpresa($data));
            $administrador = User::withoutGlobalScopes()->create([
                'empresa_id' => $empresa->id,
                'trabajador_id' => null,
                'rol_id' => $rolAdministrador->id,
                'name' => $data['admin_nombre'],
                'email' => mb_strtolower($data['admin_email']),
                'password' => Str::random(64),
                'estado' => true,
            ]);

            $this->auditoria->modelo('CREAR_EMPRESA', $empresa);
            $this->auditoria->modelo('CREAR_ADMIN_EMPRESA', $administrador);

            return [$empresa, $administrador];
        });

        $correoEnviado = $this->enviarInvitacion($administrador, $empresa);

        return redirect()->route('superadmin.empresas.show', $empresa)
            ->with($correoEnviado ? 'success' : 'warning', $correoEnviado
                ? 'Empresa creada y la invitación fue enviada al administrador.'
                : 'Empresa creada, pero el correo de invitación falló. Reintenta desde la ficha de la empresa.');
    }

    public function showEmpresa(Empresa $empresa): View
    {
        $empresa->loadCount(['trabajadores', 'users', 'dispositivosBiometricos']);
        $administradores = $this->administradoresQuery($empresa)->orderBy('name')->get();
        $dispositivos = $empresa->dispositivosBiometricos()->latest()->get();

        return view('superadmin.empresas.show', compact('empresa', 'administradores', 'dispositivos'));
    }

    public function updateEmpresa(Request $request, Empresa $empresa): RedirectResponse
    {
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
        $query = User::withoutGlobalScopes()->with('empresa')
            ->where('rol_id', $rolId)->whereNotNull('empresa_id');
        $query->when($request->filled('empresa_id'), fn ($q) => $q->where('empresa_id', $request->integer('empresa_id')));
        $query->when($request->filled('buscar'), function ($q) use ($request) {
            $buscar = trim((string) $request->input('buscar'));
            $q->where(fn ($f) => $f->where('name', 'like', "%{$buscar}%")->orWhere('email', 'like', "%{$buscar}%"));
        });

        $administradores = $query->orderBy('name')->paginate(20)->withQueryString();
        $empresas = Empresa::orderBy('razon_social')->get(['id', 'razon_social']);

        return view('superadmin.administradores.index', compact('administradores', 'empresas'));
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
        $resumenQuery = Empresa::query()->when($request->filled('empresa_id'), fn ($q) => $q->whereKey($request->integer('empresa_id')));
        $estados = (clone $resumenQuery)->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $resumenEstados = [
            'ACTIVA' => (int) $estados->get('ACTIVA', 0),
            'SUSPENDIDA' => (int) $estados->get('SUSPENDIDA', 0),
            'INACTIVA' => (int) $estados->get('INACTIVA', 0),
        ];
        $estadoEmpresas = $resumenQuery->orderBy('razon_social')->paginate(10, ['*'], 'estados_page')->withQueryString();
        $query = Auditoria::withoutGlobalScopes()->with(['empresa', 'usuario']);
        $query->when($request->filled('empresa_id'), fn ($q) => $q->where('empresa_id', $request->integer('empresa_id')));
        $query->when($request->filled('accion'), fn ($q) => $q->where('accion', 'like', '%'.trim((string) $request->input('accion')).'%'));
        $registros = $query->latest()->paginate(30)->withQueryString();
        $empresas = Empresa::orderBy('razon_social')->get(['id', 'razon_social']);

        return view('superadmin.auditoria.index', compact('registros', 'empresas', 'resumenEstados', 'estadoEmpresas'));
    }

    private function reglasEmpresa(?Empresa $empresa = null): array
    {
        return [
            'color_principal' => ['sometimes', 'required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'razon_social' => ['required', 'string', 'max:200'],
            'nombre_comercial' => ['nullable', 'string', 'max:200'],
            'ruc' => ['required', 'digits:11', new RucPeru, Rule::unique('empresas', 'ruc')->ignore($empresa?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'zona_horaria' => ['required', 'timezone:all'],
        ];
    }

    private function datosEmpresa(array $data): array
    {
        return collect($data)->only([
            'razon_social', 'nombre_comercial', 'ruc', 'email', 'telefono', 'direccion', 'zona_horaria', 'color_principal',
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
