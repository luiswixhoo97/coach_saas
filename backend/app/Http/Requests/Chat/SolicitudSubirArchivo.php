<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudSubirArchivo extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización se maneja en el controlador verificando que el chat pertenece al usuario
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => 'required|file|max:10240', // 10MB máximo
            'tipo' => 'nullable|in:imagen,documento', // Se detecta automáticamente si no se envía
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file' => 'El archivo debe ser válido.',
            'archivo.max' => 'El archivo no puede superar los 10MB.',
            'tipo.in' => 'El tipo debe ser: imagen o documento.',
        ];
    }

    /**
     * Validar tipos de archivo permitidos
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $archivo = $this->file('archivo');
            
            if (!$archivo) {
                return;
            }

            $mimeType = $archivo->getMimeType();
            $extension = strtolower($archivo->getClientOriginalExtension());

            // Tipos permitidos
            $tiposImagen = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $extensionesImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            $tiposDocumento = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $extensionesDocumento = ['pdf', 'doc', 'docx'];

            $esImagen = in_array($mimeType, $tiposImagen) || in_array($extension, $extensionesImagen);
            $esDocumento = in_array($mimeType, $tiposDocumento) || in_array($extension, $extensionesDocumento);

            if (!$esImagen && !$esDocumento) {
                $validator->errors()->add(
                    'archivo',
                    'El archivo debe ser una imagen (jpg, png, gif, webp) o un documento (pdf, doc, docx).'
                );
            }
        });
    }
}




