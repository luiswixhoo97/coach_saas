<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'precio' => $this->precio,
            'duracion_dias' => $this->duracion_dias,
            'stripe_price_id' => $this->stripe_price_id,
            'coach' => new CoachResource($this->whenLoaded('coach')),
            'suscripciones_count' => $this->when(
                isset($this->suscripciones_count),
                $this->suscripciones_count
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
