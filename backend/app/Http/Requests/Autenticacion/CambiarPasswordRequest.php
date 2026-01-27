<?php

namespace App\Http\Requests\Autenticacion;

use Illuminate\Foundation\Http\FormRequest;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual' => 'required|string',
            'password_nuevo' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required' => 'La contraseña actual es requerida.',
            'password_nuevo.required' => 'La nueva contraseña es requerida.',
            'password_nuevo.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_nuevo.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
