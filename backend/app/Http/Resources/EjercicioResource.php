<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EjercicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'grupo_muscular' => $this->grupo_muscular,
            'video_url' => $this->video_url,
            // Datos del pivot cuando viene de una rutina
            'series' => $this->whenPivotLoaded('rutina_ejercicios', fn() => $this->pivot->series),
            'repeticiones' => $this->whenPivotLoaded('rutina_ejercicios', fn() => $this->pivot->repeticiones),
            'descanso_segundos' => $this->whenPivotLoaded('rutina_ejercicios', fn() => $this->pivot->descanso_segundos),
            'bloque' => $this->whenPivotLoaded('rutina_ejercicios', fn() => $this->pivot->bloque),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
