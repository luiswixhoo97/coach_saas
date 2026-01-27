<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarRutinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'nivel' => 'required|in:principiante,intermedio,avanzado',
            'objetivo' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la rutina es requerido.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'nivel.required' => 'El nivel es requerido.',
            'nivel.in' => 'El nivel debe ser: principiante, intermedio o avanzado.',
            'objetivo.required' => 'El objetivo es requerido.',
            'objetivo.max' => 'El objetivo no puede exceder 255 caracteres.',
        ];
    }
}
