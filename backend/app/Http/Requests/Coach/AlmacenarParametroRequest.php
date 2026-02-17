<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarParametroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'unidad_medida' => 'required|string|max:50',
            'tipo_dato' => 'required|in:numero,texto,booleano',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del parámetro es requerido.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'unidad_medida.required' => 'La unidad de medida es requerida.',
            'unidad_medida.max' => 'La unidad de medida no puede exceder 50 caracteres.',
            'tipo_dato.required' => 'El tipo de dato es requerido.',
            'tipo_dato.in' => 'El tipo de dato debe ser: numero, texto o booleano.',
        ];
    }
}

