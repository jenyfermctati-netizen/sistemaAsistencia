<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReporteAvanceController;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | LOGIN
 * |--------------------------------------------------------------------------
 */

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');
});

/*
 * |--------------------------------------------------------------------------
 * | RUTAS PROTEGIDAS
 * |--------------------------------------------------------------------------
 */

Route::middleware('auth')->group(function () {

    //Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    /*
     * |--------------------------------------------------------------------------
     * | REPORTES DE AVANCE
     * |--------------------------------------------------------------------------
     */

    Route::resource(
        'reportesAvance',
        ReporteAvanceController::class
    )->parameters([
        'reportesAvance' => 'reporte'
    ]);

    Route::patch(
        '/reportesAvance/{reporte}/revisar',
        [ReporteAvanceController::class, 'revisar']
    )->name('reportesAvance.revisar');

    /*
     * |--------------------------------------------------------------------------
     * | LOGOUT
     * |--------------------------------------------------------------------------
     */

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});

/*
 * |--------------------------------------------------------------------------
 * | INICIO
 * |--------------------------------------------------------------------------
 */

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('reportesAvance.index');
    }

    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');
});
