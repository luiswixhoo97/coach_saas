<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarEvaluacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'suscripcion_id' => 'required|exists:suscripciones,id',
            'fecha' => 'required|date',
            'modo' => 'required|in:presencial,online',
            'fuente' => 'required|string|max:100',
            'notas' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'suscripcion_id.required' => 'La suscripción es requerida.',
            'suscripcion_id.exists' => 'La suscripción no existe.',
            'fecha.required' => 'La fecha es requerida.',
            'fecha.date' => 'La fecha debe ser válida.',
            'modo.required' => 'El modo es requerido.',
            'modo.in' => 'El modo debe ser: presencial u online.',
            'fuente.required' => 'La fuente es requerida.',
            'fuente.max' => 'La fuente no puede exceder 100 caracteres.',
        ];
    }
}
