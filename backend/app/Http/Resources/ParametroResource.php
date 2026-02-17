<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParametroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'unidad_medida' => $this->unidad_medida,
            'tipo_dato' => $this->tipo_dato,
            'es_predeterminado' => $this->es_predeterminado,
            'editable' => $this->when(
                isset($this->editable),
                $this->editable
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}

