<?php

use App\Services\AsistenciaService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('asistencias:cerrar {fecha?}', function () {
    $fechaIngresada = $this->argument('fecha');
    $fecha = $fechaIngresada
        ? Carbon::parse($fechaIngresada)->startOfDay()
        : today()->subDay();

    if (! $fecha->lt(today())) {
        $this->error('El cierre automático solo puede ejecutarse para un día ya terminado.');

        return 1;
    }

    $procesados = app(AsistenciaService::class)->procesarFecha($fecha);

    $this->info(
        "Cierre completado para {$fecha->toDateString()}: {$procesados} asistencias procesadas."
    );

    return 0;
})->purpose('Cierra las asistencias de un día terminado y completa marcas olvidadas');

Schedule::command('asistencias:cerrar')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/asistencias-cierre.log'));
