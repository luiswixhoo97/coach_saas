<?php

namespace App\Http\Controllers\Dispositivos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dispositivos\SolicitudRegistrarDispositivo;
use App\Models\Dispositivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorDispositivo extends Controller
{
    /**
     * Registrar o actualizar el token FCM del dispositivo del usuario autenticado.
     */
    public function registrar(SolicitudRegistrarDispositivo $request): JsonResponse
    {
        $user = $request->user();

        Dispositivo::updateOrCreate(
            [
                'user_id' => $user->id,
                'fcm_token' => $request->input('fcm_token'),
            ],
            [
                'plataforma' => $request->input('plataforma'),
                'nombre' => $request->input('nombre'),
            ]
        );

        return response()->json([
            'mensaje' => 'Dispositivo registrado correctamente.',
        ], 200);
    }
}
