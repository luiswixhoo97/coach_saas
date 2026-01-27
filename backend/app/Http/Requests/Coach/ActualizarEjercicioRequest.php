<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarEjercicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|string|max:255',
            'grupo_muscular' => 'sometimes|string|max:100',
            'video_url' => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'grupo_muscular.max' => 'El grupo muscular no puede exceder 100 caracteres.',
            'video_url.url' => 'La URL del video debe ser válida.',
        ];
    }
}
