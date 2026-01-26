<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
    ]);
});

// Aquí agregarás tus rutas API
// Route::middleware('auth:sanctum')->group(function () {
//     // Rutas protegidas
// });
