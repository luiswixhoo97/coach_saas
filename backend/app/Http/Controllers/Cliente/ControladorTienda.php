<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use App\Models\OrdenItem;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorTienda extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function productos(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $productos = Producto::where('coach_id', $cliente->creado_por)
            ->conStock()
            ->orderBy('nombre')
            ->paginate(15);

        return response()->json([
            'datos' => $productos->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'precio' => $p->precio,
                'tipo' => $p->tipo,
                'disponible' => $p->tieneStock(),
            ]),
            'meta' => [
                'total' => $productos->total(),
                'por_pagina' => $productos->perPage(),
                'pagina_actual' => $productos->currentPage(),
            ],
        ]);
    }

    public function crearOrden(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        $cliente = $this->getCliente($request);

        $orden = DB::transaction(function () use ($request, $cliente) {
            $total = 0;
            $items = [];

            foreach ($request->items as $item) {
                $producto = Producto::where('coach_id', $cliente->creado_por)
                    ->findOrFail($item['producto_id']);

                if (!$producto->reducirStock($item['cantidad'])) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}");
                }

                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                $items[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $producto->precio,
                ];
            }

            $orden = Orden::create([
                'coach_id' => $cliente->creado_por,
                'cliente_id' => $cliente->id,
                'total' => $total,
                'estado' => 'pendiente',
                'fecha' => now(),
            ]);

            foreach ($items as $item) {
                OrdenItem::create([
                    'orden_id' => $orden->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                ]);
            }

            return $orden;
        });

        return response()->json([
            'mensaje' => 'Orden creada correctamente.',
            'datos' => [
                'id' => $orden->id,
                'total' => $orden->total,
            ],
        ], 201);
    }

    public function ordenes(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $ordenes = Orden::where('cliente_id', $cliente->id)
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $ordenes->map(fn($o) => [
                'id' => $o->id,
                'total' => $o->total,
                'estado' => $o->estado,
                'fecha' => $o->fecha->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'total' => $ordenes->total(),
                'por_pagina' => $ordenes->perPage(),
                'pagina_actual' => $ordenes->currentPage(),
            ],
        ]);
    }

    public function mostrarOrden(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $orden = Orden::with(['items.producto', 'pago'])
            ->where('cliente_id', $cliente->id)
            ->findOrFail($id);

        return response()->json([
            'datos' => [
                'id' => $orden->id,
                'total' => $orden->total,
                'estado' => $orden->estado,
                'fecha' => $orden->fecha->format('Y-m-d H:i'),
                'items' => $orden->items->map(fn($i) => [
                    'producto' => $i->producto->nombre,
                    'cantidad' => $i->cantidad,
                    'precio' => $i->precio,
                    'subtotal' => $i->subtotal(),
                ]),
                'pagado' => $orden->estaPagada(),
            ],
        ]);
    }

    public function pagarOrden(Request $request, int $id): JsonResponse
    {
        // TODO: Implementar integración con Stripe
        return response()->json([
            'mensaje' => 'Integración con Stripe pendiente de implementar.',
        ], 501);
    }
}
