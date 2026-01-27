<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coach' => new CoachResource($this->whenLoaded('coach')),
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'ultimo_mensaje' => $this->when(
                method_exists($this->resource, 'ultimoMensaje'),
                fn() => $this->ultimoMensaje()?->mensaje
            ),
            'mensajes' => MensajeResource::collection($this->whenLoaded('mensajes')),
            'mensajes_no_leidos' => $this->when(
                $request->user()?->esCoach(),
                fn() => $this->mensajesNoLeidos('coach')
            ),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
