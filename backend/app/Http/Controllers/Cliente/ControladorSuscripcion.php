<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorSuscripcion extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function mostrar(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $suscripcion = $cliente->suscripcionActiva();

        if (!$suscripcion) {
            return response()->json([
                'mensaje' => 'No tienes una suscripción activa.',
                'datos' => null,
            ]);
        }

        $suscripcion->load('plan');

        return response()->json([
            'datos' => [
                'id' => $suscripcion->id,
                'plan' => [
                    'id' => $suscripcion->plan->id,
                    'nombre' => $suscripcion->plan->nombre,
                    'precio' => $suscripcion->plan->precio,
                ],
                'estado' => $suscripcion->estado,
                'fecha_inicio' => $suscripcion->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $suscripcion->fecha_fin->format('Y-m-d'),
                'dias_restantes' => $suscripcion->diasRestantes(),
            ],
        ]);
    }

    public function pagos(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $pagos = $cliente->suscripciones()
            ->with(['pagos', 'plan'])
            ->get()
            ->flatMap(fn($s) => $s->pagos->map(fn($p) => [
                'id' => $p->id,
                'monto' => $p->monto,
                'metodo_pago' => $p->metodo_pago,
                'fecha' => $p->fecha->format('Y-m-d'),
                'plan' => $s->plan->nombre,
            ]))
            ->sortByDesc('fecha')
            ->values();

        return response()->json([
            'datos' => $pagos,
        ]);
    }

    public function crearIntentoPago(Request $request): JsonResponse
    {
        // TODO: Implementar integración con Stripe
        return response()->json([
            'mensaje' => 'Integración con Stripe pendiente de implementar.',
        ], 501);
    }
}
