<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AsignacionCampoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeriadoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\ReporteAvanceController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\TrabajadorController;
use Illuminate\Support\Facades\Route;


// Redirección inicial
Route::get('/', function () {

    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});


// Rutas de invitados
Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [AuthController::class, 'showLogin']
    )->name('login');

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )->name('login.process');
});


// Rutas autenticadas
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | ÁREAS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/areas',
        [AreaController::class, 'index']
    )->name('areas.index');

    Route::post(
        '/areas',
        [AreaController::class, 'store']
    )->name('areas.store');

    Route::put(
        '/areas/{area}',
        [AreaController::class, 'update']
    )->name('areas.update');

    Route::patch(
        '/areas/{area}/estado',
        [AreaController::class, 'cambiarEstado']
    )->name('areas.estado');


    /*
    |--------------------------------------------------------------------------
    | TRABAJADORES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/trabajadores',
        [TrabajadorController::class, 'index']
    )->name('trabajadores.index');

    Route::post(
        '/trabajadores',
        [TrabajadorController::class, 'store']
    )->name('trabajadores.store');

    Route::put(
        '/trabajadores/{trabajador}',
        [TrabajadorController::class, 'update']
    )->name('trabajadores.update');

    Route::patch(
        '/trabajadores/{trabajador}/estado',
        [TrabajadorController::class, 'cambiarEstado']
    )->name('trabajadores.estado');


    /*
    |--------------------------------------------------------------------------
    | HORARIOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/trabajadores/horarios',
        [HorarioController::class, 'index']
    )->name('trabajadores.horarios');

    Route::post(
        '/trabajadores/horarios',
        [HorarioController::class, 'store']
    )->name('trabajadores.horarios.store');

    Route::post(
        '/trabajadores/horarios/asignar',
        [HorarioController::class, 'asignar']
    )->name('trabajadores.horarios.asignar');

    Route::post(
        '/trabajadores/horarios/asignar-masivo',
        [HorarioController::class, 'asignarMasivo']
    )->name('trabajadores.horarios.asignarMasivo');

    Route::put(
        '/trabajadores/horarios/{horario}',
        [HorarioController::class, 'update']
    )->name('trabajadores.horarios.update');

    Route::patch(
        '/trabajadores/horarios/{horario}/estado',
        [HorarioController::class, 'cambiarEstado']
    )->name('trabajadores.horarios.estado');


    /*
    |--------------------------------------------------------------------------
    | MARCACIONES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/marcaciones',
        [MarcacionController::class, 'index']
    )->name('marcaciones.index');

    Route::post(
        '/marcaciones',
        [MarcacionController::class, 'store']
    )->name('marcaciones.store');

    Route::patch(
        '/marcaciones/{marcacion}/anular',
        [MarcacionController::class, 'anular']
    )->name('marcaciones.anular');


    /*
    |--------------------------------------------------------------------------
    | ASISTENCIAS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/asistencias',
        [AsistenciaController::class, 'index']
    )->name('asistencias.index');

    Route::post(
        '/asistencias/procesar',
        [AsistenciaController::class, 'procesar']
    )->name('asistencias.procesar');


    /*
    |--------------------------------------------------------------------------
    | FERIADOS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/feriados',
        [FeriadoController::class, 'index']
    )->name('feriados.index');

    Route::post(
        '/feriados',
        [FeriadoController::class, 'store']
    )->name('feriados.store');

    Route::post(
        '/feriados/sincronizar',
        [FeriadoController::class, 'sincronizarNacionales']
    )->name('feriados.sincronizar');

    Route::put(
        '/feriados/{feriado}',
        [FeriadoController::class, 'update']
    )->name('feriados.update');

    Route::patch(
        '/feriados/{feriado}/estado',
        [FeriadoController::class, 'cambiarEstado']
    )->name('feriados.estado');


    /*
    |--------------------------------------------------------------------------
    | TRABAJO EN CAMPO
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/asignaciones-campo',
        [AsignacionCampoController::class, 'index']
    )->name('asignaciones-campo.index');

    Route::post(
        '/asignaciones-campo',
        [AsignacionCampoController::class, 'store']
    )->name('asignaciones-campo.store');

    Route::put(
        '/asignaciones-campo/{asignacion}',
        [AsignacionCampoController::class, 'update']
    )->name('asignaciones-campo.update');

    Route::patch(
        '/asignaciones-campo/{asignacion}/cancelar',
        [AsignacionCampoController::class, 'cancelar']
    )->name('asignaciones-campo.cancelar');


    /*
    |--------------------------------------------------------------------------
    | SOLICITUDES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/solicitudes',
        [SolicitudController::class, 'index']
    )->name('solicitudes.index');

    Route::post(
        '/solicitudes',
        [SolicitudController::class, 'store']
    )->name('solicitudes.store');

    Route::put(
        '/solicitudes/{solicitud}',
        [SolicitudController::class, 'update']
    )->name('solicitudes.update');

    Route::patch(
        '/solicitudes/{solicitud}/cancelar',
        [SolicitudController::class, 'cancelar']
    )->name('solicitudes.cancelar');

    Route::patch(
        '/solicitudes/{solicitud}/revisar',
        [SolicitudController::class, 'revisar']
    )->name('solicitudes.revisar');


    /*
    |--------------------------------------------------------------------------
    | REPORTES DE AVANCE
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reportesAvance',
        [ReporteAvanceController::class, 'index']
    )->name('reportesAvance.index');

    Route::post(
        '/reportesAvance',
        [ReporteAvanceController::class, 'store']
    )->name('reportesAvance.store');

    Route::put(
        '/reportesAvance/{reporte}',
        [ReporteAvanceController::class, 'update']
    )->name('reportesAvance.update');

    Route::patch(
        '/reportesAvance/{reporte}/revisar',
        [ReporteAvanceController::class, 'revisar']
    )->name('reportesAvance.revisar');


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    )->name('logout');
});

/*
|--------------------------------------------------------------------------
| VACACIONES
|--------------------------------------------------------------------------
*/

Route::get(
    '/vacaciones',
    [VacacionesController::class, 'index']
)->name('vacaciones.index');

Route::post(
    '/vacaciones',
    [VacacionesController::class, 'store']
)->name('vacaciones.store');

Route::put(
    '/vacaciones/{vacacion}',
    [VacacionesController::class, 'update']
)->name('vacaciones.update');

Route::patch(
    '/vacaciones/{vacacion}/revisar',
    [VacacionesController::class, 'revisar']
)->name('vacaciones.revisar');

Route::patch(
    '/vacaciones/{vacacion}/cancelar',
    [VacacionesController::class, 'cancelar']
)->name('vacaciones.cancelar');

Route::patch(
    '/vacaciones/{vacacion}/reprogramar',
    [VacacionesController::class, 'reprogramar']
)->name('vacaciones.reprogramar');