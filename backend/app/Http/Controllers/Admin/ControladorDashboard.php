<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorDashboard extends Controller
{
    /**
     * Dashboard con estadísticas generales.
     */
    public function index(): JsonResponse
    {
        $estadisticas = [
            'coaches' => [
                'total' => Coach::count(),
                'activos' => Coach::where('activo', true)->count(),
            ],
            'clientes' => [
                'total' => Cliente::count(),
                'activos' => Cliente::where('activo', true)->count(),
            ],
            'suscripciones' => [
                'activas' => Suscripcion::where('estado', 'activa')->count(),
                'vencidas' => Suscripcion::where('fecha_fin', '<', now())->count(),
            ],
            'ingresos' => [
                'mes_actual' => Pago::whereMonth('fecha', now()->month)
                    ->whereYear('fecha', now()->year)
                    ->sum('monto'),
                'mes_anterior' => Pago::whereMonth('fecha', now()->subMonth()->month)
                    ->whereYear('fecha', now()->subMonth()->year)
                    ->sum('monto'),
            ],
        ];

        return response()->json([
            'datos' => $estadisticas,
        ]);
    }

    /**
     * Reporte de ingresos por periodo.
     */
    public function reporteIngresos(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth();
        $fechaFin = $request->fecha_fin ?? now()->endOfMonth();

        $pagos = Pago::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->with('suscripcion.cliente', 'suscripcion.plan.coach')
            ->orderBy('fecha', 'desc')
            ->get();

        $resumen = [
            'total' => $pagos->sum('monto'),
            'cantidad' => $pagos->count(),
            'por_metodo' => $pagos->groupBy('metodo_pago')->map(fn($grupo) => [
                'cantidad' => $grupo->count(),
                'total' => $grupo->sum('monto'),
            ]),
        ];

        return response()->json([
            'datos' => [
                'resumen' => $resumen,
                'pagos' => $pagos->map(fn($pago) => [
                    'id' => $pago->id,
                    'monto' => $pago->monto,
                    'metodo_pago' => $pago->metodo_pago,
                    'fecha' => $pago->fecha->format('Y-m-d H:i'),
                    'cliente' => $pago->suscripcion->cliente->usuario->email ?? null,
                    'coach' => $pago->suscripcion->plan->coach->nombre ?? null,
                ]),
            ],
        ]);
    }

    /**
     * Listar todos los pagos de la plataforma.
     */
    public function pagos(Request $request): JsonResponse
    {
        $pagos = Pago::with('suscripcion.cliente.usuario', 'suscripcion.plan.coach')
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $pagos->map(fn($pago) => [
                'id' => $pago->id,
                'monto' => $pago->monto,
                'metodo_pago' => $pago->metodo_pago,
                'referencia' => $pago->referencia,
                'fecha' => $pago->fecha->format('Y-m-d H:i'),
                'cliente' => [
                    'id' => $pago->suscripcion->cliente->id ?? null,
                    'email' => $pago->suscripcion->cliente->usuario->email ?? null,
                ],
                'coach' => [
                    'id' => $pago->suscripcion->plan->coach->id ?? null,
                    'nombre' => $pago->suscripcion->plan->coach->nombre ?? null,
                ],
            ]),
            'meta' => [
                'total' => $pagos->total(),
                'por_pagina' => $pagos->perPage(),
                'pagina_actual' => $pagos->currentPage(),
                'ultima_pagina' => $pagos->lastPage(),
            ],
        ]);
    }
}
