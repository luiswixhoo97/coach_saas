<?php

namespace App\Http\Resources\Coach;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DietaClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = null;
        if ($this->archivo) {
            // Generar URL completa usando la configuración del disco
            $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
            $url = $baseUrl . '/storage/' . $this->archivo;
        }
        
        return [
            'id' => $this->id,
            'suscripcion_id' => $this->suscripcion_id,
            'archivo' => $this->archivo,
            'url' => $url,
            'nombre_archivo' => $this->archivo ? basename($this->archivo) : null,
            'activo' => $this->activo,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}

