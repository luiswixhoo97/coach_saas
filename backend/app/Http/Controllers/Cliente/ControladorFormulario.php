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

    public function responder(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $formulario = Formulario::where('coach_id', $cliente->creado_por)
            ->where('activo', true)
            ->findOrFail($id);

        $request->validate([
            'respuestas' => 'required|array',
        ]);

        FormularioRespuesta::updateOrCreate(
            ['formulario_id' => $formulario->id, 'cliente_id' => $cliente->id],
            [
                'fecha' => now(),
                'respuestas' => $request->respuestas,
            ]
        );

        return response()->json([
            'mensaje' => 'Respuestas enviadas correctamente.',
        ]);
    }

    /**
     * Obtener formulario obligatorio pendiente.
     */
    public function formularioPendiente(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);
        
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
