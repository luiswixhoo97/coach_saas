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

        $dieta = DietaCliente::where('suscripcion_id', $suscripcionActiva->id)
            ->where('activo', true)
            ->latest()
            ->first();

        if (!$dieta) {
            return response()->json([
                'mensaje' => 'No tienes una dieta asignada.',
            ], 404);
        }

        return response()->json([
            'datos' => [
                'id' => $dieta->id,
                'archivo' => $dieta->archivo,
                'url' => Storage::disk('public')->url($dieta->archivo),
                'created_at' => $dieta->created_at->format('Y-m-d'),
            ],
        ]);
    }

    public function descargar(Request $request)
    {
        $cliente = $this->getCliente($request);

        $suscripcionActiva = $cliente->suscripcionActiva();

        if (!$suscripcionActiva) {
            return response()->json([
                'mensaje' => 'No tienes una suscripción activa.',
            ], 404);
        }

        $dieta = DietaCliente::where('suscripcion_id', $suscripcionActiva->id)
            ->where('activo', true)
            ->latest()
            ->first();

        if (!$dieta) {
            return response()->json([
                'mensaje' => 'No tienes una dieta asignada.',
            ], 404);
        }

        return Storage::disk('public')->download($dieta->archivo);
    }
}
