<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hora = null;
        if ($this->hora) {
            $hora = $this->hora instanceof \Carbon\Carbon
                ? $this->hora->format('H:i')
                : (is_string($this->hora) ? $this->hora : null);
        }

        $hasUbicacion = $this->relationLoaded('ubicacion') && $this->ubicacion;

        return [
            'id' => $this->id,
            'fecha' => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'hora' => $hora,
            'ubicacion_id' => $this->ubicacion_id,
            'ubicacion' => $this->when($hasUbicacion, fn() => [
                'id' => $this->ubicacion->id,
                'nombre' => $this->ubicacion->nombre,
                'direccion' => $this->ubicacion->direccion,
                'link_google_maps' => $this->ubicacion->link_google_maps,
            ]),
            'ubicacion_o_link' => $hasUbicacion ? $this->ubicacion->link_google_maps : $this->ubicacion_o_link,
            'direccion' => $hasUbicacion ? $this->ubicacion->direccion : $this->direccion,
            'modo' => $this->modo,
            'estado' => $this->estado ?? 'agendada',
            'notas' => $this->notas,
            'cliente' => $this->when(
                $this->relationLoaded('suscripcion') && $this->suscripcion->relationLoaded('cliente'),
                fn() => $this->suscripcion->cliente->usuario->email ?? null
            ),
            'parametros' => $this->when(
                $this->relationLoaded('parametrosEvaluacion'),
                fn() => $this->parametrosEvaluacion->map(fn($pe) => [
                    'id' => $pe->id,
                    'nombre' => $pe->parametro->nombre ?? 'Parámetro eliminado',
                    'valor' => $pe->valor,
                    'unidad' => $pe->parametro->unidad_medida ?? '',
                    'notas' => $pe->notas,
                ])
            ),
            'fotos' => FotoEvaluacionResource::collection($this->whenLoaded('fotos')),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') : null,
        ];
    }
}
