<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\Ejercicio;
use App\Models\User;
use Illuminate\Database\Seeder;

class EntrenamientoEjerciciosSeeder extends Seeder
{
    /**
     * Catálogo de ejercicios por grupo muscular para desarrollo y pruebas.
     * Usa firstOrCreate para ser idempotente (se puede ejecutar varias veces).
     */
    public function run(): void
    {
        $coach = Coach::first();

        if (!$coach) {
            $this->command->warn('No hay ningún coach. Ejecuta antes RelacionesSeeder.');
            return;
        }

        $ejercicios = [
            ['nombre' => 'Sentadillas', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Sentadilla búlgara', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Prensa de piernas', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Zancadas', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Peso muerto rumano', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Curl femoral', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Extensión de cuádriceps', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Elevación de talones', 'grupo_muscular' => 'pierna'],
            ['nombre' => 'Press banca', 'grupo_muscular' => 'pecho'],
            ['nombre' => 'Press banca inclinado', 'grupo_muscular' => 'pecho'],
            ['nombre' => 'Aperturas con mancuernas', 'grupo_muscular' => 'pecho'],
            ['nombre' => 'Fondos en paralelas', 'grupo_muscular' => 'pecho'],
            ['nombre' => 'Cruces en polea', 'grupo_muscular' => 'pecho'],
            ['nombre' => 'Press de pecho en máquina', 'grupo_muscular' => 'pecho'],
            ['nombre' => 'Dominadas', 'grupo_muscular' => 'espalda'],
            ['nombre' => 'Remo con barra', 'grupo_muscular' => 'espalda'],
            ['nombre' => 'Remo con mancuerna', 'grupo_muscular' => 'espalda'],
            ['nombre' => 'Jalón al pecho', 'grupo_muscular' => 'espalda'],
            ['nombre' => 'Peso muerto convencional', 'grupo_muscular' => 'espalda'],
            ['nombre' => 'Hiperextensiones', 'grupo_muscular' => 'espalda'],
            ['nombre' => 'Press militar', 'grupo_muscular' => 'hombro'],
            ['nombre' => 'Elevaciones laterales', 'grupo_muscular' => 'hombro'],
            ['nombre' => 'Elevaciones frontales', 'grupo_muscular' => 'hombro'],
            ['nombre' => 'Face pull', 'grupo_muscular' => 'hombro'],
            ['nombre' => 'Encogimientos', 'grupo_muscular' => 'hombro'],
            ['nombre' => 'Curl de bíceps con barra', 'grupo_muscular' => 'biceps'],
            ['nombre' => 'Curl de bíceps con mancuernas', 'grupo_muscular' => 'biceps'],
            ['nombre' => 'Curl martillo', 'grupo_muscular' => 'biceps'],
            ['nombre' => 'Curl en polea', 'grupo_muscular' => 'biceps'],
            ['nombre' => 'Press de tríceps en polea', 'grupo_muscular' => 'triceps'],
            ['nombre' => 'Fondos en banco', 'grupo_muscular' => 'triceps'],
            ['nombre' => 'Extensión de tríceps con mancuerna', 'grupo_muscular' => 'triceps'],
            ['nombre' => 'Plancha', 'grupo_muscular' => 'core'],
            ['nombre' => 'Crunch abdominal', 'grupo_muscular' => 'core'],
            ['nombre' => 'Plancha lateral', 'grupo_muscular' => 'core'],
            ['nombre' => 'Russian twist', 'grupo_muscular' => 'core'],
            ['nombre' => 'Elevación de piernas', 'grupo_muscular' => 'core'],
        ];

        foreach ($ejercicios as $datos) {
            Ejercicio::firstOrCreate(
                [
                    'coach_id' => $coach->id,
                    'nombre'   => $datos['nombre'],
                ],
                [
                    'grupo_muscular' => $datos['grupo_muscular'],
                    'video_url'       => null,
                ]
            );
        }

        $this->command->info('Ejercicios del catálogo creados o actualizados para el coach.');
    }
}
