<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha->format('Y-m-d'),
            'modo' => $this->modo,
            'fuente' => $this->fuente,
            'notas' => $this->notas,
            'cliente' => $this->when(
                $this->relationLoaded('suscripcion') && $this->suscripcion->relationLoaded('cliente'),
                fn() => $this->suscripcion->cliente->usuario->email ?? null
            ),
            'parametros' => $this->when(
                $this->relationLoaded('parametrosEvaluacion'),
                fn() => $this->parametrosEvaluacion->map(fn($pe) => [
                    'id' => $pe->id,
                    'nombre' => $pe->parametro->nombre,
                    'valor' => $pe->valor,
                    'unidad' => $pe->parametro->unidad_medida,
                ])
            ),
            'fotos' => FotoEvaluacionResource::collection($this->whenLoaded('fotos')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
