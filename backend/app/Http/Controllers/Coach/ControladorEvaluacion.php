<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\AlmacenarEvaluacionRequest;
use App\Http\Resources\EvaluacionResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\Evaluacion;
use App\Models\FotoEvaluacion;
use App\Models\ParametroEvaluacion;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorEvaluacion extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $evaluaciones = Evaluacion::with(['suscripcion.cliente.usuario'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return new PaginacionCollection($evaluaciones, EvaluacionResource::class);
    }

    public function almacenar(AlmacenarEvaluacionRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($request->suscripcion_id);

        $evaluacion = Evaluacion::create([
            'suscripcion_id' => $suscripcion->id,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'ubicacion_o_link' => $request->ubicacion_o_link,
            'modo' => $request->modo,
            'estado' => $request->estado ?? 'agendada',
            'fuente' => $request->fuente,
            'notas' => $request->notas,
        ]);

        return response()->json([
            'mensaje' => 'Evaluación creada correctamente.',
            'datos' => new EvaluacionResource($evaluacion),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $evaluacion = Evaluacion::with([
            'suscripcion.cliente.usuario',
            'parametrosEvaluacion.parametro',
            'fotos',
        ])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return response()->json([
            'datos' => new EvaluacionResource($evaluacion),
        ]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $evaluacion = Evaluacion::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $request->validate([
            'fecha' => 'sometimes|date',
            'hora' => 'sometimes|date_format:H:i',
            'ubicacion_o_link' => 'nullable|string|max:500',
            'modo' => 'sometimes|in:presencial,online',
            'estado' => 'sometimes|in:agendada,confirmada,reagendar,cancelada,completada',
            'fuente' => 'sometimes|string|max:100',
            'notas' => 'nullable|string',
        ]);

        $evaluacion->update($request->only(['fecha', 'hora', 'ubicacion_o_link', 'modo', 'estado', 'fuente', 'notas']));

        return response()->json([
            'mensaje' => 'Evaluación actualizada correctamente.',
            'datos' => $evaluacion,
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $evaluacion = Evaluacion::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        // Eliminar fotos
        foreach ($evaluacion->fotos as $foto) {
            Storage::disk('public')->delete($foto->url);
        }

        $evaluacion->delete();

        return response()->json(['mensaje' => 'Evaluación eliminada correctamente.']);
    }

    public function agregarParametro(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'parametro_id' => 'required|exists:parametros,id',
            'valor' => 'required|string',
            'notas' => 'nullable|string',
        ]);

        $coach = $this->getCoach($request);

        $evaluacion = Evaluacion::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        ParametroEvaluacion::updateOrCreate(
            ['evaluacion_id' => $evaluacion->id, 'parametro_id' => $request->parametro_id],
            [
                'valor' => $request->valor,
                'notas' => $request->notas,
            ]
        );

        return response()->json(['mensaje' => 'Parámetro agregado correctamente.']);
    }

    public function subirFoto(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'foto' => 'required|image|max:5120',
            'tipo' => 'required|string|max:50',
        ]);

        $coach = $this->getCoach($request);

        $evaluacion = Evaluacion::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $path = $request->file('foto')->store('evaluaciones/fotos', 'public');

        $foto = FotoEvaluacion::create([
            'evaluacion_id' => $evaluacion->id,
            'tipo' => $request->tipo,
            'url' => $path,
        ]);

        return response()->json([
            'mensaje' => 'Foto subida correctamente.',
            'datos' => [
                'id' => $foto->id,
                'url' => Storage::disk('public')->url($path),
            ],
        ], 201);
    }

    public function eliminarFoto(Request $request, int $id, int $fotoId): JsonResponse
    {
        $coach = $this->getCoach($request);

        $evaluacion = Evaluacion::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $foto = FotoEvaluacion::where('evaluacion_id', $evaluacion->id)->findOrFail($fotoId);

        Storage::disk('public')->delete($foto->url);
        $foto->delete();

        return response()->json(['mensaje' => 'Foto eliminada correctamente.']);
    }
}
