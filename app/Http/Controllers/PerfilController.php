<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function actualizarTelefono(Request $request): RedirectResponse
    {
        $trabajador = $request->user()->trabajador;
        abort_unless($trabajador && $trabajador->estado === 'ACTIVO', 403);

        $data = $request->validate([
            'telefono' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
        ], [
            'telefono.required' => 'Ingresa tu número de teléfono.',
            'telefono.regex' => 'Usa entre 7 y 15 dígitos, con + al inicio si incluyes el código de país.',
        ]);

        $trabajador->update(['telefono' => $data['telefono']]);

        return back()->with('success', 'Tu teléfono se actualizó correctamente.');
    }
}
