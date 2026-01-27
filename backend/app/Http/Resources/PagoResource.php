<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'referencia' => $this->referencia,
            'fecha' => $this->fecha->format('Y-m-d H:i'),
            'suscripcion' => new SuscripcionResource($this->whenLoaded('suscripcion')),
        ];
    }
}
