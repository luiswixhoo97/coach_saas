<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\AlmacenarFormularioRequest;
use App\Http\Requests\Coach\ActualizarFormularioRequest;
use App\Http\Requests\Coach\SolicitudResponderFormularioPorCliente;
use App\Http\Resources\FormularioResource;
use App\Models\Cliente;
use App\Models\Formulario;
use App\Models\FormularioRespuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorFormulario extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formularios = Formulario::withCount('respuestas')
            ->where('coach_id', $coach->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => FormularioResource::collection($formularios),
            'meta' => [
                'total' => $formularios->total(),
                'por_pagina' => $formularios->perPage(),
                'pagina_actual' => $formularios->currentPage(),
            ],
        ]);
    }

    public function almacenar(AlmacenarFormularioRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'preguntas' => $request->preguntas,
            'activo' => true,
        ]);

        return response()->json([
            'mensaje' => 'Formulario creado correctamente.',
            'datos' => new FormularioResource($formulario),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        return response()->json([
            'datos' => new FormularioResource($formulario),
        ]);
    }

    public function actualizar(ActualizarFormularioRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $formulario->update($request->only(['nombre', 'preguntas', 'activo']));

        return response()->json([
            'mensaje' => 'Formulario actualizado correctamente.',
            'datos' => new FormularioResource($formulario),
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $formulario->delete();

        return response()->json(['mensaje' => 'Formulario eliminado correctamente.']);
    }

    public function enviar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'exists:clientes,id',
        ]);

        // TODO: Implementar notificación a clientes
        return response()->json([
            'mensaje' => 'Formulario enviado a los clientes seleccionados.',
        ]);
    }

    public function respuestas(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $respuestas = FormularioRespuesta::with(['cliente.usuario'])
            ->where('formulario_id', $formulario->id)
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $respuestas->map(fn($r) => [
                'id' => $r->id,
                'cliente' => $r->cliente->usuario->email,
                'fecha' => $r->fecha->format('Y-m-d H:i'),
                'respuestas' => $r->respuestas,
            ]),
            'meta' => [
                'total' => $respuestas->total(),
                'por_pagina' => $respuestas->perPage(),
                'pagina_actual' => $respuestas->currentPage(),
            ],
        ]);
    }

    /**
     * Permite al coach llenar un formulario en nombre del cliente.
     */
    public function responderPorCliente(SolicitudResponderFormularioPorCliente $request, int $cliente, int $formulario): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenezca al coach
        $clienteModel = Cliente::where('creado_por', $coach->id)
            ->findOrFail($cliente);

        // Verificar que el formulario pertenezca al coach
        $formularioModel = Formulario::where('coach_id', $coach->id)
            ->findOrFail($formulario);

        // Crear o actualizar respuesta
        $respuesta = FormularioRespuesta::updateOrCreate(
            [
                'formulario_id' => $formularioModel->id,
                'cliente_id' => $clienteModel->id,
            ],
            [
                'fecha' => now(),
                'respuestas' => $request->respuestas,
            ]
        );

        return response()->json([
            'mensaje' => 'Formulario completado correctamente.',
            'datos' => [
                'id' => $respuesta->id,
                'formulario_id' => $respuesta->formulario_id,
                'cliente_id' => $respuesta->cliente_id,
                'fecha' => $respuesta->fecha->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
