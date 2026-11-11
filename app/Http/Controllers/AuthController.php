<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
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

            DB::table('users')->where('id', $usuario->getKey())->increment('ingresos_count', 1, [
                'ultimo_acceso' => now(),
            ]);

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
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
