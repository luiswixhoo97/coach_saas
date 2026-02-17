<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use App\Models\FormularioRespuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorFormulario extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function index(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        // Obtener formularios del coach del cliente
        $formularios = Formulario::where('coach_id', $cliente->creado_por)
            ->where('activo', true)
            ->get();

        // Marcar cuáles ya fueron respondidos
        $respondidos = FormularioRespuesta::where('cliente_id', $cliente->id)
            ->pluck('formulario_id')
            ->toArray();

        return response()->json([
            'datos' => $formularios->map(fn($f) => [
                'id' => $f->id,
                'nombre' => $f->nombre,
                'preguntas_count' => $f->cantidadPreguntas(),
                'respondido' => in_array($f->id, $respondidos),
            ]),
        ]);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $formulario = Formulario::where('coach_id', $cliente->creado_por)
            ->where('activo', true)
            ->findOrFail($id);

        return response()->json([
            'datos' => [
                'id' => $formulario->id,
                'nombre' => $formulario->nombre,
                'preguntas' => $formulario->preguntas,
            ],
        ]);
    }

    /**
     * Responder el formulario estándar del coach.
     * Permite valores null para preguntas no contestadas.
     */
    public function responder(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);
        
        // Cargar relación del coach
        $cliente->load('coach');
        
        // Usar el formulario estándar del coach (formulario_inicial_id)
        if (!$cliente->coach || !$cliente->coach->tieneFormularioInicial()) {
            return response()->json([
                'mensaje' => 'Tu coach no tiene un formulario estándar configurado.',
            ], 400);
        }

        $formularioEstandar = $cliente->coach->formularioInicial;

        $request->validate([
            'respuestas' => 'required|array',
            'respuestas.*' => 'nullable', // Permitir valores null
        ]);

        // Convertir strings vacíos a null para mantener consistencia
        $respuestas = array_map(function($respuesta) {
            return $respuesta === '' || $respuesta === null ? null : $respuesta;
        }, $request->respuestas);

        FormularioRespuesta::updateOrCreate(
            ['formulario_id' => $formularioEstandar->id, 'cliente_id' => $cliente->id],
            [
                'fecha' => now(),
                'respuestas' => $respuestas,
            ]
        );

        return response()->json([
            'mensaje' => 'Respuestas enviadas correctamente.',
        ]);
    }

    /**
     * Obtener formulario estándar pendiente del coach.
     * Este es el formulario que el coach usa para todos sus clientes.
     */
    public function formularioPendiente(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);
        
        // Cargar la relación del coach para verificar formulario estándar
        $cliente->load('coach.formularioInicial');
        
        $formulario = $cliente->formularioObligatorioPendiente();
        
        if (!$formulario) {
            return response()->json([
                'mensaje' => 'No tienes formularios pendientes.',
                'datos' => null,
            ]);
        }
        
        return response()->json([
            'datos' => [
                'id' => $formulario->id,
                'nombre' => $formulario->nombre,
                'preguntas' => $formulario->preguntas,
            ],
        ]);
    }
}
