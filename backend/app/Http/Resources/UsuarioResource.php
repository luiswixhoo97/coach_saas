<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'rol' => $this->rol,
            'activo' => $this->activo,
            'perfil' => $this->when($this->esCoach(), fn() => new CoachResource($this->coach)),
            'perfil' => $this->when($this->esCliente(), fn() => new ClienteResource($this->cliente)),
        ];
    }
}
