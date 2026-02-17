<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFormularioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|string|max:255',
            'preguntas' => 'sometimes|array|min:1',
            'preguntas.*.texto' => 'required_with:preguntas|string',
            'preguntas.*.tipo' => 'required_with:preguntas|in:texto,numero,seleccion,multiple',
            'preguntas.*.opciones' => 'nullable|array',
            'activo' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'preguntas.array' => 'Las preguntas deben ser un arreglo.',
            'preguntas.min' => 'Debe haber al menos una pregunta.',
            'preguntas.*.texto.required_with' => 'El texto de cada pregunta es requerido.',
            'preguntas.*.tipo.required_with' => 'El tipo de cada pregunta es requerido.',
            'preguntas.*.tipo.in' => 'El tipo debe ser: texto, numero, seleccion o multiple.',
        ];
    }
}

