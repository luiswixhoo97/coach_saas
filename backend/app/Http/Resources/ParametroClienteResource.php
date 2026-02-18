<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParametroClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        try {
            $fecha = null;
            if ($this->fecha) {
                try {
                    $fecha = $this->fecha->format('Y-m-d');
                } catch (\Exception $e) {
                    // Si la fecha no es un objeto Carbon, intentar convertirla
                    $fecha = is_string($this->fecha) ? $this->fecha : null;
                }
            }

            $parametroData = null;
            if ($this->relationLoaded('parametro') && $this->parametro) {
                try {
                    $parametroData = [
                        'id' => $this->parametro->id ?? null,
                        'nombre' => $this->parametro->nombre ?? 'Parámetro eliminado',
                        'unidad_medida' => $this->parametro->unidad_medida ?? '',
                        'tipo_dato' => $this->parametro->tipo_dato ?? null,
                    ];
                } catch (\Exception $e) {
                    $parametroData = [
                        'id' => $this->parametro_id,
                        'nombre' => 'Parámetro no disponible',
                        'unidad_medida' => '',
                        'tipo_dato' => null,
                    ];
                }
            }

            return [
                'id' => $this->id,
                'cliente_id' => $this->cliente_id,
                'parametro_id' => $this->parametro_id,
                'valor' => $this->valor,
                'fecha' => $fecha,
                'notas' => $this->notas,
                'parametro' => $parametroData,
                'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            ];
        } catch (\Exception $e) {
            // Fallback en caso de error
            return [
                'id' => $this->id ?? null,
                'cliente_id' => $this->cliente_id ?? null,
                'parametro_id' => $this->parametro_id ?? null,
                'valor' => $this->valor ?? null,
                'fecha' => null,
                'notas' => $this->notas ?? null,
                'parametro' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }
    }
}
