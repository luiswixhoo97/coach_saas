<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensajeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emisor_tipo' => $this->emisor_tipo,
            'mensaje' => $this->mensaje,
            'enviado_en' => $this->enviado_en->format('Y-m-d H:i'),
            'leido' => $this->leido,
        ];
    }
}
