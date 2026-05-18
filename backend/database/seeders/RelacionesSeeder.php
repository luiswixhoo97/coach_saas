<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Coach;
use App\Models\Ejercicio;
use App\Models\Evaluacion;
use App\Models\Formulario;
use App\Models\Pago;
use App\Models\Parametro;
use App\Models\ParametroEvaluacion;
use App\Models\Plan;
use App\Models\Rutina;
use App\Models\RutinaCliente;
use App\Models\RutinaEjercicio;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RelacionesSeeder extends Seeder
{
    /**
     * Crea datos relacionados para probar endpoints y relaciones.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'password' => $password,
                'rol' => 'admin',
                'activo' => true,
            ]
        );

        $usuarioCoach = User::firstOrCreate(
            ['email' => 'coach@test.com'],
            [
                'password' => $password,
                'rol' => 'coach',
                'activo' => true,
            ]
        );

        $coach = Coach::firstOrCreate(
            ['usuario_id' => $usuarioCoach->id],
            [
                'nombre' => 'Carlos',
                'apellido_paterno' => 'Coach',
                'apellido_materno' => 'Prueba',
                'fecha_nacimiento' => '1985-03-10',
                'bio' => 'Coach de prueba para desarrollo',
                'activo' => true,
            ]
        );

        $usuarioCliente = User::firstOrCreate(
            ['email' => 'cliente@test.com'],
            [
                'password' => $password,
                'rol' => 'cliente',
                'activo' => true,
            ]
        );

        $cliente = Cliente::firstOrCreate(
            ['usuario_id' => $usuarioCliente->id],
            [
                'nombre' => 'María',
                'apellido_paterno' => 'López',
                'apellido_materno' => 'Sánchez',
                'sexo' => 'femenino',
                'fecha_nacimiento' => '1995-08-20',
                'altura' => 165,
                'objetivo' => 'Bajar peso',
                'activo' => true,
                'creado_por' => $coach->id,
            ]
        );

        // Cliente 2
        $usuarioCliente2 = User::firstOrCreate(
            ['email' => 'cliente2@test.com'],
            [
                'password' => $password,
                'rol' => 'cliente',
                'activo' => true,
            ]
        );

        $cliente2 = Cliente::firstOrCreate(
            ['usuario_id' => $usuarioCliente2->id],
            [
                'nombre' => 'Juan',
                'apellido_paterno' => 'García',
                'apellido_materno' => 'Martínez',
                'sexo' => 'masculino',
                'fecha_nacimiento' => '1990-05-15',
                'altura' => 178,
                'objetivo' => 'Ganar masa muscular',
                'activo' => true,
                'creado_por' => $coach->id,
            ]
        );

        // Cliente 3
        $usuarioCliente3 = User::firstOrCreate(
            ['email' => 'cliente3@test.com'],
            [
                'password' => $password,
                'rol' => 'cliente',
                'activo' => true,
            ]
        );

        $cliente3 = Cliente::firstOrCreate(
            ['usuario_id' => $usuarioCliente3->id],
            [
                'nombre' => 'Ana',
                'apellido_paterno' => 'Rodríguez',
                'apellido_materno' => 'Fernández',
                'sexo' => 'femenino',
                'fecha_nacimiento' => '1992-11-30',
                'altura' => 160,
                'objetivo' => 'Tonificar',
                'activo' => true,
                'creado_por' => $coach->id,
            ]
        );

        // Cliente 4
        $usuarioCliente4 = User::firstOrCreate(
            ['email' => 'cliente4@test.com'],
            [
                'password' => $password,
                'rol' => 'cliente',
                'activo' => true,
            ]
        );

        $cliente4 = Cliente::firstOrCreate(
            ['usuario_id' => $usuarioCliente4->id],
            [
                'nombre' => 'Pedro',
                'apellido_paterno' => 'Hernández',
                'apellido_materno' => 'Torres',
                'sexo' => 'masculino',
                'fecha_nacimiento' => '1988-02-25',
                'altura' => 175,
                'objetivo' => 'Mejorar condición física',
                'activo' => true,
                'creado_por' => $coach->id,
            ]
        );

        $plan = Plan::create([
            'coach_id' => $coach->id,
            'nombre' => 'Plan Mensual Básico',
            'precio' => 299.00,
            'duracion_dias' => 30,
        ]);

        $suscripcion = Suscripcion::create([
            'cliente_id' => $cliente->id,
            'plan_id' => $plan->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(30),
            'estado' => 'activa',
        ]);

        Pago::create([
            'suscripcion_id' => $suscripcion->id,
            'monto' => 299.00,
            'metodo_pago' => 'efectivo',
            'referencia' => 'PAGO-SEED-001',
            'fecha' => now(),
        ]);

        $parametroPeso = Parametro::create([
            'nombre' => 'Peso',
            'unidad_medida' => 'kg',
            'tipo_dato' => 'numero',
            'es_predeterminado' => true,
            'coach_id' => null,
        ]);

        $parametroImc = Parametro::create([
            'nombre' => 'IMC',
            'unidad_medida' => '',
            'tipo_dato' => 'numero',
            'es_predeterminado' => true,
            'coach_id' => null,
        ]);

        Parametro::create([
            'nombre' => 'Grasa corporal',
            'unidad_medida' => '%',
            'tipo_dato' => 'numero',
            'es_predeterminado' => false,
            'coach_id' => $coach->id,
        ]);

        $evaluacion = Evaluacion::create([
            'suscripcion_id' => $suscripcion->id,
            'fecha' => now(),
            'modo' => 'presencial',
            'notas' => 'Evaluación inicial seed',
        ]);

        ParametroEvaluacion::create([
            'evaluacion_id' => $evaluacion->id,
            'parametro_id' => $parametroPeso->id,
            'valor' => '68.5',
        ]);

        ParametroEvaluacion::create([
            'evaluacion_id' => $evaluacion->id,
            'parametro_id' => $parametroImc->id,
            'valor' => '25.2',
        ]);

        $ejercicio1 = Ejercicio::create([
            'coach_id' => $coach->id,
            'nombre' => 'Sentadillas',
            'grupo_muscular' => 'pierna',
            'video_url' => null,
        ]);

        $ejercicio2 = Ejercicio::create([
            'coach_id' => $coach->id,
            'nombre' => 'Press banca',
            'grupo_muscular' => 'pecho',
            'video_url' => null,
        ]);

        $rutina = Rutina::create([
            'coach_id' => $coach->id,
            'nombre' => 'Rutina fuerza inicial',
            'nivel' => 'principiante',
            'objetivo' => 'hipertrofia',
        ]);

        RutinaEjercicio::create([
            'rutina_id' => $rutina->id,
            'ejercicio_id' => $ejercicio1->id,
            'series' => 3,
            'repeticiones' => 12,
            'descanso_segundos' => 60,
            'bloque' => 1,
        ]);

        RutinaEjercicio::create([
            'rutina_id' => $rutina->id,
            'ejercicio_id' => $ejercicio2->id,
            'series' => 3,
            'repeticiones' => 10,
            'descanso_segundos' => 90,
            'bloque' => 1,
        ]);

        RutinaCliente::create([
            'rutina_id' => $rutina->id,
            'cliente_id' => $cliente->id,
            'dia' => ['lunes', 'miércoles', 'viernes'],
        ]);

        Formulario::create([
            'coach_id' => $coach->id,
            'nombre' => 'Cuestionario inicial',
            'preguntas' => [
                ['id' => 'q1', 'texto' => '¿Cuál es tu objetivo principal?', 'tipo' => 'texto'],
                ['id' => 'q2', 'texto' => '¿Cuántos días puedes entrenar?', 'tipo' => 'numero'],
            ],
            'activo' => true,
        ]);
    }
}
