<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
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
            'datos' => $formularios->map(fn($f) => [
                'id' => $f->id,
                'nombre' => $f->nombre,
                'activo' => $f->activo,
                'preguntas_count' => $f->cantidadPreguntas(),
                'respuestas_count' => $f->respuestas_count,
            ]),
            'meta' => [
                'total' => $formularios->total(),
                'por_pagina' => $formularios->perPage(),
                'pagina_actual' => $formularios->currentPage(),
            ],
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string',
            'preguntas.*.tipo' => 'required|in:texto,numero,seleccion,multiple',
            'preguntas.*.opciones' => 'nullable|array',
        ]);

        $coach = $this->getCoach($request);

        $formulario = Formulario::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'preguntas' => $request->preguntas,
            'activo' => true,
        ]);

        return response()->json([
            'mensaje' => 'Formulario creado correctamente.',
            'datos' => $formulario,
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        return response()->json(['datos' => $formulario]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'preguntas' => 'sometimes|array|min:1',
            'activo' => 'sometimes|boolean',
        ]);

        $formulario->update($request->only(['nombre', 'preguntas', 'activo']));

        return response()->json([
            'mensaje' => 'Formulario actualizado correctamente.',
            'datos' => $formulario,
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
}
