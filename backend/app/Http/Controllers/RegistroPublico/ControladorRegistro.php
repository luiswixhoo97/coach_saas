<?php

namespace App\Http\Controllers\RegistroPublico;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroPublico\SolicitudProcesarRegistro;
use App\Models\Coach;
use App\Models\Plan;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Suscripcion;
use App\Services\Formulario\ServicioMapeoFormulario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ControladorRegistro extends Controller
{
    public function mostrar(string $token): JsonResponse
    {
        $coach = Coach::where('token_registro', $token)
            ->where('link_registro_activo', true)
            ->where('activo', true)
            ->firstOrFail();
        
        $planes = Plan::where('coach_id', $coach->id)->get();
        
        if ($planes->isEmpty()) {
            return response()->json([
                'mensaje' => 'Este coach no tiene planes disponibles.',
            ], 400);
        }
        
        return response()->json([
            'datos' => [
                'coach' => [
                    'id' => $coach->id,
                    'nombre' => $coach->nombre,
                ],
                'planes' => $planes->map(fn($p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'precio' => $p->precio,
                    'duracion_dias' => $p->duracion_dias,
                ]),
                'tiene_formulario_registro' => $coach->tieneFormularioRegistro(),
                'formulario_registro' => $coach->formularioRegistro ? [
                    'id' => $coach->formularioRegistro->id,
                    'nombre' => $coach->formularioRegistro->nombre,
                    'preguntas' => $coach->formularioRegistro->preguntas,
                ] : null,
            ],
        ]);
    }
    
    public function procesarRegistro(SolicitudProcesarRegistro $request): JsonResponse
    {
        // Validar método de pago (solo transferencia por el momento)
        if ($request->metodo_pago === 'stripe') {
            return response()->json([
                'mensaje' => 'Pago con tarjeta no disponible por el momento. Por favor, selecciona transferencia bancaria.',
            ], 501);
        }
        
        return DB::transaction(function () use ($request) {
            // 1. Validar coach y plan
            $coach = Coach::where('token_registro', $request->coach_token)
                ->where('link_registro_activo', true)
                ->firstOrFail();
            
            $plan = Plan::where('coach_id', $coach->id)
                ->findOrFail($request->plan_id);
            
            // 2. Crear usuario
            $usuario = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rol' => 'cliente',
                'activo' => false, // Inactivo hasta activación manual
            ]);
            
            // 3. Mapear respuestas de formulario si existe
            $datosCliente = [
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno ?? null,
                'sexo' => $request->sexo,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'altura' => $request->altura,
                'objetivo' => $request->objetivo,
            ];
            
            if ($coach->tieneFormularioRegistro() && $request->respuestas_formulario) {
                $servicioMapeo = new ServicioMapeoFormulario();
                $datosMapeados = $servicioMapeo->mapearRespuestasACliente(
                    $request->respuestas_formulario,
                    $coach->formularioRegistro
                );
                // Filtrar valores vacíos del mapeo y solo usar los que tienen valor
                $datosMapeados = array_filter($datosMapeados, fn($v) => $v !== '' && $v !== null);
                $datosCliente = array_merge($datosCliente, $datosMapeados);
            }
            
            // 4. Crear cliente
            $cliente = Cliente::create([
                'usuario_id' => $usuario->id,
                'creado_por' => $coach->id,
                'activo' => false, // Siempre inactivo con transferencia
                'pendiente_activacion' => true, // Nuevo ingreso: pendiente de que el coach active
                ...$datosCliente,
            ]);
            
            // 5. Crear suscripción
            $fechaInicio = now();
            $fechaFin = $fechaInicio->copy()->addDays($plan->duracion_dias);
            
            $suscripcion = Suscripcion::create([
                'cliente_id' => $cliente->id,
                'plan_id' => $plan->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => 'pendiente', // Pendiente hasta activación
            ]);
            
            // 6. Asignar formulario inicial si existe (pero solo se activa cuando cliente se active)
            if ($coach->tieneFormularioInicial()) {
                $cliente->formulariosAsignados()->attach($coach->formulario_inicial_id, [
                    'obligatorio' => true,
                    'fecha_asignacion' => now(),
                ]);
            }
            
            return response()->json([
                'mensaje' => 'Tu cuenta ha sido creada. El coach activará tu cuenta después de verificar el pago.',
                'datos' => [
                    'email' => $usuario->email,
                    'activo' => false,
                    'mensaje_activacion' => 'Tu cuenta será activada después de que el coach verifique el pago.',
                ],
            ], 201);
        });
    }
}
