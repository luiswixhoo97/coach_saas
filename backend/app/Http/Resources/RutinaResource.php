<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutinaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'nivel' => $this->nivel,
            'objetivo' => $this->objetivo,
            'clonada_de' => $this->clonada_de,
            'ejercicios' => EjercicioResource::collection($this->whenLoaded('ejercicios')),
            'clientes_asignados' => $this->when(
                $this->relationLoaded('clientesAsignados'),
                fn() => $this->clientesAsignados->map(fn($rc) => [
                    'id' => $rc->cliente->id,
                    'email' => $rc->cliente->usuario->email ?? null,
                ])
            ),
            'clientes_asignados_count' => $this->when(
                isset($this->clientes_asignados_count),
                $this->clientes_asignados_count
            ),
            'ejercicios_count' => $this->when(
                isset($this->rutina_ejercicios_count),
                $this->rutina_ejercicios_count
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
