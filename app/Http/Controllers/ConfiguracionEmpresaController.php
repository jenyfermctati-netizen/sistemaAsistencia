<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\TransferirAdministradorService;
use App\Support\EmpresaRules;
use Illuminate\Http\Request;

class ConfiguracionEmpresaController extends Controller
{
    public function index(Request $request)
    {
        return view('empresa.configuracion', [
            'empresa' => $request->user()->empresa,
            'usuarios' => User::where('estado', true)->where('id', '!=', $request->user()->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AuditoriaService $auditoria)
    {
        $data = $request->validate([
            'nombre_comercial' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'zona_horaria' => ['required', 'timezone:all'],
            'color_turquesa' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
        ]);
        $empresa = $request->user()->empresa;
        $anteriores = $empresa->only(array_keys($data));
        $empresa->update($data);
        $auditoria->registrar('CONFIGURAR_EMPRESA', 'empresas', $empresa->id, $anteriores, $data);

        return back()->with('success', 'Configuración actualizada.');
    }

    public function transferir(Request $request, TransferirAdministradorService $service)
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'integer', EmpresaRules::exists('users')],
            'password' => ['required', 'current_password'],
        ]);
        $service->transferir($request->user()->empresa, $request->user(), (int) $data['usuario_id']);
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Administración transferida. La sesión se cerró porque cambiaron tus permisos.');
    }

    public function auditoria()
    {
        return view('empresa.auditoria', ['eventos' => Auditoria::with('usuario')->latest('id')->paginate(30)]);
    }
}
