<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchivoMensajeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'nombre_original' => $this->nombre_original,
            'ruta' => $this->ruta,
            'url' => $this->obtenerUrl(),
            'tamaño' => $this->tamaño,
            'tamaño_formateado' => $this->obtenerTamañoFormateado(),
            'mime_type' => $this->mime_type,
            'es_imagen' => $this->esImagen(),
            'es_documento' => $this->esDocumento(),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}





