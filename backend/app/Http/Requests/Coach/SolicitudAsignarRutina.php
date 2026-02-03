<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudAsignarRutina extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'required|exists:clientes,id',
            'dias' => 'nullable|array',
            'dias.*' => 'required|string|in:lunes,martes,miércoles,jueves,viernes,sábado,domingo',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_ids.required' => 'Debes seleccionar al menos un cliente.',
            'cliente_ids.array' => 'Los clientes deben ser un array.',
            'cliente_ids.min' => 'Debes seleccionar al menos un cliente.',
            'cliente_ids.*.exists' => 'Uno o más clientes seleccionados no existen.',
            'dias.array' => 'Los días deben ser un array.',
            'dias.*.in' => 'Los días válidos son: lunes, martes, miércoles, jueves, viernes, sábado, domingo.',
        ];
    }
}

