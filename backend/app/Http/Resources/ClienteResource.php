<?php

namespace App\Http\Resources;

use App\Http\Resources\Coach\DietaClienteResource;
use App\Http\Resources\Coach\RutinaAsignadaResource;
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
            'formulario_completado' => !$this->tieneFormularioPendiente(),
            'coach' => new CoachResource($this->whenLoaded('coach')),
            'suscripcion_activa' => $this->when(
                $this->suscripciones()->exists(),
                function () {
                    // Obtener la última suscripción (activa o pendiente)
                    $suscripcion = $this->suscripciones()
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($suscripcion) {
                        // Cargar plan si no está cargado
                        if (!$suscripcion->relationLoaded('plan') && $suscripcion->plan_id) {
                            $suscripcion->load('plan');
                        }
                        return new SuscripcionResource($suscripcion);
                    }
                    return null;
                }
            ),
            'rutinas_asignadas' => $this->when(
                $this->relationLoaded('rutinasAsignadas'),
                function () {
                    // Asegurar que todas las rutinas tengan la relación cargada
                    $this->rutinasAsignadas->loadMissing('rutina');
                    return RutinaAsignadaResource::collection($this->rutinasAsignadas);
                }
            ),
            'dietas' => $this->when(
                $this->suscripcionActiva(),
                function () {
                    $suscripcion = $this->suscripcionActiva();
                    // Cargar dietas si no están cargadas
                    if (!$suscripcion->relationLoaded('dietas')) {
                        $suscripcion->load('dietas');
                    }
                    return DietaClienteResource::collection($suscripcion->dietas);
                }
            ),
            'ultima_evaluacion' => $this->when(
                $this->relationLoaded('suscripciones'),
                function () {
                    $evaluaciones = $this->suscripciones->flatMap->evaluaciones;
                    if ($evaluaciones->isEmpty()) {
                        return null;
                    }
                    $ultima = $evaluaciones->sortByDesc('fecha')->first();
                    return [
                        'id' => $ultima->id,
                        'fecha' => $ultima->fecha?->format('Y-m-d'),
                        'hora' => $ultima->hora instanceof \Carbon\Carbon ? $ultima->hora->format('H:i') : $ultima->hora,
                        'estado' => $ultima->estado ?? 'agendada',
                    ];
                }
            ),
            'ultima_dieta' => $this->when(
                $this->relationLoaded('suscripciones'),
                function () {
                    $dietas = $this->suscripciones->flatMap->dietas;
                    if ($dietas->isEmpty()) {
                        return null;
                    }
                    $ultima = $dietas->sortByDesc('created_at')->first();
                    return [
                        'nombre_archivo' => $ultima->archivo ? basename($ultima->archivo) : null,
                        'created_at' => $ultima->created_at->format('Y-m-d H:i'),
                    ];
                }
            ),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
