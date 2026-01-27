<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'duracion_dias' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del plan es requerido.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'precio.required' => 'El precio es requerido.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
            'duracion_dias.required' => 'La duración es requerida.',
            'duracion_dias.integer' => 'La duración debe ser un número entero.',
            'duracion_dias.min' => 'La duración debe ser al menos 1 día.',
        ];
    }
}
