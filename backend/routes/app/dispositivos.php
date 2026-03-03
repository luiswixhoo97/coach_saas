<?php

use App\Http\Controllers\Dispositivos\ControladorDispositivo;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de dispositivos (tokens FCM para push)
|--------------------------------------------------------------------------
| Disponible para cualquier usuario autenticado (coach o cliente).
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dispositivos', [ControladorDispositivo::class, 'registrar'])
        ->name('dispositivos.registrar');
});
