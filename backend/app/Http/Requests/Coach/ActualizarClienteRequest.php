<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|string|max:255',
            'apellido_paterno' => 'sometimes|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'sexo' => 'nullable|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'altura' => 'nullable|numeric|min:0|max:300',
            'objetivo' => 'nullable|string',
            'pendiente_actualizacion' => 'sometimes|boolean',
            'semanas_entre_evaluaciones' => 'nullable|integer|min:1|max:52',
        ];
    }

    public function messages(): array
    {
        return [
            'sexo.in' => 'El sexo debe ser: masculino, femenino u otro.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'altura.numeric' => 'La altura debe ser un número.',
            'altura.min' => 'La altura no puede ser negativa.',
            'altura.max' => 'La altura no puede exceder 300 cm.',
        ];
    }
}
