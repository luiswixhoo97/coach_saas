<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\ActualizarEjercicioRequest;
use App\Http\Requests\Coach\AlmacenarEjercicioRequest;
use App\Http\Resources\EjercicioResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\Ejercicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorEjercicio extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $query = Ejercicio::where('coach_id', $coach->id);

        if ($request->has('grupo_muscular')) {
            $query->where('grupo_muscular', $request->grupo_muscular);
        }

        if ($request->has('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }

        $ejercicios = $query->orderBy('nombre')->paginate(15);

        return new PaginacionCollection($ejercicios, EjercicioResource::class);
    }

    public function almacenar(AlmacenarEjercicioRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $ejercicio = Ejercicio::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'grupo_muscular' => $request->grupo_muscular,
            'video_url' => $request->video_url,
        ]);

        return response()->json([
            'mensaje' => 'Ejercicio creado correctamente.',
            'datos' => new EjercicioResource($ejercicio),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $ejercicio = Ejercicio::where('coach_id', $coach->id)->findOrFail($id);

        return response()->json(['datos' => new EjercicioResource($ejercicio)]);
    }

    public function actualizar(ActualizarEjercicioRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $ejercicio = Ejercicio::where('coach_id', $coach->id)->findOrFail($id);

        $ejercicio->update($request->only(['nombre', 'grupo_muscular', 'video_url']));

        return response()->json([
            'mensaje' => 'Ejercicio actualizado correctamente.',
            'datos' => new EjercicioResource($ejercicio),
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $ejercicio = Ejercicio::where('coach_id', $coach->id)->findOrFail($id);

        $ejercicio->delete();

        return response()->json(['mensaje' => 'Ejercicio eliminado correctamente.']);
    }

    public function subirVideo(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'video' => 'required|mimetypes:video/mp4,video/avi,video/mpeg|max:102400',
        ], [
            'video.max' => 'El video no debe superar 100MB.',
        ]);

        $coach = $this->getCoach($request);

        $ejercicio = Ejercicio::where('coach_id', $coach->id)->findOrFail($id);

        $path = $request->file('video')->store('ejercicios/videos', 'public');

        $ejercicio->update(['video_url' => Storage::disk('public')->url($path)]);

        return response()->json([
            'mensaje' => 'Video subido correctamente.',
            'datos' => ['video_url' => $ejercicio->video_url],
        ]);
    }
}
