<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudSubirDietaVarios extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'required|integer|exists:clientes,id',
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|file|mimes:pdf|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_ids.required' => 'Debes seleccionar al menos un cliente.',
            'cliente_ids.array' => 'Los clientes deben ser un array.',
            'cliente_ids.min' => 'Debes seleccionar al menos un cliente.',
            'cliente_ids.*.exists' => 'Uno o más clientes seleccionados no existen.',
            'archivos.required' => 'Debes seleccionar al menos un archivo.',
            'archivos.array' => 'Los archivos deben ser un array.',
            'archivos.min' => 'Debes seleccionar al menos un archivo.',
            'archivos.*.file' => 'Todos los archivos deben ser válidos.',
            'archivos.*.mimes' => 'Los archivos deben ser PDF.',
            'archivos.*.max' => 'Cada archivo no puede superar los 10MB.',
        ];
    }
}

