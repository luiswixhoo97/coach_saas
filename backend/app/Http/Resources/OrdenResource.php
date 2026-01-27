<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total' => $this->total,
            'estado' => $this->estado,
            'fecha' => $this->fecha->format('Y-m-d H:i'),
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'items' => $this->when(
                $this->relationLoaded('items'),
                fn() => $this->items->map(fn($i) => [
                    'id' => $i->id,
                    'producto' => new ProductoResource($i->producto),
                    'cantidad' => $i->cantidad,
                    'precio' => $i->precio,
                    'subtotal' => $i->subtotal(),
                ])
            ),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn() => $this->items->count()
            ),
            'pago' => $this->when(
                $this->relationLoaded('pago') && $this->pago,
                fn() => [
                    'metodo' => $this->pago->metodo_pago,
                    'monto' => $this->pago->monto,
                ]
            ),
            'pagado' => $this->when(
                method_exists($this->resource, 'estaPagada'),
                fn() => $this->estaPagada()
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
