<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'activo' => $this->activo,
            'email' => $this->whenLoaded('usuario', fn() => $this->usuario->email),
            'estadisticas' => $this->when($this->relationLoaded('clientes'), fn() => [
                'clientes_total' => $this->clientes->count(),
                'clientes_activos' => $this->clientes->where('activo', true)->count(),
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
