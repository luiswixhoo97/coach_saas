<?php

use App\Http\Controllers\Cliente\ControladorPerfil;
use App\Http\Controllers\Cliente\ControladorRutina;
use App\Http\Controllers\Cliente\ControladorDieta;
use App\Http\Controllers\Cliente\ControladorEvaluacion;
use App\Http\Controllers\Cliente\ControladorFormulario;
use App\Http\Controllers\Cliente\ControladorChat;
use App\Http\Controllers\Cliente\ControladorSuscripcion;
use App\Http\Controllers\Cliente\ControladorTienda;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Cliente
|--------------------------------------------------------------------------
*/

Route::prefix('cliente')
    ->middleware([
        'auth:sanctum', 
        'rol:cliente',
        'cliente.activo',        // Nuevo
        'formulario.completado'  // Nuevo
    ])
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */
    Route::get('/perfil', [ControladorPerfil::class, 'mostrar'])
        ->name('cliente.perfil.mostrar');
    
    Route::put('/perfil', [ControladorPerfil::class, 'actualizar'])
        ->name('cliente.perfil.actualizar');

    /*
    |--------------------------------------------------------------------------
    | Rutinas
    |--------------------------------------------------------------------------
    */
    Route::get('/rutinas', [ControladorRutina::class, 'index'])
        ->name('cliente.rutinas.index');
    
    Route::get('/rutinas/dia-actual', [ControladorRutina::class, 'rutinaDelDia'])
        ->name('cliente.rutinas.dia-actual');
    
    Route::get('/rutinas/{id}', [ControladorRutina::class, 'mostrar'])
        ->name('cliente.rutinas.mostrar');
    
    Route::post('/rutinas/{id}/registrar', [ControladorRutina::class, 'registrarEntrenamiento'])
        ->name('cliente.rutinas.registrar');
    
    Route::get('/ejercicios/{id}/video', [ControladorRutina::class, 'verVideo'])
        ->name('cliente.ejercicios.video');

    /*
    |--------------------------------------------------------------------------
    | Dieta
    |--------------------------------------------------------------------------
    */
    Route::get('/dieta', [ControladorDieta::class, 'mostrar'])
        ->name('cliente.dieta.mostrar');
    
    Route::get('/dieta/{id}', [ControladorDieta::class, 'ver'])
        ->name('cliente.dieta.ver');
    
    Route::get('/dieta/{id}/download', [ControladorDieta::class, 'descargar'])
        ->name('cliente.dieta.descargar');
    
    Route::get('/dieta/download', [ControladorDieta::class, 'descargar'])
        ->name('cliente.dieta.descargar.legacy');

    /*
    |--------------------------------------------------------------------------
    | Evaluaciones y Progreso
    |--------------------------------------------------------------------------
    */
    Route::get('/evaluaciones', [ControladorEvaluacion::class, 'index'])
        ->name('cliente.evaluaciones.index');
    
    Route::get('/evaluacion-agendada', [ControladorEvaluacion::class, 'evaluacionAgendada'])
        ->name('cliente.evaluacion-agendada');
    
    Route::get('/evaluaciones/{id}', [ControladorEvaluacion::class, 'mostrar'])
        ->name('cliente.evaluaciones.mostrar');
    
    Route::post('/evaluaciones/{id}/confirmar', [ControladorEvaluacion::class, 'confirmar'])
        ->name('cliente.evaluaciones.confirmar');
    
    Route::get('/progreso', [ControladorEvaluacion::class, 'progreso'])
        ->name('cliente.progreso');

    /*
    |--------------------------------------------------------------------------
    | Formularios
    |--------------------------------------------------------------------------
    */
    Route::get('/formularios', [ControladorFormulario::class, 'index'])
        ->name('cliente.formularios.index');
    
    Route::get('/formularios/{id}', [ControladorFormulario::class, 'mostrar'])
        ->name('cliente.formularios.mostrar');
    
    Route::post('/formulario-estandar/responder', [ControladorFormulario::class, 'responder'])
        ->name('cliente.formulario-estandar.responder');
    
    Route::get('/formulario-pendiente', [ControladorFormulario::class, 'formularioPendiente'])
        ->name('cliente.formulario-pendiente');

    /*
    |--------------------------------------------------------------------------
    | Chat
    |--------------------------------------------------------------------------
    */
    Route::get('/chat', [ControladorChat::class, 'mostrar'])
        ->name('cliente.chat.mostrar');
    
    Route::get('/chat/mensajes', [ControladorChat::class, 'mensajes'])
        ->name('cliente.chat.mensajes');
    
    Route::post('/chat/mensajes', [ControladorChat::class, 'enviarMensaje'])
        ->name('cliente.chat.enviar-mensaje');
    
    Route::put('/chat/leer', [ControladorChat::class, 'marcarLeido'])
        ->name('cliente.chat.marcar-leido');
    
    Route::get('/chat/archivos/{archivoId}', [ControladorChat::class, 'descargarArchivo'])
        ->name('cliente.chat.descargar-archivo');

    /*
    |--------------------------------------------------------------------------
    | Suscripción y Pagos
    |--------------------------------------------------------------------------
    */
    Route::get('/suscripcion', [ControladorSuscripcion::class, 'mostrar'])
        ->name('cliente.suscripcion.mostrar');
    
    Route::get('/pagos', [ControladorSuscripcion::class, 'pagos'])
        ->name('cliente.pagos.index');
    
    Route::post('/pagos/stripe', [ControladorSuscripcion::class, 'crearIntentoPago'])
        ->name('cliente.pagos.stripe');

    /*
    |--------------------------------------------------------------------------
    | Tienda
    |--------------------------------------------------------------------------
    */
    Route::get('/tienda/productos', [ControladorTienda::class, 'productos'])
        ->name('cliente.tienda.productos');
    
    Route::post('/tienda/ordenes', [ControladorTienda::class, 'crearOrden'])
        ->name('cliente.tienda.crear-orden');
    
    Route::get('/tienda/ordenes', [ControladorTienda::class, 'ordenes'])
        ->name('cliente.tienda.ordenes');
    
    Route::get('/tienda/ordenes/{id}', [ControladorTienda::class, 'mostrarOrden'])
        ->name('cliente.tienda.mostrar-orden');
    
    Route::post('/tienda/ordenes/{id}/pagar', [ControladorTienda::class, 'pagarOrden'])
        ->name('cliente.tienda.pagar-orden');
});
