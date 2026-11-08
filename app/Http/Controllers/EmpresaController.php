<?php

namespace App\Http\Controllers;

use App\Models\DispositivoBiometrico;
use App\Models\Empresa;
use App\Models\EmpresaUsuario;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use App\Tenancy\EmpresaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $empresas = $request->user()->empresas()->wherePivot('estado', true)->where('empresas.estado', true)->get();

        return view('empresas.index', compact('empresas'));
    }

    public function seleccionar(Request $request)
    {
        $data = $request->validate(['empresa_id' => ['required', 'integer']]);
        $empresa = $request->user()->empresas()->wherePivot('estado', true)
            ->where('empresas.estado', true)->findOrFail($data['empresa_id']);
        $request->session()->put('empresa_id', $empresa->id);
        $request->session()->forget('url.intended');
        $request->session()->regenerateToken();

        return redirect()->route('dashboard');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->estado && $request->user()->membresias()
            ->where('estado', true)->whereHas('empresa', fn ($q) => $q->where('estado', true))
            ->whereIn('rol_id', Rol::where('nombre', 'ADMINISTRADOR')->where('estado', true)->select('id'))->exists(), 403);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ruc' => ['nullable', 'digits:11', 'unique:empresas,ruc'],
        ]);
        $empresa = DB::transaction(function () use ($data, $request) {
            $empresa = Empresa::create($data);
            $empresa->usuarios()->attach($request->user()->id, [
                'rol_id' => Rol::where('nombre', 'ADMINISTRADOR')->where('estado', true)->firstOrFail()->id,
                'estado' => true,
            ]);

            return $empresa;
        });
        $request->session()->put('empresa_id', $empresa->id);
        $request->session()->regenerateToken();

        return redirect()->route('dashboard')->with('success', 'Empresa creada. Ya puedes registrar sus áreas y personal.');
    }

    public function usuarios()
    {
        $this->autorizar();
        $empresa = app(EmpresaActual::class)->empresa();
        $usuarios = $empresa->usuarios()->orderBy('name')->get();
        $roles = Rol::where('estado', true)->get();
        $trabajadores = Trabajador::orderBy('apellidos')->get();
        $dispositivos = DispositivoBiometrico::orderBy('nombre')->get();

        return view('empresas.usuarios', compact('empresa', 'usuarios', 'roles', 'trabajadores', 'dispositivos'));
    }

    public function actualizar(Request $request)
    {
        $this->autorizar();
        $empresa = app(EmpresaActual::class)->empresa();
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ruc' => ['nullable', 'digits:11', Rule::unique('empresas', 'ruc')->ignore($empresa->id)],
        ]);
        $empresa->update($data);

        return back()->with('success', 'Datos de la empresa actualizados.');
    }

    public function guardarUsuario(Request $request)
    {
        $this->autorizar();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'rol_id' => ['required', Rule::exists('roles', 'id')->where('estado', true)],
            'trabajador_id' => ['nullable', 'integer', 'exists:trabajadores,id'],
            'estado' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $empresa = Empresa::whereKey(app(EmpresaActual::class)->requerirId())->lockForUpdate()->firstOrFail();
            $user = User::withoutGlobalScope('miembros_empresa')->where('email', $data['email'])->first();
            if (! $user) {
                if (empty($data['name']) || empty($data['password'])) {
                    throw ValidationException::withMessages(['email' => 'Para una cuenta nueva debes indicar nombre y contraseña.']);
                }
                $user = User::create([
                    'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                    'rol_id' => $data['rol_id'], 'estado' => true,
                ]);
            }
            if (! empty($data['trabajador_id']) && EmpresaUsuario::where('empresa_id', $empresa->id)
                ->where('trabajador_id', $data['trabajador_id'])->where('user_id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages(['trabajador_id' => 'El trabajador ya tiene una cuenta vinculada.']);
            }
            $user->actualizarMembresia([
                'rol_id' => $data['rol_id'], 'trabajador_id' => $data['trabajador_id'] ?? null,
                'estado' => $data['estado'],
            ]);
        });

        return back()->with('success', 'Acceso actualizado para esta empresa. Los datos de acceso de cuentas existentes se conservan.');
    }

    public function registrarDispositivo(Request $request)
    {
        $this->autorizar();
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'serial' => ['required', 'string', 'max:100', 'unique:dispositivos_biometricos,serial'],
        ]);
        // El serial identifica un dispositivo globalmente; nunca debe reasignarse a otra empresa.
        abort_if(DispositivoBiometrico::withoutGlobalScope('empresa')->where('serial', $data['serial'])->exists(), 422, 'Serial no disponible.');
        $token = Str::random(64);
        $dispositivo = new DispositivoBiometrico($data);
        $dispositivo->token_hash = hash('sha256', $token);
        $dispositivo->save();

        return back()->with('dispositivo_token', $token)->with('success', 'Dispositivo registrado. Guarda su token; se muestra una sola vez.');
    }

    private function autorizar(): void
    {
        abort_unless(auth()->user()?->rol?->nombre === 'ADMINISTRADOR', 403);
    }

    public function renovarToken(DispositivoBiometrico $dispositivo)
    {
        $this->autorizar();
        $token = Str::random(64);
        $dispositivo->token_hash = hash('sha256', $token);
        $dispositivo->save();

        return back()->with('dispositivo_token', $token)->with('success', 'Token renovado para '.$dispositivo->nombre.'. Actualiza la configuración del dispositivo.');
    }
}
