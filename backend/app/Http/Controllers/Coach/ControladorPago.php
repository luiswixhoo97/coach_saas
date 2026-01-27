<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorPago extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $pagos = Pago::with(['suscripcion.cliente.usuario', 'suscripcion.plan'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $pagos->map(fn($p) => [
                'id' => $p->id,
                'monto' => $p->monto,
                'metodo_pago' => $p->metodo_pago,
                'referencia' => $p->referencia,
                'fecha' => $p->fecha->format('Y-m-d H:i'),
                'cliente' => $p->suscripcion->cliente->usuario->email,
                'plan' => $p->suscripcion->plan->nombre,
            ]),
            'meta' => [
                'total' => $pagos->total(),
                'por_pagina' => $pagos->perPage(),
                'pagina_actual' => $pagos->currentPage(),
            ],
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'suscripcion_id' => 'required|exists:suscripciones,id',
            'monto' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:efectivo,transferencia,stripe',
            'referencia' => 'nullable|string|max:255',
        ]);

        $coach = $this->getCoach($request);

        // Verificar que la suscripción pertenece a un plan del coach
        $suscripcion = Suscripcion::whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($request->suscripcion_id);

        $pago = Pago::create([
            'suscripcion_id' => $suscripcion->id,
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
            'referencia' => $request->referencia,
            'fecha' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Pago registrado correctamente.',
            'datos' => $pago,
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $pago = Pago::with(['suscripcion.cliente.usuario', 'suscripcion.plan'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return response()->json(['datos' => $pago]);
    }
}
