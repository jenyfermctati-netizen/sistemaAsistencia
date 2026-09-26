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

        if (Auth::attempt($credenciales)) {

            $request->session()->regenerate();

            // Registrar último acceso
            $usuario = Auth::user();

            $usuario->ultimo_acceso = now();
            $usuario->save();

            return redirect()
                ->intended(route('reportesAvance.index'));
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