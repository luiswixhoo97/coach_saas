<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarClienteActivo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cliente = $request->user()?->cliente;
        
        if (!$cliente) {
            return response()->json([
                'mensaje' => 'Cliente no encontrado.',
            ], 404);
        }
        
        // Permitir acceso a perfil siempre
        $ruta = $request->route()?->getName();
        if ($ruta && (str_starts_with($ruta, 'cliente.perfil') || $ruta === 'cliente.perfil.mostrar' || $ruta === 'cliente.perfil.actualizar')) {
            return $next($request);
        }
        
        if (!$cliente->activo) {
            return response()->json([
                'mensaje' => 'Tu cuenta está inactiva. Solo puedes ver tu perfil hasta que el coach active tu cuenta.',
                'redirigir' => '/cliente/perfil',
            ], 403);
        }
        
        return $next($request);
    }
}
