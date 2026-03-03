<?php

namespace App\Http\Requests\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudRegistrarDispositivo extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:500'],
            'plataforma' => ['nullable', 'string', 'in:android,ios'],
            'nombre' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'El token FCM es obligatorio.',
        ];
    }
}
