<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\AlmacenarEvaluacionRequest;
use App\Http\Resources\EvaluacionResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\Evaluacion;
use App\Models\FotoEvaluacion;
use App\Models\ParametroCliente;
use App\Models\ParametroEvaluacion;
use App\Models\Suscripcion;
use App\Models\Ubicacion;
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

        $evaluaciones = Evaluacion::with(['suscripcion.cliente.usuario', 'ubicacion'])
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

        $ubicacionId = $this->resolveUbicacionId($request);
        $ubicacion = $ubicacionId ? Ubicacion::find($ubicacionId) : null;

        $evaluacion = Evaluacion::create([
            'suscripcion_id' => $suscripcion->id,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'ubicacion_id' => $ubicacionId,
            'ubicacion_o_link' => $ubicacion ? $ubicacion->link_google_maps : $request->ubicacion_o_link,
            'direccion' => $ubicacion ? $ubicacion->direccion : $request->direccion,
            'modo' => $request->modo,
            'estado' => $request->estado ?? 'agendada',
            'notas' => $request->notas,
        ]);

        $evaluacion->load('ubicacion');

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
            'ubicacion',
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
            'ubicacion_id' => 'nullable|exists:ubicaciones,id',
            'ubicacion' => 'nullable|array',
            'ubicacion.link_google_maps' => 'required_with:ubicacion|string|max:500',
            'ubicacion.nombre' => 'nullable|string|max:255',
            'ubicacion.direccion' => 'nullable|string|max:500',
            'ubicacion_o_link' => 'nullable|string|max:500',
            'direccion' => 'nullable|string|max:500',
            'modo' => 'sometimes|in:presencial,online',
            'estado' => 'sometimes|in:agendada,confirmada,reagendar,cancelada,completada',
            'notas' => 'nullable|string',
        ]);

        $ubicacionId = $this->resolveUbicacionId($request);
        $ubicacion = $ubicacionId ? Ubicacion::find($ubicacionId) : null;

        $evaluacion->update([
            'fecha' => $request->input('fecha', $evaluacion->fecha),
            'hora' => $request->input('hora', $evaluacion->hora),
            'ubicacion_id' => $ubicacionId,
            'ubicacion_o_link' => $ubicacion ? $ubicacion->link_google_maps : $request->ubicacion_o_link,
            'direccion' => $ubicacion ? $ubicacion->direccion : $request->direccion,
            'modo' => $request->input('modo', $evaluacion->modo),
            'estado' => $request->input('estado', $evaluacion->estado),
            'notas' => $request->input('notas', $evaluacion->notas),
        ]);

        $evaluacion->load('ubicacion');

        return response()->json([
            'mensaje' => 'Evaluación actualizada correctamente.',
            'datos' => new EvaluacionResource($evaluacion),
        ]);
    }

    /**
     * Resolver ubicacion_id desde request: usar ubicacion_id si viene, o hacer upsert desde objeto ubicacion.
     */
    private function resolveUbicacionId(Request $request): ?int
    {
        if ($request->filled('ubicacion_id')) {
            return (int) $request->ubicacion_id;
        }

        $ubicacionInput = $request->input('ubicacion');
        if (!is_array($ubicacionInput) || empty($ubicacionInput['link_google_maps'])) {
            return null;
        }

        $ubicacion = Ubicacion::updateOrCreate(
            ['link_google_maps' => $ubicacionInput['link_google_maps']],
            [
                'nombre' => $ubicacionInput['nombre'] ?? '',
                'direccion' => $ubicacionInput['direccion'] ?? '',
            ]
        );

        return $ubicacion->id;
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

        $evaluacion = Evaluacion::with('suscripcion')->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        ParametroEvaluacion::updateOrCreate(
            ['evaluacion_id' => $evaluacion->id, 'parametro_id' => $request->parametro_id],
            [
                'valor' => $request->valor,
                'notas' => $request->notas,
            ]
        );

        $clienteId = $evaluacion->suscripcion->cliente_id;
        ParametroCliente::updateOrCreate(
            [
                'cliente_id' => $clienteId,
                'evaluacion_id' => $evaluacion->id,
                'parametro_id' => $request->parametro_id,
            ],
            [
                'valor' => $request->valor,
                'fecha' => $evaluacion->fecha,
                'notas' => $request->notas,
            ]
        );

        $evaluacion->update(['estado' => 'completada']);

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
