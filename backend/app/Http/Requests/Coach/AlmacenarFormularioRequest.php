<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class AlmacenarFormularioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->esCoach();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string',
            'preguntas.*.tipo' => 'required|in:texto,numero,seleccion,multiple',
            'preguntas.*.opciones' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del formulario es requerido.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'preguntas.required' => 'Las preguntas son requeridas.',
            'preguntas.array' => 'Las preguntas deben ser un arreglo.',
            'preguntas.min' => 'Debe haber al menos una pregunta.',
            'preguntas.*.texto.required' => 'El texto de cada pregunta es requerido.',
            'preguntas.*.tipo.required' => 'El tipo de cada pregunta es requerido.',
            'preguntas.*.tipo.in' => 'El tipo debe ser: texto, numero, seleccion o multiple.',
        ];
    }
}
