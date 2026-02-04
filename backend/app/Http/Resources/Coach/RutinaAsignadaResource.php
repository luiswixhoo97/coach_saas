<?php

namespace App\Http\Resources\Coach;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RutinaAsignadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rutina = $this->whenLoaded('rutina') ? $this->rutina : null;
        
        // Contar ejercicios si la rutina está cargada
        $ejerciciosCount = 0;
        if ($rutina && $rutina->relationLoaded('ejercicios')) {
            $ejerciciosCount = $rutina->ejercicios->count();
        } elseif ($rutina) {
            // Si no está cargada, hacer un count directo
            $ejerciciosCount = $rutina->ejercicios()->count();
        }
        
        return [
            'id' => $this->id,
            'rutina_id' => $this->rutina_id,
            'cliente_id' => $this->cliente_id,
            'nombre' => $rutina ? $rutina->nombre : null,
            'nivel' => $rutina ? $rutina->nivel : null,
            'objetivo' => $rutina ? $rutina->objetivo : null,
            'dias' => $this->dia ?? [],
            'ejercicios_count' => $ejerciciosCount,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}

