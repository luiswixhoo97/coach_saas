<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Coach SaaS
|--------------------------------------------------------------------------
|
| Prefijo: /api/v1
| Autenticación: Laravel Sanctum
|
*/

Route::prefix('v1')->group(function () {
    
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'estado' => 'ok',
            'mensaje' => 'API funcionando correctamente',
            'version' => 'v1',
        ]);
    });

    // Cargar rutas de módulos
    require __DIR__.'/app/autenticacion.php';
    require __DIR__.'/app/admin.php';
    require __DIR__.'/app/coach.php';
    require __DIR__.'/app/cliente.php';
    require __DIR__.'/app/webhooks.php';
});
