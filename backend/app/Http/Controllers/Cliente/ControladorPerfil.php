<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorPerfil extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function mostrar(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        if (!$cliente) {
            return response()->json([
                'mensaje' => 'Perfil de cliente no encontrado.',
            ], 404);
        }

        $cliente->load('coach');

        return response()->json([
            'datos' => [
                'id' => $cliente->id,
                'email' => $request->user()->email,
                'sexo' => $cliente->sexo,
                'fecha_nacimiento' => $cliente->fecha_nacimiento?->format('Y-m-d'),
                'edad' => $cliente->edad(),
                'altura' => $cliente->altura,
                'objetivo' => $cliente->objetivo,
                'coach' => $cliente->coach ? [
                    'id' => $cliente->coach->id,
                    'nombre' => $cliente->coach->nombre,
                ] : null,
            ],
        ]);
    }

    public function actualizar(Request $request): JsonResponse
    {
        $request->validate([
            'sexo' => 'nullable|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'altura' => 'nullable|numeric|min:0|max:300',
            'objetivo' => 'nullable|string',
        ]);

        $cliente = $this->getCliente($request);

        $cliente->update($request->only(['sexo', 'fecha_nacimiento', 'altura', 'objetivo']));

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente.',
        ]);
    }
}
