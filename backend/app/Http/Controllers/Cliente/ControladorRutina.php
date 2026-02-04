<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\RutinaCliente;
use App\Models\Ejercicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function rutinaDelDia(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        // Obtener el día actual en español (lunes, martes, etc.)
        $diasSemana = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miércoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sábado',
            0 => 'domingo',
        ];
        $diaActual = $diasSemana[(int) date('w')];

        // Buscar rutina asignada al cliente que corresponda al día actual
        $rutinaCliente = RutinaCliente::with(['rutina.ejercicios'])
            ->where('cliente_id', $cliente->id)
            ->get()
            ->first(function ($rc) use ($diaActual) {
                $dias = $rc->dia ?? [];
                return is_array($dias) && in_array($diaActual, $dias);
            });

        if (!$rutinaCliente) {
            return response()->json([
                'datos' => null,
                'mensaje' => 'No hay rutina asignada para hoy.',
            ]);
        }

        // Agrupar ejercicios por bloque
        $ejercicios = $rutinaCliente->rutina->ejercicios;
        $bloques = [];
        $bloquesCompletados = 0;

        foreach ($ejercicios as $ejercicio) {
            $bloqueNum = $ejercicio->pivot->bloque ?? 1;
            
            if (!isset($bloques[$bloqueNum])) {
                $bloques[$bloqueNum] = [
                    'numero' => $bloqueNum,
                    'ejercicios' => [],
                    'completado' => false,
                ];
            }

            $bloques[$bloqueNum]['ejercicios'][] = [
                'id' => $ejercicio->id,
                'nombre' => $ejercicio->nombre,
                'grupo_muscular' => $ejercicio->grupo_muscular,
                'video_url' => $ejercicio->video_url,
                'series' => $ejercicio->pivot->series,
                'repeticiones' => $ejercicio->pivot->repeticiones,
                'descanso_segundos' => $ejercicio->pivot->descanso_segundos,
                'nota' => $ejercicio->pivot->nota ?? null,
            ];
        }

        // Ordenar bloques por número y determinar progreso
        ksort($bloques);
        $bloquesArray = array_values($bloques);
        $totalBloques = count($bloquesArray);

        // Calcular progreso: un bloque se considera completado si tiene progreso registrado
        // Por ahora, usamos el progreso general de la rutina para todos los bloques
        // (esto se puede refinar más adelante con progreso por bloque individual)
        if ($rutinaCliente->tieneProgreso()) {
            $bloquesCompletados = $totalBloques; // Si hay progreso general, consideramos todos completados
        }

        return response()->json([
            'datos' => [
                'id' => $rutinaCliente->rutina->id,
                'nombre' => $rutinaCliente->rutina->nombre,
                'nivel' => $rutinaCliente->rutina->nivel,
                'objetivo' => $rutinaCliente->rutina->objetivo,
                'dias' => $rutinaCliente->dia,
                'bloques' => $bloquesArray,
                'progreso' => [
                    'bloques_completados' => $bloquesCompletados,
                    'total_bloques' => $totalBloques,
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

    public function verVideo(Request $request, int $ejercicioId)
    {
        $cliente = $this->getCliente($request);

        // Verificar que el ejercicio pertenezca a una rutina asignada al cliente
        $rutinaCliente = RutinaCliente::where('cliente_id', $cliente->id)
            ->whereHas('rutina.ejercicios', function ($query) use ($ejercicioId) {
                $query->where('ejercicios.id', $ejercicioId);
            })
            ->first();

        if (!$rutinaCliente) {
            return response()->json([
                'mensaje' => 'No tienes acceso a este ejercicio.',
            ], 403);
        }

        $ejercicio = Ejercicio::findOrFail($ejercicioId);

        if (!$ejercicio->video_url) {
            return response()->json([
                'mensaje' => 'Este ejercicio no tiene video.',
            ], 404);
        }

        // Extraer la ruta del archivo desde la URL
        $videoUrl = $ejercicio->video_url;
        $path = null;

        // Si la URL contiene /storage/, extraer la ruta relativa
        if (strpos($videoUrl, '/storage/') !== false) {
            $path = str_replace('/storage/', '', parse_url($videoUrl, PHP_URL_PATH));
        } else {
            // Si es una ruta relativa directa
            $path = $videoUrl;
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json([
                'mensaje' => 'El archivo de video no existe.',
            ], 404);
        }

        $filePath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($filePath) ?: 'video/mp4';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
