<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudEnviarMensaje extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización se maneja en el controlador verificando que el chat pertenece al usuario
        return true;
    }

    public function rules(): array
    {
        return [
            'mensaje' => 'nullable|string|max:5000',
            'archivos' => 'nullable|array|max:5', // Máximo 5 archivos por mensaje
            'archivos.*' => 'required|file|max:10240', // 10MB por archivo
        ];
    }

    public function messages(): array
    {
        return [
            'mensaje.string' => 'El mensaje debe ser texto válido.',
            'mensaje.max' => 'El mensaje no puede exceder 5000 caracteres.',
            'archivos.array' => 'Los archivos deben ser un array.',
            'archivos.max' => 'No puedes adjuntar más de 5 archivos por mensaje.',
            'archivos.*.required' => 'Cada archivo debe ser válido.',
            'archivos.*.file' => 'Cada elemento debe ser un archivo válido.',
            'archivos.*.max' => 'Cada archivo no puede superar los 10MB.',
        ];
    }

    /**
     * Validación personalizada: debe haber mensaje o archivos
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $mensaje = $this->input('mensaje');
            $archivos = $this->file('archivos');

            $mensajeVacio = empty(trim($mensaje ?? ''));
            $sinArchivos = empty($archivos) || count($archivos) === 0;

            if ($mensajeVacio && $sinArchivos) {
                $validator->errors()->add(
                    'mensaje',
                    'Debes escribir un mensaje o adjuntar al menos un archivo.'
                );
            }
        });
    }
}





