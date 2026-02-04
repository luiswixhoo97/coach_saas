<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\DietaCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        try {
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
            
            if (!file_exists($path)) {
                Log::error("Archivo de dieta no encontrado: {$path}", [
                    'dieta_id' => $id,
                    'archivo' => $dieta->archivo,
                ]);
                
                return response()->json([
                    'mensaje' => 'El archivo de dieta no existe en el servidor.',
                ], 404);
            }
            
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($dieta->archivo) . '"',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Dieta no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error al mostrar dieta: " . $e->getMessage(), [
                'dieta_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'mensaje' => 'Error al cargar la dieta. Por favor, intenta más tarde.',
            ], 500);
        }
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
