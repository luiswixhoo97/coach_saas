<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParametroClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'parametro_id' => $this->parametro_id,
            'valor' => $this->valor,
            'fecha' => $this->fecha->format('Y-m-d'),
            'notas' => $this->notas,
            'parametro' => $this->whenLoaded('parametro', function () {
                return [
                    'id' => $this->parametro->id,
                    'nombre' => $this->parametro->nombre,
                    'unidad_medida' => $this->parametro->unidad_medida,
                    'tipo_dato' => $this->parametro->tipo_dato,
                ];
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
