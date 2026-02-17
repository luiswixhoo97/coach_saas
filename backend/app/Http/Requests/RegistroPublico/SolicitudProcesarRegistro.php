<?php

namespace App\Http\Requests\RegistroPublico;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudProcesarRegistro extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Público
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coach_token' => 'required|string',
            'plan_id' => 'required|exists:planes,id',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'metodo_pago' => 'required|in:transferencia,stripe',
            'respuestas_formulario' => 'nullable|array',
            
            // Campos del cliente (nombre y apellido paterno requeridos si no hay formulario)
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'sexo' => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'required|date|before:today',
            'altura' => 'required|numeric|min:0|max:300',
            'objetivo' => 'required|string|in:Ganar masa muscular,Bajar de peso,Recomposición corporal,Mejorar condición física,Mantenimiento',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coach_token.required' => 'El token del coach es requerido.',
            'plan_id.required' => 'Debes seleccionar un plan.',
            'plan_id.exists' => 'El plan seleccionado no existe.',
            'email.required' => 'El email es requerido.',
            'email.email' => 'El email debe ser válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'metodo_pago.required' => 'Debes seleccionar un método de pago.',
            'metodo_pago.in' => 'Método de pago no válido.',
            'nombre.required' => 'El nombre es requerido.',
            'apellido_paterno.required' => 'El apellido paterno es requerido.',
            'sexo.required' => 'El sexo es requerido.',
            'sexo.in' => 'El sexo seleccionado no es válido.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es requerida.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'altura.required' => 'La altura es requerida.',
            'altura.numeric' => 'La altura debe ser un número.',
            'altura.min' => 'La altura debe ser mayor a 0.',
            'altura.max' => 'La altura debe ser menor a 300 cm.',
            'objetivo.required' => 'El objetivo es requerido.',
            'objetivo.in' => 'El objetivo seleccionado no es válido.',
        ];
    }
}
