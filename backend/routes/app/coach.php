<?php

use App\Http\Controllers\Coach\ControladorPerfil;
use App\Http\Controllers\Coach\ControladorCliente;
use App\Http\Controllers\Coach\ControladorPlan;
use App\Http\Controllers\Coach\ControladorSuscripcion;
use App\Http\Controllers\Coach\ControladorPago;
use App\Http\Controllers\Coach\ControladorEjercicio;
use App\Http\Controllers\Coach\ControladorRutina;
use App\Http\Controllers\Coach\ControladorDieta;
use App\Http\Controllers\Coach\ControladorParametro;
use App\Http\Controllers\Coach\ControladorParametroCliente;
use App\Http\Controllers\Coach\ControladorEvaluacion;
use App\Http\Controllers\Coach\ControladorFormulario;
use App\Http\Controllers\Coach\ControladorChat;
use App\Http\Controllers\Coach\ControladorProducto;
use App\Http\Controllers\Coach\ControladorOrden;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Coach
|--------------------------------------------------------------------------
*/

Route::prefix('coach')
    ->middleware(['auth:sanctum', 'rol:coach'])
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */
    Route::get('/perfil', [ControladorPerfil::class, 'mostrar'])
        ->name('coach.perfil.mostrar');
    
    Route::put('/perfil', [ControladorPerfil::class, 'actualizar'])
        ->name('coach.perfil.actualizar');
    
    Route::post('/perfil/avatar', [ControladorPerfil::class, 'subirAvatar'])
        ->name('coach.perfil.avatar');
    
    Route::get('/dashboard', [ControladorPerfil::class, 'dashboard'])
        ->name('coach.dashboard');
    
    Route::post('/perfil/generar-link-registro', [ControladorPerfil::class, 'generarLinkRegistro'])
        ->name('coach.perfil.generar-link-registro');
    
    Route::put('/perfil/toggle-link-registro', [ControladorPerfil::class, 'toggleLinkRegistro'])
        ->name('coach.perfil.toggle-link-registro');

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */
    Route::get('/clientes', [ControladorCliente::class, 'index'])
        ->name('coach.clientes.index');
    
    Route::post('/clientes', [ControladorCliente::class, 'almacenar'])
        ->name('coach.clientes.almacenar');
    
    Route::get('/clientes/{id}', [ControladorCliente::class, 'mostrar'])
        ->name('coach.clientes.mostrar');
    
    Route::put('/clientes/{id}', [ControladorCliente::class, 'actualizar'])
        ->name('coach.clientes.actualizar');
    
    Route::delete('/clientes/{id}', [ControladorCliente::class, 'eliminar'])
        ->name('coach.clientes.eliminar');
    
    Route::get('/clientes/{id}/historial', [ControladorCliente::class, 'historial'])
        ->name('coach.clientes.historial');
    
    Route::get('/clientes/{id}/progreso', [ControladorCliente::class, 'progreso'])
        ->name('coach.clientes.progreso');
    
    Route::put('/clientes/{id}/activar', [ControladorCliente::class, 'activar'])
        ->name('coach.clientes.activar');
    
    Route::put('/clientes/{id}/desactivar', [ControladorCliente::class, 'desactivar'])
        ->name('coach.clientes.desactivar');

    /*
    |--------------------------------------------------------------------------
    | Planes
    |--------------------------------------------------------------------------
    */
    Route::get('/planes', [ControladorPlan::class, 'index'])
        ->name('coach.planes.index');
    
    Route::post('/planes', [ControladorPlan::class, 'almacenar'])
        ->name('coach.planes.almacenar');
    
    Route::get('/planes/{id}', [ControladorPlan::class, 'mostrar'])
        ->name('coach.planes.mostrar');
    
    Route::put('/planes/{id}', [ControladorPlan::class, 'actualizar'])
        ->name('coach.planes.actualizar');
    
    Route::delete('/planes/{id}', [ControladorPlan::class, 'eliminar'])
        ->name('coach.planes.eliminar');
    
    Route::post('/planes/{id}/stripe-sync', [ControladorPlan::class, 'sincronizarStripe'])
        ->name('coach.planes.stripe-sync');

    /*
    |--------------------------------------------------------------------------
    | Suscripciones
    |--------------------------------------------------------------------------
    */
    Route::get('/suscripciones', [ControladorSuscripcion::class, 'index'])
        ->name('coach.suscripciones.index');
    
    Route::post('/suscripciones', [ControladorSuscripcion::class, 'almacenar'])
        ->name('coach.suscripciones.almacenar');
    
    Route::get('/suscripciones/{id}', [ControladorSuscripcion::class, 'mostrar'])
        ->name('coach.suscripciones.mostrar');
    
    Route::put('/suscripciones/{id}', [ControladorSuscripcion::class, 'actualizar'])
        ->name('coach.suscripciones.actualizar');
    
    Route::post('/suscripciones/{id}/renovar', [ControladorSuscripcion::class, 'renovar'])
        ->name('coach.suscripciones.renovar');
    
    Route::post('/suscripciones/{id}/cancelar', [ControladorSuscripcion::class, 'cancelar'])
        ->name('coach.suscripciones.cancelar');

    /*
    |--------------------------------------------------------------------------
    | Pagos
    |--------------------------------------------------------------------------
    */
    Route::get('/pagos', [ControladorPago::class, 'index'])
        ->name('coach.pagos.index');
    
    Route::post('/pagos', [ControladorPago::class, 'almacenar'])
        ->name('coach.pagos.almacenar');
    
    Route::get('/pagos/{id}', [ControladorPago::class, 'mostrar'])
        ->name('coach.pagos.mostrar');

    /*
    |--------------------------------------------------------------------------
    | Ejercicios
    |--------------------------------------------------------------------------
    */
    Route::get('/ejercicios', [ControladorEjercicio::class, 'index'])
        ->name('coach.ejercicios.index');
    
    Route::post('/ejercicios', [ControladorEjercicio::class, 'almacenar'])
        ->name('coach.ejercicios.almacenar');
    
    Route::get('/ejercicios/{id}', [ControladorEjercicio::class, 'mostrar'])
        ->name('coach.ejercicios.mostrar');
    
    Route::put('/ejercicios/{id}', [ControladorEjercicio::class, 'actualizar'])
        ->name('coach.ejercicios.actualizar');
    
    Route::delete('/ejercicios/{id}', [ControladorEjercicio::class, 'eliminar'])
        ->name('coach.ejercicios.eliminar');
    
    Route::post('/ejercicios/{id}/video', [ControladorEjercicio::class, 'subirVideo'])
        ->name('coach.ejercicios.video');

    /*
    |--------------------------------------------------------------------------
    | Rutinas
    |--------------------------------------------------------------------------
    */
    Route::get('/rutinas', [ControladorRutina::class, 'index'])
        ->name('coach.rutinas.index');
    
    Route::post('/rutinas', [ControladorRutina::class, 'almacenar'])
        ->name('coach.rutinas.almacenar');
    
    Route::get('/rutinas/{id}', [ControladorRutina::class, 'mostrar'])
        ->name('coach.rutinas.mostrar');
    
    Route::put('/rutinas/{id}', [ControladorRutina::class, 'actualizar'])
        ->name('coach.rutinas.actualizar');
    
    Route::delete('/rutinas/{id}', [ControladorRutina::class, 'eliminar'])
        ->name('coach.rutinas.eliminar');
    
    Route::post('/rutinas/{id}/duplicar', [ControladorRutina::class, 'duplicar'])
        ->name('coach.rutinas.duplicar');
    
    Route::post('/rutinas/{id}/ejercicios', [ControladorRutina::class, 'agregarEjercicio'])
        ->name('coach.rutinas.agregar-ejercicio');
    
    Route::put('/rutinas/{id}/ejercicios/{ejercicioId}', [ControladorRutina::class, 'actualizarEjercicio'])
        ->name('coach.rutinas.actualizar-ejercicio');
    
    Route::delete('/rutinas/{id}/ejercicios/{ejercicioId}', [ControladorRutina::class, 'quitarEjercicio'])
        ->name('coach.rutinas.quitar-ejercicio');
    
    Route::post('/rutinas/{id}/asignar', [ControladorRutina::class, 'asignar'])
        ->name('coach.rutinas.asignar');
    
    Route::delete('/rutinas/{id}/desasignar/{clienteId}', [ControladorRutina::class, 'desasignar'])
        ->name('coach.rutinas.desasignar');

    /*
    |--------------------------------------------------------------------------
    | Dietas
    |--------------------------------------------------------------------------
    */
    Route::get('/dietas', [ControladorDieta::class, 'index'])
        ->name('coach.dietas.index');
    
    Route::post('/dietas', [ControladorDieta::class, 'almacenar'])
        ->name('coach.dietas.almacenar');
    
    Route::get('/dietas/{id}', [ControladorDieta::class, 'mostrar'])
        ->name('coach.dietas.mostrar');
    
    Route::put('/dietas/{id}', [ControladorDieta::class, 'actualizar'])
        ->name('coach.dietas.actualizar');
    
    Route::delete('/dietas/{id}', [ControladorDieta::class, 'eliminar'])
        ->name('coach.dietas.eliminar');
    
    Route::get('/dietas/{id}/download', [ControladorDieta::class, 'descargar'])
        ->name('coach.dietas.descargar');
    
    Route::get('/dietas/{id}/ver', [ControladorDieta::class, 'ver'])
        ->name('coach.dietas.ver');
    
    Route::post('/dietas/subir-varios', [ControladorDieta::class, 'subirVarios'])
        ->name('coach.dietas.subir-varios');

    /*
    |--------------------------------------------------------------------------
    | Parámetros de Evaluación
    |--------------------------------------------------------------------------
    */
    Route::get('/parametros', [ControladorParametro::class, 'index'])
        ->name('coach.parametros.index');
    
    Route::post('/parametros', [ControladorParametro::class, 'almacenar'])
        ->name('coach.parametros.almacenar');
    
    Route::put('/parametros/{id}', [ControladorParametro::class, 'actualizar'])
        ->name('coach.parametros.actualizar');
    
    Route::delete('/parametros/{id}', [ControladorParametro::class, 'eliminar'])
        ->name('coach.parametros.eliminar');

    /*
    |--------------------------------------------------------------------------
    | Evaluaciones
    |--------------------------------------------------------------------------
    */
    Route::get('/evaluaciones', [ControladorEvaluacion::class, 'index'])
        ->name('coach.evaluaciones.index');
    
    Route::post('/evaluaciones', [ControladorEvaluacion::class, 'almacenar'])
        ->name('coach.evaluaciones.almacenar');
    
    Route::get('/evaluaciones/{id}', [ControladorEvaluacion::class, 'mostrar'])
        ->name('coach.evaluaciones.mostrar');
    
    Route::put('/evaluaciones/{id}', [ControladorEvaluacion::class, 'actualizar'])
        ->name('coach.evaluaciones.actualizar');
    
    Route::delete('/evaluaciones/{id}', [ControladorEvaluacion::class, 'eliminar'])
        ->name('coach.evaluaciones.eliminar');
    
    Route::post('/evaluaciones/{id}/parametros', [ControladorEvaluacion::class, 'agregarParametro'])
        ->name('coach.evaluaciones.agregar-parametro');
    
    Route::post('/evaluaciones/{id}/fotos', [ControladorEvaluacion::class, 'subirFoto'])
        ->name('coach.evaluaciones.subir-foto');
    
    Route::delete('/evaluaciones/{id}/fotos/{fotoId}', [ControladorEvaluacion::class, 'eliminarFoto'])
        ->name('coach.evaluaciones.eliminar-foto');

    /*
    |--------------------------------------------------------------------------
    | Formularios
    |--------------------------------------------------------------------------
    */
    Route::get('/formularios', [ControladorFormulario::class, 'index'])
        ->name('coach.formularios.index');
    
    Route::post('/formularios', [ControladorFormulario::class, 'almacenar'])
        ->name('coach.formularios.almacenar');
    
    Route::get('/formularios/{id}', [ControladorFormulario::class, 'mostrar'])
        ->name('coach.formularios.mostrar');
    
    Route::put('/formularios/{id}', [ControladorFormulario::class, 'actualizar'])
        ->name('coach.formularios.actualizar');
    
    Route::delete('/formularios/{id}', [ControladorFormulario::class, 'eliminar'])
        ->name('coach.formularios.eliminar');
    
    Route::post('/formularios/{id}/enviar', [ControladorFormulario::class, 'enviar'])
        ->name('coach.formularios.enviar');
    
    Route::get('/formularios/{id}/respuestas', [ControladorFormulario::class, 'respuestas'])
        ->name('coach.formularios.respuestas');
    
    Route::post('/clientes/{cliente}/formularios/{formulario}/responder', [ControladorFormulario::class, 'responderPorCliente'])
        ->name('coach.clientes.formularios.responder');

    /*
    |--------------------------------------------------------------------------
    | Parámetros del Cliente
    |--------------------------------------------------------------------------
    */
    Route::get('/clientes/{cliente}/parametros', [ControladorParametroCliente::class, 'index'])
        ->name('coach.clientes.parametros.index');
    
    Route::post('/clientes/{cliente}/parametros', [ControladorParametroCliente::class, 'almacenar'])
        ->name('coach.clientes.parametros.almacenar');
    
    Route::put('/parametros-cliente/{id}', [ControladorParametroCliente::class, 'actualizar'])
        ->name('coach.parametros-cliente.actualizar');
    
    Route::delete('/parametros-cliente/{id}', [ControladorParametroCliente::class, 'eliminar'])
        ->name('coach.parametros-cliente.eliminar');

    /*
    |--------------------------------------------------------------------------
    | Chat
    |--------------------------------------------------------------------------
    */
    Route::get('/chats', [ControladorChat::class, 'index'])
        ->name('coach.chats.index');
    
    Route::post('/chats', [ControladorChat::class, 'almacenar'])
        ->name('coach.chats.almacenar');
    
    Route::get('/chats/{id}', [ControladorChat::class, 'mostrar'])
        ->name('coach.chats.mostrar');
    
    Route::get('/chats/{id}/mensajes', [ControladorChat::class, 'mensajes'])
        ->name('coach.chats.mensajes');
    
    Route::post('/chats/{id}/mensajes', [ControladorChat::class, 'enviarMensaje'])
        ->name('coach.chats.enviar-mensaje');
    
    Route::put('/chats/{id}/leer', [ControladorChat::class, 'marcarLeido'])
        ->name('coach.chats.marcar-leido');
    
    Route::get('/chats/{id}/archivos/{archivoId}', [ControladorChat::class, 'descargarArchivo'])
        ->name('coach.chats.descargar-archivo');

    /*
    |--------------------------------------------------------------------------
    | Productos (Tienda)
    |--------------------------------------------------------------------------
    */
    Route::get('/productos', [ControladorProducto::class, 'index'])
        ->name('coach.productos.index');
    
    Route::post('/productos', [ControladorProducto::class, 'almacenar'])
        ->name('coach.productos.almacenar');
    
    Route::get('/productos/{id}', [ControladorProducto::class, 'mostrar'])
        ->name('coach.productos.mostrar');
    
    Route::put('/productos/{id}', [ControladorProducto::class, 'actualizar'])
        ->name('coach.productos.actualizar');
    
    Route::delete('/productos/{id}', [ControladorProducto::class, 'eliminar'])
        ->name('coach.productos.eliminar');
    
    Route::put('/productos/{id}/stock', [ControladorProducto::class, 'ajustarStock'])
        ->name('coach.productos.ajustar-stock');

    /*
    |--------------------------------------------------------------------------
    | Órdenes (Tienda)
    |--------------------------------------------------------------------------
    */
    Route::get('/ordenes', [ControladorOrden::class, 'index'])
        ->name('coach.ordenes.index');
    
    Route::get('/ordenes/{id}', [ControladorOrden::class, 'mostrar'])
        ->name('coach.ordenes.mostrar');
    
    Route::put('/ordenes/{id}/estado', [ControladorOrden::class, 'cambiarEstado'])
        ->name('coach.ordenes.cambiar-estado');
});
