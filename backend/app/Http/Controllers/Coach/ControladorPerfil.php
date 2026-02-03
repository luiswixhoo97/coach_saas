<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\DietaCliente;
use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorPerfil extends Controller
{
    /**
     * Obtener perfil del coach autenticado.
     */
    public function mostrar(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'mensaje' => 'Perfil de coach no encontrado.',
            ], 404);
        }

        return response()->json([
            'datos' => [
                'id' => $coach->id,
                'nombre' => $coach->nombre,
                'bio' => $coach->bio,
                'avatar' => $coach->avatar,
                'activo' => $coach->activo,
                'email' => $request->user()->email,
            ],
        ]);
    }

    /**
     * Actualizar perfil del coach.
     */
    public function actualizar(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $coach = $request->user()->coach;

        $coach->update($request->only(['nombre', 'bio']));

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente.',
            'datos' => [
                'id' => $coach->id,
                'nombre' => $coach->nombre,
                'bio' => $coach->bio,
                'avatar' => $coach->avatar,
            ],
        ]);
    }

    /**
     * Subir avatar del coach.
     */
    public function subirAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ], [
            'avatar.required' => 'La imagen es requerida.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.max' => 'La imagen no debe superar 2MB.',
        ]);

        $coach = $request->user()->coach;

        // Eliminar avatar anterior si existe
        if ($coach->avatar) {
            Storage::disk('public')->delete($coach->avatar);
        }

        // Guardar nuevo avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $coach->update(['avatar' => $path]);

        return response()->json([
            'mensaje' => 'Avatar actualizado correctamente.',
            'datos' => [
                'avatar' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Dashboard con métricas del coach.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        $clientesActivos = Cliente::where('creado_por', $coach->id)
            ->where('activo', true)
            ->count();

        $clientesTotal = Cliente::where('creado_por', $coach->id)->count();

        $suscripcionesActivas = Suscripcion::whereHas('cliente', fn($q) => 
            $q->where('creado_por', $coach->id)
        )->where('estado', 'activa')->count();

        $ingresosMes = Pago::whereHas('suscripcion.plan', fn($q) => 
            $q->where('coach_id', $coach->id)
        )
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->sum('monto');

        $clientesConDieta = DietaCliente::clientesConDietaCount($coach->id);

        $clientesVencimientoProximo = Cliente::delCoach($coach->id)
            ->whereHas('suscripciones', fn($q) => $q->venceProximo(30))
            ->count();

        return response()->json([
            'datos' => [
                'clientes' => [
                    'activos' => $clientesActivos,
                    'total' => $clientesTotal,
                    'con_dieta' => $clientesConDieta,
                    'vencimiento_proximo' => $clientesVencimientoProximo,
                ],
                'suscripciones_activas' => $suscripcionesActivas,
                'ingresos_mes' => $ingresosMes,
            ],
        ]);
    }
}
