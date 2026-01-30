<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActualizarCoachRequest;
use App\Http\Requests\Admin\AlmacenarCoachRequest;
use App\Http\Resources\CoachResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\Coach;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ControladorCoach extends Controller
{
    /**
     * Listar todos los coaches.
     */
    public function index(Request $request): PaginacionCollection
    {
        $query = Coach::with('usuario');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhereHas('usuario', fn($q) => $q->where('email', 'like', "%{$buscar}%"));
            });
        }

        $coaches = $query->orderBy('created_at', 'desc')->paginate(15);

        return new PaginacionCollection($coaches, CoachResource::class);
    }

    /**
     * Crear nuevo coach.
     */
    public function almacenar(AlmacenarCoachRequest $request): JsonResponse
    {
        // Generar contraseña si no se proporciona
        $password = $request->password ?? Str::random(12);

        $coach = DB::transaction(function () use ($request, $password) {
            // Crear usuario
            $usuario = User::create([
                'email' => $request->email,
                'password' => Hash::make($password),
                'rol' => 'coach',
                'activo' => true,
            ]);

            // Crear perfil de coach
            $coach = Coach::create([
                'usuario_id' => $usuario->id,
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'bio' => $request->bio,
                'activo' => true,
            ]);

            return $coach;
        });

        $coach->load('usuario');

        return response()->json([
            'mensaje' => 'Coach creado correctamente.',
            'datos' => new CoachResource($coach),
            'credenciales' => [
                'email' => $request->email,
                'password' => $password,
            ],
        ], 201);
    }

    /**
     * Mostrar detalle de un coach.
     */
    public function mostrar(int $id): JsonResponse
    {
        $coach = Coach::with(['usuario', 'clientes', 'planes'])
            ->findOrFail($id);

        return response()->json([
            'datos' => new CoachResource($coach),
        ]);
    }

    /**
     * Actualizar datos del coach.
     */
    public function actualizar(ActualizarCoachRequest $request, int $id): JsonResponse
    {
        $coach = Coach::findOrFail($id);

        DB::transaction(function () use ($request, $coach) {
            // Actualizar usuario si se proporciona email
            if ($request->has('email')) {
                $coach->usuario->update(['email' => $request->email]);
            }

            // Actualizar coach
            $coach->update($request->only(['nombre', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'bio']));
        });

        $coach->load('usuario');

        return response()->json([
            'mensaje' => 'Coach actualizado correctamente.',
            'datos' => new CoachResource($coach),
        ]);
    }

    /**
     * Activar/desactivar coach.
     */
    public function toggleEstado(int $id): JsonResponse
    {
        $coach = Coach::findOrFail($id);

        DB::transaction(function () use ($coach) {
            $nuevoEstado = !$coach->activo;
            
            $coach->update(['activo' => $nuevoEstado]);
            $coach->usuario->update(['activo' => $nuevoEstado]);
        });

        $coach->load('usuario');

        return response()->json([
            'mensaje' => $coach->activo 
                ? 'Coach activado correctamente.' 
                : 'Coach desactivado correctamente.',
            'datos' => new CoachResource($coach),
        ]);
    }

    /**
     * Eliminar coach (soft delete).
     */
    public function eliminar(int $id): JsonResponse
    {
        $coach = Coach::findOrFail($id);

        DB::transaction(function () use ($coach) {
            $coach->usuario->delete();
            $coach->delete();
        });

        return response()->json([
            'mensaje' => 'Coach eliminado correctamente.',
        ]);
    }
}
