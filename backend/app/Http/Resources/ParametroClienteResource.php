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

            $evaluacionData = null;
            if ($this->relationLoaded('evaluacion') && $this->evaluacion) {
                try {
                    $evaluacionFecha = null;
                    if ($this->evaluacion->fecha) {
                        $evaluacionFecha = $this->evaluacion->fecha instanceof \Carbon\Carbon
                            ? $this->evaluacion->fecha->format('Y-m-d')
                            : (is_string($this->evaluacion->fecha) ? $this->evaluacion->fecha : null);
                    }

                    $evaluacionHora = null;
                    if ($this->evaluacion->hora) {
                        $evaluacionHora = $this->evaluacion->hora instanceof \Carbon\Carbon
                            ? $this->evaluacion->hora->format('H:i')
                            : (is_string($this->evaluacion->hora) ? $this->evaluacion->hora : null);
                    }

                    $hasUbicacion = $this->evaluacion->relationLoaded('ubicacion') && $this->evaluacion->ubicacion;
                    $evaluacionData = [
                        'id' => $this->evaluacion->id ?? null,
                        'fecha' => $evaluacionFecha,
                        'hora' => $evaluacionHora,
                        'modo' => $this->evaluacion->modo ?? null,
                        'estado' => $this->evaluacion->estado ?? null,
                        'ubicacion_o_link' => $hasUbicacion ? $this->evaluacion->ubicacion->link_google_maps : ($this->evaluacion->ubicacion_o_link ?? null),
                        'direccion' => $hasUbicacion ? $this->evaluacion->ubicacion->direccion : ($this->evaluacion->direccion ?? null),
                        'ubicacion' => $hasUbicacion ? [
                            'id' => $this->evaluacion->ubicacion->id,
                            'nombre' => $this->evaluacion->ubicacion->nombre,
                            'direccion' => $this->evaluacion->ubicacion->direccion,
                            'link_google_maps' => $this->evaluacion->ubicacion->link_google_maps,
                        ] : null,
                    ];
                } catch (\Exception $e) {
                    $evaluacionData = [
                        'id' => $this->evaluacion_id ?? null,
                        'fecha' => null,
                        'hora' => null,
                        'modo' => null,
                        'estado' => null,
                        'ubicacion_o_link' => null,
                        'direccion' => null,
                        'ubicacion' => null,
                    ];
                }
            }

            return [
                'id' => $this->id,
                'cliente_id' => $this->cliente_id,
                'evaluacion_id' => $this->evaluacion_id,
                'parametro_id' => $this->parametro_id,
                'valor' => $this->valor,
                'fecha' => $fecha,
                'notas' => $this->notas,
                'parametro' => $parametroData,
                'evaluacion' => $evaluacionData,
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
