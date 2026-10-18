<?php

namespace App\Http\Controllers;

use App\Models\DispositivoBiometrico;
use App\Models\Marcacion;
use Illuminate\Support\Facades\Auth;

class BiometricoEstadoController extends Controller
{
    public function index()
    {
        $this->verificarAdministrador();

        $dispositivos = DispositivoBiometrico::orderBy('nombre')->get();

        $ultimaMarcacion = Marcacion::with('trabajador')
            ->where('origen', 'BIOMETRICO')
            ->where('anulada', false)
            ->latest('fecha_hora')
            ->first();

        return view('biometrico.index', compact('dispositivos', 'ultimaMarcacion'));
    }

    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() &&
                Auth::user()->rol?->nombre === 'ADMINISTRADOR',
            403
        );
    }
}
