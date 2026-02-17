<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudResponderFormularioPorCliente extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'respuestas' => 'required|array',
            'respuestas.*' => 'nullable', // Permitir valores null
        ];
    }

    public function messages(): array
    {
        return [
            'respuestas.required' => 'Las respuestas del formulario son requeridas.',
            'respuestas.array' => 'Las respuestas deben ser un arreglo.',
        ];
    }
}
