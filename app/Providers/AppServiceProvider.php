<?php

namespace App\Providers;

use App\Models\Marcacion;
use App\Models\MarcacionCampo;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use App\Models\Solicitud;
use App\Models\Vacaciones;
use App\Observers\MarcacionObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Marcacion::observe(MarcacionObserver::class);

        View::composer('partials.sidebar', function (\Illuminate\View\View $view): void {
            $usuario = auth()->user();

            if (strtoupper(trim($usuario?->rol?->nombre ?? '')) !== 'ADMINISTRADOR') {
                return;
            }

            $view->with('pendientesSidebar', [
                'campo' => MarcacionCampo::where('estado', 'PENDIENTE')->count(),
                'solicitudes' => Solicitud::where('estado', 'PENDIENTE')
                    ->when($usuario->trabajador_id, fn ($query, $trabajadorId) =>
                        $query->where('trabajador_id', '!=', $trabajadorId)
                    )->count(),
                'vacaciones' => Vacaciones::where('estado', 'PENDIENTE')
                    ->whereHas('trabajador', fn ($query) => $query->where('tipo_vinculo', 'CONTRATADO'))
                    ->count(),
                'avances' => ReporteAvance::where('estado', 'PENDIENTE')->count()
                    + PendienteAvance::where('estado_revision', 'POR_REVISAR')->count(),
            ]);
        });
    }
}
