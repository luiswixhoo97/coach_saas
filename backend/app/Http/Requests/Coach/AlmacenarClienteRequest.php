<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'sexo' => 'nullable|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'altura' => 'nullable|numeric|min:0|max:300',
            'objetivo' => 'nullable|string',
            'password' => 'nullable|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'nombre.required' => 'El nombre es requerido.',
            'apellido_paterno.required' => 'El apellido paterno es requerido.',
            'sexo.in' => 'El sexo debe ser: masculino, femenino u otro.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'altura.numeric' => 'La altura debe ser un número.',
            'altura.min' => 'La altura no puede ser negativa.',
            'altura.max' => 'La altura no puede exceder 300 cm.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
