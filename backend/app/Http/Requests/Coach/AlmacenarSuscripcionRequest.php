<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarSuscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|exists:clientes,id',
            'plan_id' => 'required|exists:planes,id',
            'fecha_inicio' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es requerido.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'plan_id.required' => 'El plan es requerido.',
            'plan_id.exists' => 'El plan seleccionado no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
        ];
    }
}
