<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarRutinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|string|max:255',
            'nivel' => 'sometimes|in:principiante,intermedio,avanzado',
            'objetivo' => 'sometimes|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'nivel.in' => 'El nivel debe ser: principiante, intermedio o avanzado.',
            'objetivo.max' => 'El objetivo no puede exceder 255 caracteres.',
        ];
    }
}
