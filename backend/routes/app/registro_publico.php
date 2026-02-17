<?php

use App\Http\Controllers\RegistroPublico\ControladorRegistro;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Registro Público
|--------------------------------------------------------------------------
|
| Estas rutas NO requieren autenticación.
| Son accesibles mediante link único del coach.
|
*/

Route::prefix('registro')->group(function () {
    Route::get('/{token}', [ControladorRegistro::class, 'mostrar'])
        ->name('registro.mostrar')
        ->middleware('throttle:60,1');
    
    Route::post('', [ControladorRegistro::class, 'procesarRegistro'])
        ->name('registro.procesar')
        ->middleware('throttle:5,1');
});

