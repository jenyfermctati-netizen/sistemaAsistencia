<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AsignacionCampoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricoController;
use App\Http\Controllers\BiometricoEstadoController;
use App\Http\Controllers\ConsultaDniController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeriadoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PasswordResetLinkController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ReporteAvanceController;
use App\Http\Controllers\ReporteControlController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\VacacionesController;
use Illuminate\Support\Facades\Route;

// Redirección inicial
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isSuperAdmin()
            ? 'superadmin.dashboard'
            : 'dashboard');
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

Route::middleware('auth')->group(function () {
    Route::patch('/mi-cuenta/telefono', [PerfilController::class, 'actualizarTelefono'])
        ->middleware(['empresa', 'throttle:10,1'])->name('perfil.telefono.update');
    Route::get('/mi-cuenta/contrasena', [PasswordController::class, 'edit'])
        ->name('password.edit');
    Route::put('/mi-cuenta/contrasena', [PasswordController::class, 'update'])
        ->middleware('throttle:5,1')->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('superadmin')->name('superadmin.')
    ->middleware(['auth', 'superadmin'])
    ->group(function () {
        Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/empresas', [SuperAdminController::class, 'empresas'])->name('empresas.index');
        Route::post('/empresas', [SuperAdminController::class, 'storeEmpresa'])->name('empresas.store');
        Route::get('/empresas/{empresa}', [SuperAdminController::class, 'showEmpresa'])->name('empresas.show');
        Route::put('/empresas/{empresa}', [SuperAdminController::class, 'updateEmpresa'])->name('empresas.update');
        Route::patch('/empresas/{empresa}/estado', [SuperAdminController::class, 'cambiarEstado'])->name('empresas.estado');
        Route::post('/empresas/{empresa}/administradores', [SuperAdminController::class, 'storeAdministrador'])->name('administradores.store');
        Route::post('/empresas/{empresa}/administradores/{administrador}/invitacion', [SuperAdminController::class, 'reenviarInvitacion'])->name('administradores.invitacion');
        Route::patch('/empresas/{empresa}/administradores/{administrador}/estado', [SuperAdminController::class, 'cambiarEstadoAdministrador'])->name('administradores.estado');
        Route::get('/administradores', [SuperAdminController::class, 'administradores'])->name('administradores.index');
        Route::get('/dispositivos', [SuperAdminController::class, 'dispositivos'])->name('dispositivos.index');
        Route::post('/dispositivos', [SuperAdminController::class, 'storeDispositivo'])->name('dispositivos.store');
        Route::post('/empresas/{empresa}/dispositivos', [SuperAdminController::class, 'storeDispositivo'])->name('empresas.dispositivos.store');
        Route::patch('/dispositivos/{dispositivo}/estado', [SuperAdminController::class, 'cambiarEstadoDispositivo'])->name('dispositivos.estado');
        Route::get('/auditoria', [SuperAdminController::class, 'auditoria'])->name('auditoria.index');
    });

// Rutas autenticadas
Route::middleware(['auth', 'empresa', 'auditoria.empresa'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Áreas
    Route::get('/areas', [AreaController::class, 'index'])->middleware('role:ADMINISTRADOR')->name('areas.index');
    Route::post('/areas', [AreaController::class, 'store'])->middleware('role:ADMINISTRADOR')->name('areas.store');
    Route::put('/areas/{area}', [AreaController::class, 'update'])->middleware('role:ADMINISTRADOR')->name('areas.update');
    Route::patch('/areas/{area}/estado', [AreaController::class, 'cambiarEstado'])->middleware('role:ADMINISTRADOR')->name('areas.estado');

    // Trabajadores
    Route::post('/trabajadores/consultar-dni', [ConsultaDniController::class, 'consultar'])
        ->middleware(['role:ADMINISTRADOR', 'throttle:20,1'])
        ->name('trabajadores.consultar-dni');
    Route::get('/trabajadores', [TrabajadorController::class, 'index'])->middleware('role:ADMINISTRADOR')->name('trabajadores.index');
    Route::post('/trabajadores', [TrabajadorController::class, 'store'])->middleware('role:ADMINISTRADOR')->name('trabajadores.store');
    Route::put('/trabajadores/{trabajador}', [TrabajadorController::class, 'update'])->middleware('role:ADMINISTRADOR')->name('trabajadores.update');
    Route::patch('/trabajadores/{trabajador}/estado', [TrabajadorController::class, 'cambiarEstado'])->middleware('role:ADMINISTRADOR')->name('trabajadores.estado');
    Route::post('/trabajadores/{trabajador}/reportes-avance', [ReporteAvanceController::class, 'storeParaTrabajador'])
        ->middleware('role:ADMINISTRADOR')->name('trabajadores.reportes-avance.store');

    // Horarios
    Route::get('/trabajadores/horarios', [HorarioController::class, 'index'])->middleware('role:ADMINISTRADOR')->name('trabajadores.horarios');
    Route::post('/trabajadores/horarios', [HorarioController::class, 'store'])->middleware('role:ADMINISTRADOR')->name('trabajadores.horarios.store');
    Route::put('/trabajadores/horarios/{horario}', [HorarioController::class, 'update'])->middleware('role:ADMINISTRADOR')->name('trabajadores.horarios.update');

    // Marcaciones
    Route::get('/marcaciones', [MarcacionController::class, 'index'])->middleware('role:ADMINISTRADOR')->name('marcaciones.index');
    Route::get('/marcaciones/manual', [MarcacionController::class, 'manual'])->middleware('role:ADMINISTRADOR')->name('marcaciones.manual');
    Route::post('/marcaciones/manual/{trabajador}', [MarcacionController::class, 'marcarAhora'])->middleware('role:ADMINISTRADOR')->name('marcaciones.marcarAhora');
    Route::post('/marcaciones', [MarcacionController::class, 'store'])->middleware('role:ADMINISTRADOR')->name('marcaciones.store');
    Route::patch('/marcaciones/{marcacion}/anular', [MarcacionController::class, 'anular'])->middleware('role:ADMINISTRADOR')->name('marcaciones.anular');

    // Asistencias
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->middleware('role:ADMINISTRADOR,GERENTE')->name('asistencias.index');
    Route::post('/asistencias/procesar', [AsistenciaController::class, 'procesar'])->middleware('role:ADMINISTRADOR')->name('asistencias.procesar');

    // Feriados
    Route::get('/feriados', [FeriadoController::class, 'index'])->middleware('role:ADMINISTRADOR')->name('feriados.index');
    Route::post('/feriados', [FeriadoController::class, 'store'])->middleware('role:ADMINISTRADOR')->name('feriados.store');
    Route::post('/feriados/sincronizar', [FeriadoController::class, 'sincronizarNacionales'])->middleware('role:ADMINISTRADOR')->name('feriados.sincronizar');
    Route::put('/feriados/{feriado}', [FeriadoController::class, 'update'])->middleware('role:ADMINISTRADOR')->name('feriados.update');
    Route::patch('/feriados/{feriado}/estado', [FeriadoController::class, 'cambiarEstado'])->middleware('role:ADMINISTRADOR')->name('feriados.estado');

    // Trabajo en campo
    Route::get('/asignaciones-campo', [AsignacionCampoController::class, 'index'])->name('asignaciones-campo.index');
    Route::post('/asignaciones-campo', [AsignacionCampoController::class, 'store'])->name('asignaciones-campo.store');
    Route::put('/asignaciones-campo/{asignacion}', [AsignacionCampoController::class, 'update'])->name('asignaciones-campo.update');
    Route::patch('/asignaciones-campo/{asignacion}/cancelar', [AsignacionCampoController::class, 'cancelar'])->name('asignaciones-campo.cancelar');
    Route::post('/asignaciones-campo/{asignacion}/marcar', [AsignacionCampoController::class, 'marcarRemoto'])
        ->middleware('throttle:10,1')
        ->name('asignaciones-campo.marcar');
    Route::post('/asignaciones-campo/{asignacion}/avances', [AsignacionCampoController::class, 'registrarAvance'])
        ->middleware('throttle:10,1')
        ->name('asignaciones-campo.avances.store');
    Route::get('/marcaciones-campo/{marcacionCampo}/evidencia', [AsignacionCampoController::class, 'verEvidencia'])
        ->name('marcaciones-campo.evidencia');
    Route::patch('/marcaciones-campo/{marcacionCampo}/revisar', [AsignacionCampoController::class, 'revisarMarcacion'])
        ->name('marcaciones-campo.revisar');

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
    Route::put('/reportesAvance/pendientes/{pendiente}', [ReporteAvanceController::class, 'actualizarPendienteTrabajador'])
        ->name('reportesAvance.pendientes.update');
    Route::patch('/reportesAvance/pendientes/{pendiente}/resolver', [ReporteAvanceController::class, 'resolverPendiente'])->name('reportesAvance.pendientes.resolver');
    Route::patch('/reportesAvance/pendientes/{pendiente}/revisar', [ReporteAvanceController::class, 'revisarPendiente'])
        ->name('reportesAvance.pendientes.revisar');
    Route::get('/reportesAvance/actividades/{actividad}/archivo', [ReporteAvanceController::class, 'verArchivoActividad'])
        ->name('reportesAvance.actividades.archivo');
    Route::get('/reportesAvance/actividades/{actividad}/descargar', [ReporteAvanceController::class, 'descargarArchivoActividad'])
        ->name('reportesAvance.actividades.descargar');

    // Exportaciones del reporte consolidado mostrado en el dashboard
    Route::get('/dashboard/reporte-control/pdf', [ReporteControlController::class, 'pdf'])
        ->name('dashboard.reporteControl.pdf');
    Route::get('/dashboard/reporte-control/excel', [ReporteControlController::class, 'excel'])
        ->name('dashboard.reporteControl.excel');

    // Vacaciones
    Route::get('/vacaciones', [VacacionesController::class, 'index'])->name('vacaciones.index');
    Route::post('/vacaciones', [VacacionesController::class, 'store'])->name('vacaciones.store');
    Route::put('/vacaciones/{vacacion}', [VacacionesController::class, 'update'])->name('vacaciones.update');
    Route::patch('/vacaciones/{vacacion}/revisar', [VacacionesController::class, 'revisar'])->name('vacaciones.revisar');
    Route::patch('/vacaciones/{vacacion}/cancelar', [VacacionesController::class, 'cancelar'])->name('vacaciones.cancelar');
    Route::patch('/vacaciones/{vacacion}/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');

    // Biometrico
    Route::get('/biometrico', [BiometricoEstadoController::class, 'index'])->middleware('role:ADMINISTRADOR')->name('biometrico.index');

    Route::post('/biometrico/{dispositivo}/consultar-usuarios', [BiometricoEstadoController::class, 'consultarUsuarios'])
        ->middleware('role:ADMINISTRADOR')->name('biometrico.usuarios.consultar');
    Route::post('/biometrico/{dispositivo}/reconciliar', [BiometricoEstadoController::class, 'reconciliar'])
        ->middleware('role:ADMINISTRADOR')->name('biometrico.usuarios.reconciliar');
    Route::post('/biometrico/{dispositivo}/usuarios/{usuario}/sincronizar', [BiometricoEstadoController::class, 'sincronizarUsuario'])->middleware('role:ADMINISTRADOR')->name('biometrico.usuarios.sincronizar');
    Route::post('/biometrico/{dispositivo}/usuarios/sincronizar-pendientes', [BiometricoEstadoController::class, 'sincronizarPendientes'])->middleware('role:ADMINISTRADOR')->name('biometrico.usuarios.sincronizar-pendientes');
});

// Biometrico
Route::middleware(['biometrico.dispositivo', 'throttle:120,1'])->group(function () {
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
});
