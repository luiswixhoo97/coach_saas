<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarFormularioCompletado
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cliente = $request->user()?->cliente;
        
        if (!$cliente || !$cliente->activo) {
            return $next($request);
        }
        
        // Permitir acceso a perfil y formulario pendiente
        $ruta = $request->route()?->getName();
        if ($ruta && (
            str_starts_with($ruta, 'cliente.perfil') || 
            $ruta === 'cliente.formulario-pendiente' ||
            str_starts_with($ruta, 'cliente.formulario-pendiente')
        )) {
            return $next($request);
        }
        
        if ($cliente->tieneFormularioPendiente()) {
            return response()->json([
                'mensaje' => 'Debes completar el formulario pendiente antes de acceder.',
                'redirigir' => '/cliente/formulario-pendiente',
            ], 403);
        }
        
        return $next($request);
    }
}
