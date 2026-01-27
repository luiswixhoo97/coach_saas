<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'stock' => $this->stock,
            'stock_minimo' => $this->stock_minimo,
            'tipo' => $this->tipo,
            'disponible' => $this->tieneStock(),
            'stock_bajo' => $this->when(
                method_exists($this->resource, 'tieneStockBajo'),
                fn() => $this->tieneStockBajo()
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
