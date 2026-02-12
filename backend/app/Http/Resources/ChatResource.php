<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ultimoMensaje = null;
        if (method_exists($this->resource, 'ultimoMensaje')) {
            $mensaje = $this->ultimoMensaje();
            if ($mensaje) {
                $mensaje->load('archivos');
                $ultimoMensaje = new MensajeResource($mensaje);
            }
        }

        return [
            'id' => $this->id,
            'coach' => new CoachResource($this->whenLoaded('coach')),
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'ultimo_mensaje' => $ultimoMensaje,
            'mensajes' => MensajeResource::collection($this->whenLoaded('mensajes')),
            'mensajes_no_leidos' => $this->when(
                $request->user()?->esCoach(),
                fn() => $this->mensajesNoLeidos('coach')
            ),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
