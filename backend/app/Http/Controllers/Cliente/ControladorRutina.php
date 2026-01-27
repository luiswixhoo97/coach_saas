<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\RutinaCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorRutina extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function index(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $rutinas = RutinaCliente::with(['rutina.ejercicios'])
            ->where('cliente_id', $cliente->id)
            ->get();

        return response()->json([
            'datos' => $rutinas->map(fn($rc) => [
                'id' => $rc->rutina->id,
                'nombre' => $rc->rutina->nombre,
                'nivel' => $rc->rutina->nivel,
                'objetivo' => $rc->rutina->objetivo,
                'dias' => $rc->dia,
                'ejercicios_count' => $rc->rutina->ejercicios->count(),
            ]),
        ]);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $rutinaCliente = RutinaCliente::with(['rutina.ejercicios'])
            ->where('cliente_id', $cliente->id)
            ->where('rutina_id', $id)
            ->firstOrFail();

        return response()->json([
            'datos' => [
                'id' => $rutinaCliente->rutina->id,
                'nombre' => $rutinaCliente->rutina->nombre,
                'nivel' => $rutinaCliente->rutina->nivel,
                'objetivo' => $rutinaCliente->rutina->objetivo,
                'dias' => $rutinaCliente->dia,
                'ejercicios' => $rutinaCliente->rutina->ejercicios->map(fn($e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'grupo_muscular' => $e->grupo_muscular,
                    'video_url' => $e->video_url,
                    'series' => $e->pivot->series,
                    'repeticiones' => $e->pivot->repeticiones,
                    'descanso_segundos' => $e->pivot->descanso_segundos,
                    'bloque' => $e->pivot->bloque,
                ]),
                'progreso' => [
                    'series_realizadas' => $rutinaCliente->series_realizadas,
                    'reps_realizadas' => $rutinaCliente->reps_realizadas,
                    'peso_usado' => $rutinaCliente->peso_usado,
                ],
            ],
        ]);
    }

    public function registrarEntrenamiento(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'series_realizadas' => 'nullable|integer|min:0',
            'reps_realizadas' => 'nullable|integer|min:0',
            'peso_usado' => 'nullable|numeric|min:0',
        ]);

        $cliente = $this->getCliente($request);

        $rutinaCliente = RutinaCliente::where('cliente_id', $cliente->id)
            ->where('rutina_id', $id)
            ->firstOrFail();

        $rutinaCliente->update($request->only(['series_realizadas', 'reps_realizadas', 'peso_usado']));

        return response()->json([
            'mensaje' => 'Entrenamiento registrado correctamente.',
        ]);
    }
}
