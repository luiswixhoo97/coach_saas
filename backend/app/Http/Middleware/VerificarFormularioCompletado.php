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
        
        // No bloquear el acceso si hay formulario pendiente
        // El cliente puede usar todas las funcionalidades
        // El contenedor de advertencia en el perfil le recordará que debe completarlo
        // Solo permitir acceso a perfil y formulario pendiente si se solicita explícitamente
        $ruta = $request->route()?->getName();
        if ($ruta && (
            str_starts_with($ruta, 'cliente.perfil') || 
            $ruta === 'cliente.formulario-pendiente' ||
            str_starts_with($ruta, 'cliente.formulario-pendiente')
        )) {
            return $next($request);
        }
        
        // Permitir acceso a todas las rutas, no bloquear
        return $next($request);
    }
}
