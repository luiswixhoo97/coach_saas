<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Parametro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorParametro extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Obtener parámetros predeterminados + personalizados del coach
        $parametros = Parametro::where(function ($q) use ($coach) {
            $q->where('es_predeterminado', true)
              ->orWhere('coach_id', $coach->id);
        })->orderBy('nombre')->get();

        return response()->json([
            'datos' => $parametros->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'unidad_medida' => $p->unidad_medida,
                'tipo_dato' => $p->tipo_dato,
                'es_predeterminado' => $p->es_predeterminado,
                'editable' => !$p->es_predeterminado && $p->coach_id === $coach->id,
            ]),
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'unidad_medida' => 'required|string|max:50',
            'tipo_dato' => 'required|in:numero,texto,booleano',
        ]);

        $coach = $this->getCoach($request);

        $parametro = Parametro::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'unidad_medida' => $request->unidad_medida,
            'tipo_dato' => $request->tipo_dato,
            'es_predeterminado' => false,
        ]);

        return response()->json([
            'mensaje' => 'Parámetro creado correctamente.',
            'datos' => $parametro,
        ], 201);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $parametro = Parametro::where('coach_id', $coach->id)
            ->where('es_predeterminado', false)
            ->findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'unidad_medida' => 'sometimes|string|max:50',
            'tipo_dato' => 'sometimes|in:numero,texto,booleano',
        ]);

        $parametro->update($request->only(['nombre', 'unidad_medida', 'tipo_dato']));

        return response()->json([
            'mensaje' => 'Parámetro actualizado correctamente.',
            'datos' => $parametro,
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $parametro = Parametro::where('coach_id', $coach->id)
            ->where('es_predeterminado', false)
            ->findOrFail($id);

        $parametro->delete();

        return response()->json(['mensaje' => 'Parámetro eliminado correctamente.']);
    }
}
