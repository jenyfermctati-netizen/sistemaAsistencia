<?php

namespace App\Providers;

use App\Models\ActividadReporte;
use App\Models\Marcacion;
use App\Models\MarcacionCampo;
use App\Models\PendienteAvance;
use App\Models\Solicitud;
use App\Models\Vacaciones;
use App\Observers\MarcacionObserver;
use App\Tenancy\EmpresaActual;
use App\Tenancy\VerificadorPresenciaEmpresa;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(EmpresaActual::class);
        $this->app->singleton('validation.presence', fn ($app) => new VerificadorPresenciaEmpresa($app['db'])
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->afterResolving('validator', function ($validator, $app): void {
            $validator->setPresenceVerifier(new VerificadorPresenciaEmpresa($app['db']));
        });
        Marcacion::observe(MarcacionObserver::class);

        View::composer('partials.sidebar', function (\Illuminate\View\View $view): void {
            $usuario = auth()->user();

            $rol = strtoupper(trim($usuario?->rol?->nombre ?? ''));

            if ($rol === 'TRABAJADOR') {
                $areaId = $usuario->trabajador?->area_id;
                $view->with('pendientesAreaSidebar', $areaId
                    ? PendienteAvance::abiertosOEnRevision()->deArea($areaId)->count()
                    : 0);
            }

            if ($rol !== 'ADMINISTRADOR') {
                return;
            }

            $view->with('pendientesSidebar', [
                'campo' => MarcacionCampo::where('estado', 'PENDIENTE')->count(),
                'solicitudes' => Solicitud::where('estado', 'PENDIENTE')
                    ->when($usuario->trabajador_id, fn ($query, $trabajadorId) => $query->where('trabajador_id', '!=', $trabajadorId)
                    )->count(),
                'vacaciones' => Vacaciones::where('estado', 'PENDIENTE')
                    ->whereHas('trabajador', fn ($query) => $query->where('tipo_vinculo', 'CONTRATADO'))
                    ->count(),
                'avances' => PendienteAvance::abiertosOEnRevision()->count()
                    + ActividadReporte::count(),
            ]);
        });
    }
}
