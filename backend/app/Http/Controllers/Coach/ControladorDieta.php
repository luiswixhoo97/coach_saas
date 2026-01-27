<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\DietaCliente;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorDieta extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dietas = DietaCliente::with(['suscripcion.cliente.usuario'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $dietas->map(fn($d) => [
                'id' => $d->id,
                'archivo' => $d->archivo,
                'activo' => $d->activo,
                'cliente' => $d->suscripcion->cliente->usuario->email,
                'created_at' => $d->created_at->format('Y-m-d'),
            ]),
            'meta' => [
                'total' => $dietas->total(),
                'por_pagina' => $dietas->perPage(),
                'pagina_actual' => $dietas->currentPage(),
            ],
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'suscripcion_id' => 'required|exists:suscripciones,id',
            'archivo' => 'required|file|mimes:pdf|max:10240',
        ]);

        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($request->suscripcion_id);

        // Desactivar dietas anteriores
        DietaCliente::where('suscripcion_id', $suscripcion->id)->update(['activo' => false]);

        $path = $request->file('archivo')->store('dietas', 'public');

        $dieta = DietaCliente::create([
            'suscripcion_id' => $suscripcion->id,
            'archivo' => $path,
            'activo' => true,
        ]);

        return response()->json([
            'mensaje' => 'Dieta subida correctamente.',
            'datos' => $dieta,
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::with(['suscripcion.cliente.usuario'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return response()->json(['datos' => $dieta]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $request->validate([
            'activo' => 'sometimes|boolean',
        ]);

        $dieta->update($request->only(['activo']));

        return response()->json([
            'mensaje' => 'Dieta actualizada correctamente.',
            'datos' => $dieta,
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        Storage::disk('public')->delete($dieta->archivo);
        $dieta->delete();

        return response()->json(['mensaje' => 'Dieta eliminada correctamente.']);
    }

    public function descargar(Request $request, int $id)
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return Storage::disk('public')->download($dieta->archivo);
    }
}
