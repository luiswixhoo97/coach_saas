<?php

namespace App\Http\Resources\Coach;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutinaAsignadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rutina = $this->whenLoaded('rutina') ? $this->rutina : null;
        
        return [
            'id' => $this->id,
            'rutina_id' => $this->rutina_id,
            'cliente_id' => $this->cliente_id,
            'nombre' => $rutina ? $rutina->nombre : null,
            'nivel' => $rutina ? $rutina->nivel : null,
            'objetivo' => $rutina ? $rutina->objetivo : null,
            'dias' => $this->dia ?? [],
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}

