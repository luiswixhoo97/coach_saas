<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuscripcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d'),
            'dias_restantes' => $this->diasRestantes(),
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'pagos' => PagoResource::collection($this->whenLoaded('pagos')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
