<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            // Registrar último acceso
            $usuario = Auth::user();

            $usuario->ultimo_acceso = now();
            $usuario->save();

            $request->session()->forget('empresa_id');
            $empresas = $usuario->empresas()->wherePivot('estado', true)->where('empresas.estado', true)->get();
            if ($empresas->count() !== 1) {
                return redirect()->route('empresas.index');
            }
            $request->session()->put('empresa_id', $empresas->first()->id);

            return redirect()
                ->intended(route('dashboard'));
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
