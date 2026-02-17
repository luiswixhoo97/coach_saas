<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudActualizarParametroCliente extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'valor' => 'sometimes|required|string|max:255',
            'fecha' => 'sometimes|required|date',
            'notas' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'El valor es requerido.',
            'valor.max' => 'El valor no puede exceder 255 caracteres.',
            'fecha.required' => 'La fecha es requerida.',
            'fecha.date' => 'La fecha debe ser válida.',
        ];
    }
}
