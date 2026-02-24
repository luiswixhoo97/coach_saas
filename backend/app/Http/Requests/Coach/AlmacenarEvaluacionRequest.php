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
            'hora' => 'required|date_format:H:i',
            'ubicacion_o_link' => 'nullable|string|max:500',
            'modo' => 'required|in:presencial,online',
            'estado' => 'nullable|in:agendada,confirmada,reagendar,cancelada,completada',
            'fuente' => 'nullable|string|max:100',
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
            'hora.required' => 'La hora es requerida.',
            'hora.date_format' => 'La hora debe tener el formato HH:MM (ej: 14:30).',
            'ubicacion_o_link.max' => 'La ubicación o link no puede exceder 500 caracteres.',
            'modo.required' => 'El modo es requerido.',
            'modo.in' => 'El modo debe ser: presencial u online.',
            'estado.in' => 'El estado debe ser: agendada, confirmada, reagendar, cancelada o completada.',
            'fuente.max' => 'La fuente no puede exceder 100 caracteres.',
        ];
    }
}
