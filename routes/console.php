<?php

use App\Models\Empresa;
use App\Services\AsistenciaService;
use App\Support\EmpresaContext;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('asistencias:cerrar {fecha?}', function () {
    $fechaIngresada = $this->argument('fecha');
    $total = 0;

    Empresa::where('estado', Empresa::ACTIVA)->orderBy('id')->each(function (Empresa $empresa) use ($fechaIngresada, &$total) {
        $hoyEmpresa = Carbon::now($empresa->zona_horaria)->startOfDay();
        $fecha = $fechaIngresada
            ? Carbon::parse($fechaIngresada, $empresa->zona_horaria)->startOfDay()
            : $hoyEmpresa->copy()->subDay();

        if (! $fecha->lt($hoyEmpresa)) {
            $this->warn("{$empresa->razon_social}: la fecha debe ser anterior al día local actual.");

            return;
        }

        $procesados = app(EmpresaContext::class)->run(
            $empresa,
            fn () => app(AsistenciaService::class)->procesarFecha($fecha)
        );
        $total += $procesados;
        $this->line("{$empresa->razon_social}: {$procesados} asistencias procesadas para {$fecha->toDateString()}.");
    });

    $this->info("Cierre multiempresa completado: {$total} asistencias procesadas.");

    return 0;
})->purpose('Cierra las asistencias de un día terminado y completa marcas olvidadas');

Schedule::command('asistencias:cerrar')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/asistencias-cierre.log'));
