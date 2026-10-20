<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /** Muestra el formulario existente de recuperación. */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /** Solicita el envío del enlace al correo registrado. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ]);

        try {
            // Usa el broker configurado en config/auth.php y la notificación
            // sendPasswordResetNotification() del modelo User.
            $status = Password::broker('users')->sendResetLink($data);
        } catch (Throwable $exception) {
            Log::error('Falló el envío del enlace de recuperación de contraseña.', [
                'exception' => $exception,
            ]);

            return back()->withErrors([
                'email' => 'No se pudo procesar la solicitud de recuperación. Intenta nuevamente más tarde.',
            ])->withInput($request->only('email'));
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Debes esperar antes de solicitar otro enlace.',
            ])->withInput($request->only('email'));
        }

        // No revelamos si existe una cuenta con el correo indicado.
        return back()->with('status',
            'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.'
        );
    }
}