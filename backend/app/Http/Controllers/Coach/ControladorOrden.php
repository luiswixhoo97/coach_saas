<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorOrden extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $query = Orden::with(['cliente.usuario', 'items.producto'])
            ->where('coach_id', $coach->id);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $ordenes = $query->orderBy('fecha', 'desc')->paginate(15);

        return response()->json([
            'datos' => $ordenes->map(fn($o) => [
                'id' => $o->id,
                'cliente' => $o->cliente->usuario->email,
                'total' => $o->total,
                'estado' => $o->estado,
                'fecha' => $o->fecha->format('Y-m-d H:i'),
                'items_count' => $o->items->count(),
            ]),
            'meta' => [
                'total' => $ordenes->total(),
                'por_pagina' => $ordenes->perPage(),
                'pagina_actual' => $ordenes->currentPage(),
            ],
        ]);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $orden = Orden::with(['cliente.usuario', 'items.producto', 'pago'])
            ->where('coach_id', $coach->id)
            ->findOrFail($id);

        return response()->json([
            'datos' => [
                'id' => $orden->id,
                'cliente' => [
                    'id' => $orden->cliente->id,
                    'email' => $orden->cliente->usuario->email,
                ],
                'total' => $orden->total,
                'estado' => $orden->estado,
                'fecha' => $orden->fecha->format('Y-m-d H:i'),
                'items' => $orden->items->map(fn($i) => [
                    'producto' => $i->producto->nombre,
                    'cantidad' => $i->cantidad,
                    'precio' => $i->precio,
                    'subtotal' => $i->subtotal(),
                ]),
                'pago' => $orden->pago ? [
                    'metodo' => $orden->pago->metodo_pago,
                    'monto' => $orden->pago->monto,
                ] : null,
            ],
        ]);
    }

    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estado' => 'required|in:pendiente,procesando,completada,cancelada',
        ]);

        $coach = $this->getCoach($request);

        $orden = Orden::where('coach_id', $coach->id)->findOrFail($id);

        $orden->update(['estado' => $request->estado]);

        return response()->json([
            'mensaje' => 'Estado de la orden actualizado.',
            'datos' => ['estado' => $orden->estado],
        ]);
    }
}
