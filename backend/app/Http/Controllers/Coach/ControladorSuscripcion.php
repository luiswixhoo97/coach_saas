<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorSuscripcion extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $suscripciones = Suscripcion::with(['cliente.usuario', 'plan'])
            ->whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $suscripciones->map(fn($s) => [
                'id' => $s->id,
                'cliente' => [
                    'id' => $s->cliente->id,
                    'email' => $s->cliente->usuario->email,
                ],
                'plan' => $s->plan->nombre,
                'estado' => $s->estado,
                'fecha_inicio' => $s->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $s->fecha_fin->format('Y-m-d'),
                'dias_restantes' => $s->diasRestantes(),
            ]),
            'meta' => [
                'total' => $suscripciones->total(),
                'por_pagina' => $suscripciones->perPage(),
                'pagina_actual' => $suscripciones->currentPage(),
            ],
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'plan_id' => 'required|exists:planes,id',
            'fecha_inicio' => 'required|date',
        ]);

        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenece al coach
        $cliente = Cliente::where('creado_por', $coach->id)->findOrFail($request->cliente_id);
        
        // Verificar que el plan pertenece al coach
        $plan = Plan::where('coach_id', $coach->id)->findOrFail($request->plan_id);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio);
        $fechaFin = $fechaInicio->copy()->addDays($plan->duracion_dias);

        $suscripcion = Suscripcion::create([
            'cliente_id' => $cliente->id,
            'plan_id' => $plan->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => 'activa',
        ]);

        return response()->json([
            'mensaje' => 'Suscripción creada correctamente.',
            'datos' => $suscripcion->load(['cliente.usuario', 'plan']),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::with(['cliente.usuario', 'plan', 'pagos'])
            ->whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return response()->json(['datos' => $suscripcion]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $request->validate([
            'estado' => 'sometimes|in:activa,pausada,cancelada,vencida',
            'fecha_fin' => 'sometimes|date',
        ]);

        $suscripcion->update($request->only(['estado', 'fecha_fin']));

        return response()->json([
            'mensaje' => 'Suscripción actualizada correctamente.',
            'datos' => $suscripcion,
        ]);
    }

    public function renovar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::with('plan')
            ->whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $nuevaFechaFin = $suscripcion->fecha_fin->addDays($suscripcion->plan->duracion_dias);

        $suscripcion->update([
            'fecha_fin' => $nuevaFechaFin,
            'estado' => 'activa',
        ]);

        return response()->json([
            'mensaje' => 'Suscripción renovada correctamente.',
            'datos' => $suscripcion,
        ]);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $suscripcion->update(['estado' => 'cancelada']);

        return response()->json([
            'mensaje' => 'Suscripción cancelada correctamente.',
        ]);
    }
}
