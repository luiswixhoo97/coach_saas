<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\ActualizarRutinaRequest;
use App\Http\Requests\Coach\AlmacenarRutinaRequest;
use App\Http\Resources\PaginacionCollection;
use App\Http\Resources\RutinaResource;
use App\Models\Rutina;
use App\Models\RutinaCliente;
use App\Models\RutinaEjercicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorRutina extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    /**
     * Listar rutinas del coach.
     */
    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $query = Rutina::withCount('clientesAsignados')
            ->where('coach_id', $coach->id);

        if ($request->has('nivel')) {
            $query->where('nivel', $request->nivel);
        }

        if ($request->has('objetivo')) {
            $query->where('objetivo', $request->objetivo);
        }

        $rutinas = $query->orderBy('created_at', 'desc')->paginate(15);

        return new PaginacionCollection($rutinas, RutinaResource::class);
    }

    /**
     * Crear rutina.
     */
    public function almacenar(AlmacenarRutinaRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'nivel' => $request->nivel,
            'objetivo' => $request->objetivo,
        ]);

        return response()->json([
            'mensaje' => 'Rutina creada correctamente.',
            'datos' => new RutinaResource($rutina),
        ], 201);
    }

    /**
     * Mostrar rutina con ejercicios.
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::with(['ejercicios', 'clientesAsignados.cliente.usuario'])
            ->where('coach_id', $coach->id)
            ->findOrFail($id);

        return response()->json([
            'datos' => new RutinaResource($rutina),
        ]);
    }

    /**
     * Actualizar rutina.
     */
    public function actualizar(ActualizarRutinaRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        $rutina->update($request->only(['nombre', 'nivel', 'objetivo']));

        return response()->json([
            'mensaje' => 'Rutina actualizada correctamente.',
            'datos' => new RutinaResource($rutina),
        ]);
    }

    /**
     * Eliminar rutina.
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        $rutina->delete();

        return response()->json([
            'mensaje' => 'Rutina eliminada correctamente.',
        ]);
    }

    /**
     * Duplicar rutina para personalización.
     */
    public function duplicar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutinaOriginal = Rutina::with('ejercicios')
            ->where('coach_id', $coach->id)
            ->findOrFail($id);

        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        $nuevaRutina = DB::transaction(function () use ($request, $rutinaOriginal, $coach) {
            // Crear copia de la rutina
            $nuevaRutina = Rutina::create([
                'coach_id' => $coach->id,
                'nombre' => $request->nombre ?? $rutinaOriginal->nombre . ' (copia)',
                'nivel' => $rutinaOriginal->nivel,
                'objetivo' => $rutinaOriginal->objetivo,
                'clonada_de' => $rutinaOriginal->id,
            ]);

            // Copiar ejercicios
            foreach ($rutinaOriginal->ejercicios as $ejercicio) {
                RutinaEjercicio::create([
                    'rutina_id' => $nuevaRutina->id,
                    'ejercicio_id' => $ejercicio->id,
                    'series' => $ejercicio->pivot->series,
                    'repeticiones' => $ejercicio->pivot->repeticiones,
                    'descanso_segundos' => $ejercicio->pivot->descanso_segundos,
                    'bloque' => $ejercicio->pivot->bloque,
                ]);
            }

            // Si se especifica cliente, asignar nueva rutina y desasignar original
            if ($request->cliente_id) {
                // Desasignar rutina original del cliente
                RutinaCliente::where('rutina_id', $rutinaOriginal->id)
                    ->where('cliente_id', $request->cliente_id)
                    ->delete();

                // Asignar nueva rutina
                RutinaCliente::create([
                    'rutina_id' => $nuevaRutina->id,
                    'cliente_id' => $request->cliente_id,
                    'dia' => [],
                ]);
            }

            return $nuevaRutina;
        });

        $nuevaRutina->load('ejercicios', 'clientesAsignados.cliente.usuario');

        return response()->json([
            'mensaje' => 'Rutina duplicada correctamente.',
            'datos' => new RutinaResource($nuevaRutina),
        ], 201);
    }

    /**
     * Agregar ejercicio a rutina.
     */
    public function agregarEjercicio(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        $request->validate([
            'ejercicio_id' => 'required|exists:ejercicios,id',
            'series' => 'required|integer|min:1',
            'repeticiones' => 'required|integer|min:1',
            'descanso_segundos' => 'required|integer|min:0',
            'bloque' => 'required|integer|min:1',
        ]);

        RutinaEjercicio::create([
            'rutina_id' => $rutina->id,
            'ejercicio_id' => $request->ejercicio_id,
            'series' => $request->series,
            'repeticiones' => $request->repeticiones,
            'descanso_segundos' => $request->descanso_segundos,
            'bloque' => $request->bloque,
        ]);

        return response()->json([
            'mensaje' => 'Ejercicio agregado a la rutina.',
        ], 201);
    }

    /**
     * Actualizar ejercicio en rutina.
     */
    public function actualizarEjercicio(Request $request, int $id, int $ejercicioId): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        $request->validate([
            'series' => 'sometimes|integer|min:1',
            'repeticiones' => 'sometimes|integer|min:1',
            'descanso_segundos' => 'sometimes|integer|min:0',
            'bloque' => 'sometimes|integer|min:1',
        ]);

        RutinaEjercicio::where('rutina_id', $rutina->id)
            ->where('ejercicio_id', $ejercicioId)
            ->update($request->only(['series', 'repeticiones', 'descanso_segundos', 'bloque']));

        return response()->json([
            'mensaje' => 'Ejercicio actualizado en la rutina.',
        ]);
    }

    /**
     * Quitar ejercicio de rutina.
     */
    public function quitarEjercicio(Request $request, int $id, int $ejercicioId): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        RutinaEjercicio::where('rutina_id', $rutina->id)
            ->where('ejercicio_id', $ejercicioId)
            ->delete();

        return response()->json([
            'mensaje' => 'Ejercicio eliminado de la rutina.',
        ]);
    }

    /**
     * Asignar rutina a cliente(s).
     */
    public function asignar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        $request->validate([
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'exists:clientes,id',
            'dias' => 'nullable|array',
        ]);

        foreach ($request->cliente_ids as $clienteId) {
            RutinaCliente::updateOrCreate(
                ['rutina_id' => $rutina->id, 'cliente_id' => $clienteId],
                ['dia' => $request->dias ?? []]
            );
        }

        return response()->json([
            'mensaje' => 'Rutina asignada correctamente.',
        ]);
    }

    /**
     * Desasignar rutina de un cliente.
     */
    public function desasignar(Request $request, int $id, int $clienteId): JsonResponse
    {
        $coach = $this->getCoach($request);

        $rutina = Rutina::where('coach_id', $coach->id)->findOrFail($id);

        RutinaCliente::where('rutina_id', $rutina->id)
            ->where('cliente_id', $clienteId)
            ->delete();

        return response()->json([
            'mensaje' => 'Rutina desasignada del cliente.',
        ]);
    }
}
