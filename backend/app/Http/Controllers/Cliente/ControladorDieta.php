<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\DietaCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorDieta extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function mostrar(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $suscripcionActiva = $cliente->suscripcionActiva();

        if (!$suscripcionActiva) {
            return response()->json([
                'mensaje' => 'No tienes una suscripción activa.',
            ], 404);
        }

        $dietas = DietaCliente::where('suscripcion_id', $suscripcionActiva->id)
            ->where('activo', true)
            ->latest()
            ->get();

        if ($dietas->isEmpty()) {
            return response()->json([
                'mensaje' => 'No tienes dietas asignadas.',
            ], 404);
        }

        $datos = $dietas->map(function ($dieta) {
            return [
                'id' => $dieta->id,
                'archivo' => $dieta->archivo,
                'nombre' => basename($dieta->archivo),
                'url' => Storage::disk('public')->url($dieta->archivo),
                'created_at' => $dieta->created_at->format('Y-m-d'),
            ];
        })->values();

        return response()->json([
            'datos' => $datos,
        ]);
    }

    public function ver(Request $request, int $id)
    {
        $cliente = $this->getCliente($request);

        $suscripcionActiva = $cliente->suscripcionActiva();

        if (!$suscripcionActiva) {
            return response()->json([
                'mensaje' => 'No tienes una suscripción activa.',
            ], 404);
        }

        $dieta = DietaCliente::where('suscripcion_id', $suscripcionActiva->id)
            ->where('id', $id)
            ->where('activo', true)
            ->firstOrFail();

        $path = Storage::disk('public')->path($dieta->archivo);
        
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($dieta->archivo) . '"',
        ]);
    }

    public function descargar(Request $request, int $id = null)
    {
        $cliente = $this->getCliente($request);

        $suscripcionActiva = $cliente->suscripcionActiva();

        if (!$suscripcionActiva) {
            return response()->json([
                'mensaje' => 'No tienes una suscripción activa.',
            ], 404);
        }

        if ($id) {
            // Descargar archivo específico por ID
            $dieta = DietaCliente::where('suscripcion_id', $suscripcionActiva->id)
                ->where('id', $id)
                ->where('activo', true)
                ->firstOrFail();
        } else {
            // Descargar el más reciente (compatibilidad hacia atrás)
            $dieta = DietaCliente::where('suscripcion_id', $suscripcionActiva->id)
                ->where('activo', true)
                ->latest()
                ->firstOrFail();
        }

        return Storage::disk('public')->download($dieta->archivo);
    }
}
