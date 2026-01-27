<?php

use App\Http\Controllers\Webhook\ControladorStripe;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Webhooks
|--------------------------------------------------------------------------
|
| Estas rutas NO requieren autenticación.
| Stripe envía eventos directamente a estos endpoints.
|
*/

Route::prefix('webhooks')->group(function () {
    
    Route::post('/stripe', [ControladorStripe::class, 'handle'])
        ->name('webhooks.stripe');
});
