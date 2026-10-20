<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AsignacionCampoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricoController;
use App\Http\Controllers\BiometricoEstadoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeriadoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PasswordResetLinkController;
use App\Http\Controllers\ReporteAvanceController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\VacacionesController;
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
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
    Route::get('/olvide-contrasena', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/olvide-contrasena', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/restablecer-contrasena', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

// Rutas autenticadas
Route::middleware('auth')->group(function () {
    Route::get('/mi-cuenta/contrasena', [PasswordController::class, 'edit'])
        ->name('password.edit');
    Route::put('/mi-cuenta/contrasena', [PasswordController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Áreas
    Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::patch('/areas/{area}/estado', [AreaController::class, 'cambiarEstado'])->name('areas.estado');

    // Trabajadores
    Route::get('/trabajadores', [TrabajadorController::class, 'index'])->name('trabajadores.index');
    Route::post('/trabajadores', [TrabajadorController::class, 'store'])->name('trabajadores.store');
    Route::put('/trabajadores/{trabajador}', [TrabajadorController::class, 'update'])->name('trabajadores.update');
    Route::patch('/trabajadores/{trabajador}/estado', [TrabajadorController::class, 'cambiarEstado'])->name('trabajadores.estado');
    Route::post('/trabajadores/{trabajador}/reportes-avance', [ReporteAvanceController::class, 'storeParaTrabajador'])
        ->name('trabajadores.reportes-avance.store');

    // Horarios
    Route::get('/trabajadores/horarios', [HorarioController::class, 'index'])->name('trabajadores.horarios');
    Route::post('/trabajadores/horarios', [HorarioController::class, 'store'])->name('trabajadores.horarios.store');
    Route::put('/trabajadores/horarios/{horario}', [HorarioController::class, 'update'])->name('trabajadores.horarios.update');

    // Marcaciones
    Route::get('/marcaciones', [MarcacionController::class, 'index'])->name('marcaciones.index');
    Route::get('/marcaciones/manual', [MarcacionController::class, 'manual'])->name('marcaciones.manual');
    Route::post('/marcaciones/manual/{trabajador}', [MarcacionController::class, 'marcarAhora'])->name('marcaciones.marcarAhora');
    Route::post('/marcaciones', [MarcacionController::class, 'store'])->name('marcaciones.store');
    Route::patch('/marcaciones/{marcacion}/anular', [MarcacionController::class, 'anular'])->name('marcaciones.anular');

    // Asistencias
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
    Route::post('/asistencias/procesar', [AsistenciaController::class, 'procesar'])->name('asistencias.procesar');

    // Feriados
    Route::get('/feriados', [FeriadoController::class, 'index'])->name('feriados.index');
    Route::post('/feriados', [FeriadoController::class, 'store'])->name('feriados.store');
    Route::post('/feriados/sincronizar', [FeriadoController::class, 'sincronizarNacionales'])->name('feriados.sincronizar');
    Route::put('/feriados/{feriado}', [FeriadoController::class, 'update'])->name('feriados.update');
    Route::patch('/feriados/{feriado}/estado', [FeriadoController::class, 'cambiarEstado'])->name('feriados.estado');

    // Trabajo en campo
    Route::get('/asignaciones-campo', [AsignacionCampoController::class, 'index'])->name('asignaciones-campo.index');
    Route::post('/asignaciones-campo', [AsignacionCampoController::class, 'store'])->name('asignaciones-campo.store');
    Route::put('/asignaciones-campo/{asignacion}', [AsignacionCampoController::class, 'update'])->name('asignaciones-campo.update');
    Route::patch('/asignaciones-campo/{asignacion}/cancelar', [AsignacionCampoController::class, 'cancelar'])->name('asignaciones-campo.cancelar');

    // Solicitudes
    Route::get('/solicitudes', [SolicitudController::class, 'index'])->name('solicitudes.index');
    Route::post('/solicitudes', [SolicitudController::class, 'store'])->name('solicitudes.store');
    Route::put('/solicitudes/{solicitud}', [SolicitudController::class, 'update'])->name('solicitudes.update');
    Route::patch('/solicitudes/{solicitud}/cancelar', [SolicitudController::class, 'cancelar'])->name('solicitudes.cancelar');
    Route::patch('/solicitudes/{solicitud}/revisar', [SolicitudController::class, 'revisar'])->name('solicitudes.revisar');
    Route::get('/solicitudes/{solicitud}/archivo', [SolicitudController::class, 'verArchivo'])->name('solicitudes.archivo.ver');
    Route::get('/solicitudes/{solicitud}/archivo/descargar', [SolicitudController::class, 'descargarArchivo'])->name('solicitudes.archivo.descargar');

    // Reportes de avance
    Route::get('/reportesAvance', [ReporteAvanceController::class, 'index'])->name('reportesAvance.index');
    Route::post('/reportesAvance', [ReporteAvanceController::class, 'store'])->name('reportesAvance.store');
    Route::post('/reportesAvance/{reporte}/agregar', [ReporteAvanceController::class, 'agregarAvance'])
        ->name('reportesAvance.agregar');
    Route::put('/reportesAvance/{reporte}', [ReporteAvanceController::class, 'update'])->name('reportesAvance.update');
    Route::patch('/reportesAvance/{reporte}/revisar', [ReporteAvanceController::class, 'revisar'])->name('reportesAvance.revisar');
    Route::post('/reportesAvance/pendientes', [ReporteAvanceController::class, 'storePendiente'])->name('reportesAvance.pendientes.store');
    Route::post('/reportesAvance/pendientes/registrar', [ReporteAvanceController::class, 'storePendienteTrabajador'])
        ->name('reportesAvance.pendientes.trabajador.store');
    Route::patch('/reportesAvance/pendientes/{pendiente}/resolver', [ReporteAvanceController::class, 'resolverPendiente'])->name('reportesAvance.pendientes.resolver');
    Route::patch('/reportesAvance/pendientes/{pendiente}/revisar', [ReporteAvanceController::class, 'revisarPendiente'])
        ->name('reportesAvance.pendientes.revisar');
    Route::get('/reportesAvance/actividades/{actividad}/archivo', [ReporteAvanceController::class, 'verArchivoActividad'])
        ->name('reportesAvance.actividades.archivo');
    Route::get('/reportesAvance/actividades/{actividad}/descargar', [ReporteAvanceController::class, 'descargarArchivoActividad'])
        ->name('reportesAvance.actividades.descargar');

    // Vacaciones
    Route::get('/vacaciones', [VacacionesController::class, 'index'])->name('vacaciones.index');
    Route::post('/vacaciones', [VacacionesController::class, 'store'])->name('vacaciones.store');
    Route::put('/vacaciones/{vacacion}', [VacacionesController::class, 'update'])->name('vacaciones.update');
    Route::patch('/vacaciones/{vacacion}/revisar', [VacacionesController::class, 'revisar'])->name('vacaciones.revisar');
    Route::patch('/vacaciones/{vacacion}/cancelar', [VacacionesController::class, 'cancelar'])->name('vacaciones.cancelar');
    Route::patch('/vacaciones/{vacacion}/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');

    // Biometrico
    Route::get('/biometrico', [BiometricoEstadoController::class, 'index'])->name('biometrico.index');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Biometrico
Route::match(['GET', 'POST'], '/iclock/cdata', [BiometricoController::class, 'cdata']);
Route::match(['GET', 'POST'], '/iclock/cdata.aspx', [BiometricoController::class, 'cdata']);

Route::match(['GET', 'POST'], '/iclock/getrequest', [BiometricoController::class, 'getRequest']);
Route::match(['GET', 'POST'], '/iclock/getrequest.aspx', [BiometricoController::class, 'getRequest']);

Route::match(['GET', 'POST'], '/iclock/devicecmd', [BiometricoController::class, 'deviceCmd']);
Route::match(['GET', 'POST'], '/iclock/devicecmd.aspx', [BiometricoController::class, 'deviceCmd']);

Route::match(['GET', 'POST'], '/iclock/registry', [BiometricoController::class, 'registry']);
Route::match(['GET', 'POST'], '/iclock/registry.aspx', [BiometricoController::class, 'registry']);

Route::match(['GET', 'POST'], '/iclock/ping', [BiometricoController::class, 'ping']);
Route::match(['GET', 'POST'], '/iclock/ping.aspx', [BiometricoController::class, 'ping']);
