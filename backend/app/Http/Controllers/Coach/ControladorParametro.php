<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\AlmacenarParametroRequest;
use App\Http\Requests\Coach\ActualizarParametroRequest;
use App\Http\Resources\ParametroResource;
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
            'datos' => $parametros->map(function ($p) use ($coach) {
                $resource = new ParametroResource($p);
                $data = $resource->toArray(request());
                $data['editable'] = !$p->es_predeterminado && $p->coach_id === $coach->id;
                return $data;
            }),
        ]);
    }

    public function almacenar(AlmacenarParametroRequest $request): JsonResponse
    {
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
            'datos' => new ParametroResource($parametro),
        ], 201);
    }

    public function actualizar(ActualizarParametroRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $parametro = Parametro::where('coach_id', $coach->id)
            ->where('es_predeterminado', false)
            ->findOrFail($id);

        $parametro->update($request->only(['nombre', 'unidad_medida', 'tipo_dato']));

        return response()->json([
            'mensaje' => 'Parámetro actualizado correctamente.',
            'datos' => new ParametroResource($parametro),
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
