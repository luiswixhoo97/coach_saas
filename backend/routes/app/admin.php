<?php

use App\Http\Controllers\Admin\ControladorDashboard;
use App\Http\Controllers\Admin\ControladorCoach;
use App\Http\Controllers\Admin\ControladorCliente;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Administrador
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'rol:admin'])
    ->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [ControladorDashboard::class, 'index'])
        ->name('admin.dashboard');
    
    Route::get('/reportes/ingresos', [ControladorDashboard::class, 'reporteIngresos'])
        ->name('admin.reportes.ingresos');

    // Coaches
    Route::get('/coaches', [ControladorCoach::class, 'index'])
        ->name('admin.coaches.index');
    
    Route::post('/coaches', [ControladorCoach::class, 'almacenar'])
        ->name('admin.coaches.almacenar');
    
    Route::get('/coaches/{id}', [ControladorCoach::class, 'mostrar'])
        ->name('admin.coaches.mostrar');
    
    Route::put('/coaches/{id}', [ControladorCoach::class, 'actualizar'])
        ->name('admin.coaches.actualizar');
    
    Route::put('/coaches/{id}/toggle-status', [ControladorCoach::class, 'toggleEstado'])
        ->name('admin.coaches.toggle-estado');
    
    Route::delete('/coaches/{id}', [ControladorCoach::class, 'eliminar'])
        ->name('admin.coaches.eliminar');

    // Clientes (solo lectura para admin)
    Route::get('/clientes', [ControladorCliente::class, 'index'])
        ->name('admin.clientes.index');
    
    Route::get('/clientes/{id}', [ControladorCliente::class, 'mostrar'])
        ->name('admin.clientes.mostrar');

    // Pagos
    Route::get('/pagos', [ControladorDashboard::class, 'pagos'])
        ->name('admin.pagos.index');
});
