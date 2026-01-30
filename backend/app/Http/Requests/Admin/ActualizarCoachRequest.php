<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esAdmin();
    }

    public function rules(): array
    {
        $coachId = $this->route('id');
        
        return [
            'nombre' => 'sometimes|string|max:255',
            'apellido_paterno' => 'sometimes|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'bio' => 'nullable|string',
            'email' => 'sometimes|email|unique:users,email,' . $this->getUsuarioId(),
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
        ];
    }

    private function getUsuarioId(): ?int
    {
        $coach = \App\Models\Coach::find($this->route('id'));
        return $coach?->usuario_id;
    }
}
