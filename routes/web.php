<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReporteAvanceController;


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');

});


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | REPORTES DE AVANCE
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

});


/*
|--------------------------------------------------------------------------
| INICIO
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (auth()->check()) {
        return redirect()->route('reportesAvance.index');
    }

    return redirect()->route('login');

});
