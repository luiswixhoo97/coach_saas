<?php

use App\Http\Controllers\Autenticacion\ControladorAutenticacion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    
    // Evita 405 como excepción no controlada cuando alguien abre la URL en el navegador (GET) o un crawler hace HEAD.
    Route::match(['get', 'head'], '/login', function () {
        return response()->json([
            'mensaje' => 'Este endpoint solo acepta POST. Envía email y contraseña en el cuerpo de la solicitud.',
        ], 405)->header('Allow', 'POST');
    });

    // Rutas públicas
    Route::post('/login', [ControladorAutenticacion::class, 'login'])
        ->name('autenticacion.login');
    
    Route::post('/forgot-password', [ControladorAutenticacion::class, 'olvidoPassword'])
        ->name('autenticacion.olvido-password');
    
    Route::post('/reset-password', [ControladorAutenticacion::class, 'resetearPassword'])
        ->name('autenticacion.resetear-password');

    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [ControladorAutenticacion::class, 'logout'])
            ->name('autenticacion.logout');
        
        Route::get('/me', [ControladorAutenticacion::class, 'me'])
            ->name('autenticacion.me');
        
        Route::put('/change-password', [ControladorAutenticacion::class, 'cambiarPassword'])
            ->name('autenticacion.cambiar-password');
    });
});
