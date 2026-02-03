<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->whenLoaded('usuario', fn() => $this->usuario->email),
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'sexo' => $this->sexo,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'edad' => $this->edad(),
            'altura' => $this->altura,
            'objetivo' => $this->objetivo,
            'activo' => $this->activo,
            'tiene_dieta' => $this->tieneDietaActiva(),
            'coach' => new CoachResource($this->whenLoaded('coach')),
            'suscripcion_activa' => $this->when(
                method_exists($this->resource, 'suscripcionActiva') && $this->suscripcionActiva(),
                fn() => new SuscripcionResource($this->suscripcionActiva())
            ),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
