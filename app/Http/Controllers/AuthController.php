<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login.
     */
    public function showLogin()
    {
        return view('auth.login');
    }


    /**
     * Procesar inicio de sesión.
     */
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credenciales['estado'] = 1;

        if (Auth::attempt($credenciales, false)) {
            $request->session()->regenerate();
            $usuario = Auth::user();

            $usuario->loadMissing(['rol', 'empresa']);

            $accesoGlobalValido = $usuario->isSuperAdmin();
            $accesoEmpresaValido = $usuario->rol?->ambito === 'EMPRESA'
                && $usuario->empresa?->estaActiva();

            if (! $accesoGlobalValido && ! $accesoEmpresaValido) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'La cuenta o la empresa no están habilitadas para acceder.',
                ])->onlyInput('email');
            }

            $actualizacion = [
                'ultimo_acceso' => now(),
            ];

            if (Schema::hasColumn('users', 'active_session_id')) {
                $actualizacion['active_session_id'] = $request->session()->getId();
            }

            DB::table('users')->where('id', $usuario->getKey())->increment('ingresos_count', 1, $actualizacion);

            return redirect()
                ->intended(route($accesoGlobalValido ? 'superadmin.dashboard' : 'dashboard'));
        }

        return back()
            ->withErrors([
                'email' => 'El correo o la contraseña son incorrectos.',
            ])
            ->onlyInput('email');
    }


    /**
     * Cerrar sesión.
     */
    public function logout(Request $request)
    {
        $usuario = Auth::user();
        $sessionId = $request->session()->getId();

        if ($usuario && Schema::hasColumn('users', 'active_session_id')) {
            DB::table('users')
                ->where('id', $usuario->getKey())
                ->where('active_session_id', $sessionId)
                ->update(['active_session_id' => null]);
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
