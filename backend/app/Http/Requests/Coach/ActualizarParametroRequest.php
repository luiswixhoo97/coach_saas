<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarParametroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|string|max:255',
            'unidad_medida' => 'sometimes|string|max:50',
            'tipo_dato' => 'sometimes|in:numero,texto,booleano',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'unidad_medida.max' => 'La unidad de medida no puede exceder 50 caracteres.',
            'tipo_dato.in' => 'El tipo de dato debe ser: numero, texto o booleano.',
        ];
    }
}

