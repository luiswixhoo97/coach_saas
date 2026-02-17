<?php

namespace App\Services\Formulario;

use App\Models\Formulario;

class ServicioMapeoFormulario
{
    /**
     * Mapea respuestas del formulario a campos del cliente.
     * Usa orden de preguntas (Opción A - más simple).
     */
    public function mapearRespuestasACliente(array $respuestas, Formulario $formulario): array
    {
        $mapeo = [
            0 => 'nombre',
            1 => 'apellido_paterno',
            2 => 'apellido_materno',
            3 => 'sexo',
            4 => 'fecha_nacimiento',
            5 => 'altura',
            6 => 'objetivo',
        ];
        
        $datos = [];
        
        foreach ($respuestas as $index => $respuesta) {
            if (isset($mapeo[$index])) {
                $campo = $mapeo[$index];
                $datos[$campo] = $respuesta;
            }
        }
        
        return $datos;
    }
}

