<?php

namespace App\Http\Controllers;

use App\Models\BiometricoComando;
use App\Models\BiometricoUsuario;
use App\Models\DispositivoBiometrico;
use App\Models\Marcacion;
use App\Services\BiometricoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function sincronizarUsuario(DispositivoBiometrico $dispositivo, BiometricoUsuario $usuario)
    {
        $this->verificarAdministrador();

        if (!$dispositivo->esta_conectado) {
            return back()->with(
                'error',
                'El dispositivo biométrico no está conectado.'
            );
        }

        try {
            $comando = $this->biometricoSyncService->sincronizarUsuario(
                $dispositivo,
                $usuario
            );

            return back()->with(
                'success',
                $comando->wasRecentlyCreated
                    ? 'Trabajador enviado a la cola de sincronización.'
                    : 'Este trabajador ya tiene una sincronización pendiente.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with(
                'error',
                collect($e->errors())->flatten()->first()
            );
        } catch (\Throwable $e) {
            \Log::error('Error sincronizando usuario SenseFace', [
                'dispositivo_id' => $dispositivo->id,
                'usuario_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'No se pudo preparar la sincronización.'
            );
        }
    }

    public function sincronizarPendientes(DispositivoBiometrico $dispositivo)
    {
        $this->verificarAdministrador();

        if (!$dispositivo->esta_conectado) {
            return back()->with(
                'error',
                'El dispositivo biométrico no está conectado.'
            );
        }

        try {
            $resultado = $this
                ->biometricoSyncService
                ->sincronizarPendientes($dispositivo);

            if ($resultado['total'] === 0) {
                return back()->with(
                    'success',
                    'No hay trabajadores pendientes de sincronización.'
                );
            }

            return back()->with(
                'success',
                'Sincronización preparada. '
                    . 'Nuevos comandos: ' . $resultado['creados']
                    . ', ya en cola: ' . $resultado['existentes']
                    . ', errores: ' . $resultado['errores'] . '.'
            );
        } catch (\Throwable $e) {
            \Log::error('Error sincronizando pendientes SenseFace', [
                'dispositivo_id' => $dispositivo->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'No se pudo preparar la sincronización de los trabajadores pendientes.'
            );
        }
    }
}
