<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\ReporteAvanceController;
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
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

// Rutas autenticadas
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

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

    // Horarios
    Route::get('/trabajadores/horarios', [HorarioController::class, 'index'])->name('trabajadores.horarios');
    Route::post('/trabajadores/horarios', [HorarioController::class, 'store'])->name('trabajadores.horarios.store');
    Route::put('/trabajadores/horarios/{horario}', [HorarioController::class, 'update'])->name('trabajadores.horarios.update');
    Route::patch('/trabajadores/horarios/{horario}/estado', [HorarioController::class, 'cambiarEstado'])->name('trabajadores.horarios.estado');
    Route::post('/trabajadores/horarios/asignar', [HorarioController::class, 'asignar'])->name('trabajadores.horarios.asignar');

    // Reportes de avance
    Route::get('/reportesAvance', [ReporteAvanceController::class, 'index'])->name('reportesAvance.index');
    Route::post('/reportesAvance', [ReporteAvanceController::class, 'store'])->name('reportesAvance.store');
    Route::put('/reportesAvance/{reporte}', [ReporteAvanceController::class, 'update'])->name('reportesAvance.update');
    Route::patch('/reportesAvance/{reporte}/revisar', [ReporteAvanceController::class, 'revisar'])->name('reportesAvance.revisar');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    // Horarios
    Route::get('/trabajadores/horarios',[HorarioController::class, 'index'])->name('trabajadores.horarios');
    Route::post('/trabajadores/horarios',[HorarioController::class, 'store'])->name('trabajadores.horarios.store');
    Route::post('/trabajadores/horarios/asignar',[HorarioController::class, 'asignar'])->name('trabajadores.horarios.asignar');
    Route::put('/trabajadores/horarios/{horario}',[HorarioController::class, 'update'])->name('trabajadores.horarios.update');
    Route::patch('/trabajadores/horarios/{horario}/estado',[HorarioController::class, 'cambiarEstado'])->name('trabajadores.horarios.estado');