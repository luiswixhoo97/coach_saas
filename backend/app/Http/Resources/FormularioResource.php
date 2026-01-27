<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormularioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'preguntas' => $this->preguntas,
            'activo' => $this->activo,
            'preguntas_count' => $this->cantidadPreguntas(),
            'respuestas_count' => $this->when(
                isset($this->respuestas_count),
                $this->respuestas_count
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
