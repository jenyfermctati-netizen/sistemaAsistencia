<?php

namespace App\Http\Controllers;

use App\Models\DispositivoBiometrico;
use App\Models\Marcacion;
use App\Models\BiometricoUsuario;
use App\Models\BiometricoComando;
use App\Services\BiometricoSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BiometricoEstadoController extends Controller
{
    public function __construct(
        private readonly BiometricoSyncService $biometricoSyncService
    ) {}

    public function index()
    {
        $this->verificarAdministrador();

        $dispositivos = DispositivoBiometrico::orderBy('nombre')->get();

        $usuarios = BiometricoUsuario::with(['trabajador', 'dispositivo'])
            ->orderBy('estado')
            ->orderBy('pin')
            ->get();

        $ultimaMarcacion = Marcacion::with('trabajador')
            ->where('origen', 'BIOMETRICO')
            ->where('anulada', false)
            ->latest('fecha_hora')
            ->first();

        $comandos = BiometricoComando::with('dispositivo')
            ->latest()
            ->limit(10)
            ->get();

        return view('biometrico.index', compact(
            'dispositivos',
            'usuarios',
            'ultimaMarcacion',
            'comandos'
        ));
    }

    public function consultarUsuarios(
        Request $request,
        DispositivoBiometrico $dispositivo
    ) {
        $this->verificarAdministrador();

        if (!$dispositivo->esta_conectado) {
            return back()->with(
                'error',
                'El dispositivo no está conectado.'
            );
        }

        $comando = $this
            ->biometricoSyncService
            ->solicitarUsuarios($dispositivo);

        return back()->with(
            'success',
            $comando->wasRecentlyCreated
                ? 'Consulta enviada. El SenseFace la recibirá en su próximo polling.'
                : 'Ya existe una consulta de usuarios pendiente.'
        );
    }

    public function reconciliar(
        DispositivoBiometrico $dispositivo
    ) {
        $this->verificarAdministrador();

        $resultado = $this
            ->biometricoSyncService
            ->reconciliar($dispositivo);

        return back()->with(
            'success',
            'Comparación completada. Sincronizados: '
                . ($resultado['SINCRONIZADO'] ?? 0)
                . ', pendientes: '
                . ($resultado['PENDIENTE'] ?? 0)
                . ', solo dispositivo: '
                . ($resultado['SOLO_DISPOSITIVO'] ?? 0) . '.'
        );
    }

    private function verificarAdministrador(): void
    {
        abort_unless(Auth::check() && Auth::user()->rol?->nombre === 'ADMINISTRADOR', 403);
    }
}
