<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudAlmacenarParametroCliente extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'parametro_id' => 'required|exists:parametros,id',
            'valor' => 'required|string|max:255',
            'fecha' => 'nullable|date',
            'notas' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'evaluacion_id.required' => 'La evaluación es requerida.',
            'evaluacion_id.exists' => 'La evaluación seleccionada no existe.',
            'parametro_id.required' => 'El parámetro es requerido.',
            'parametro_id.exists' => 'El parámetro seleccionado no existe.',
            'valor.required' => 'El valor es requerido.',
            'valor.max' => 'El valor no puede exceder 255 caracteres.',
            'fecha.date' => 'La fecha debe ser válida.',
        ];
    }
}
