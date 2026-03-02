<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConfiguracionCoach;
use App\Models\DietaCliente;
use App\Models\Evaluacion;
use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorPerfil extends Controller
{
    /**
     * Obtener perfil del coach autenticado.
     */
    public function mostrar(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        if (!$coach) {
            return response()->json([
                'mensaje' => 'Perfil de coach no encontrado.',
            ], 404);
        }

        // Cargar formulario estándar y configuración
        $coach->load(['formularioInicial', 'configuracion']);

        $config = $coach->configuracion;
        if (!$config) {
            $config = ConfiguracionCoach::create(['coach_id' => $coach->id]);
        }

        return response()->json([
            'datos' => [
                'id' => $coach->id,
                'nombre' => $coach->nombre,
                'bio' => $coach->bio,
                'avatar' => $coach->avatar,
                'activo' => $coach->activo,
                'email' => $request->user()->email,
                'token_registro' => $coach->token_registro,
                'link_registro' => $coach->obtenerLinkRegistro(),
                'link_registro_activo' => $coach->link_registro_activo,
                'formulario_inicial_id' => $coach->formulario_inicial_id,
                'formulario_estandar' => $coach->formularioInicial ? [
                    'id' => $coach->formularioInicial->id,
                    'nombre' => $coach->formularioInicial->nombre,
                ] : null,
                'configuracion' => [
                    'nombre_cuenta' => $config->nombre_cuenta,
                    'banco' => $config->banco,
                    'clave' => $config->clave,
                    'semanas_entre_evaluaciones' => $config->semanas_entre_evaluaciones,
                ],
            ],
        ]);
    }

    /**
     * Actualizar perfil del coach.
     */
    public function actualizar(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'bio' => 'nullable|string',
            'formulario_inicial_id' => 'nullable|exists:formularios,id',
        ]);

        $coach = $request->user()->coach;

        // Verificar que el formulario pertenezca al coach si se está actualizando
        if ($request->has('formulario_inicial_id') && $request->formulario_inicial_id) {
            $formulario = \App\Models\Formulario::where('coach_id', $coach->id)
                ->find($request->formulario_inicial_id);
            
            if (!$formulario) {
                return response()->json([
                    'mensaje' => 'El formulario seleccionado no existe o no pertenece a tu cuenta.',
                ], 400);
            }
        }

        $coach->update($request->only(['nombre', 'bio', 'formulario_inicial_id']));

        // Recargar relación
        $coach->load('formularioInicial');

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente.',
            'datos' => [
                'id' => $coach->id,
                'nombre' => $coach->nombre,
                'bio' => $coach->bio,
                'avatar' => $coach->avatar,
                'formulario_inicial_id' => $coach->formulario_inicial_id,
                'formulario_estandar' => $coach->formularioInicial ? [
                    'id' => $coach->formularioInicial->id,
                    'nombre' => $coach->formularioInicial->nombre,
                ] : null,
            ],
        ]);
    }

    /**
     * Subir avatar del coach.
     */
    public function subirAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ], [
            'avatar.required' => 'La imagen es requerida.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.max' => 'La imagen no debe superar 2MB.',
        ]);

        $coach = $request->user()->coach;

        // Eliminar avatar anterior si existe
        if ($coach->avatar) {
            Storage::disk('public')->delete($coach->avatar);
        }

        // Guardar nuevo avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $coach->update(['avatar' => $path]);

        return response()->json([
            'mensaje' => 'Avatar actualizado correctamente.',
            'datos' => [
                'avatar' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Dashboard con métricas del coach.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        $clientesActivos = Cliente::where('creado_por', $coach->id)
            ->where('activo', true)
            ->count();

        $clientesTotal = Cliente::where('creado_por', $coach->id)->count();

        $nuevoIngreso = Cliente::where('creado_por', $coach->id)
            ->where('pendiente_activacion', true)
            ->count();

        $suscripcionesActivas = Suscripcion::whereHas('cliente', fn($q) => 
            $q->where('creado_por', $coach->id)
        )->where('estado', 'activa')->count();

        $ingresosMes = Pago::whereHas('suscripcion.plan', fn($q) => 
            $q->where('coach_id', $coach->id)
        )
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->sum('monto');

        $clientesConDieta = DietaCliente::clientesConDietaCount($coach->id);

        $clientesSinDieta = Cliente::where('creado_por', $coach->id)
            ->whereDoesntHave('suscripciones', fn($q) => $q->whereHas('dietas', fn($dq) => $dq->where('activo', true)))
            ->count();

        $clientesVencimientoProximo = Cliente::delCoach($coach->id)
            ->whereHas('suscripciones', fn($q) => $q->venceProximo(30))
            ->count();

        $citasAgendadas = Evaluacion::whereHas('suscripcion.cliente', fn($q) =>
            $q->where('creado_por', $coach->id)
        )->whereIn('estado', ['agendada', 'confirmada', 'reagendar'])->count();

        $citasReagendadas = Evaluacion::whereHas('suscripcion.cliente', fn($q) =>
            $q->where('creado_por', $coach->id)
        )->where('estado', 'reagendar')->count();

        $evaluacionProxima = $this->obtenerClientesProximoAEvaluacion($coach)->count();

        return response()->json([
            'datos' => [
                'clientes' => [
                    'activos' => $clientesActivos,
                    'total' => $clientesTotal,
                    'nuevo_ingreso' => $nuevoIngreso,
                    'con_dieta' => $clientesConDieta,
                    'sin_dieta' => $clientesSinDieta,
                    'vencimiento_proximo' => $clientesVencimientoProximo,
                    'evaluacion_proxima' => $evaluacionProxima,
                ],
                'suscripciones_activas' => $suscripcionesActivas,
                'ingresos_mes' => $ingresosMes,
                'citas_agendadas' => $citasAgendadas,
                'citas_reagendadas' => $citasReagendadas,
            ],
        ]);
    }

    /**
     * Listar citas agendadas (evaluaciones pendientes) para el modal de estadísticas.
     */
    public function citasAgendadas(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;

        $query = Evaluacion::with(['suscripcion.cliente.usuario'])
            ->whereHas('suscripcion.cliente', fn($q) => $q->where('creado_por', $coach->id));

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
            if (!$request->filled('estado')) {
                $query->whereIn('estado', ['agendada', 'confirmada']);
            }
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        } elseif (!$request->filled('fecha')) {
            $query->whereIn('estado', ['agendada', 'confirmada', 'reagendar']);
        }

        $evaluaciones = $query->orderBy('fecha')->orderBy('hora')->limit(100)->get();

        $datos = $evaluaciones->map(function ($ev) {
            $cliente = $ev->suscripcion->cliente ?? null;
            $nombre = $cliente
                ? trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellido_paterno ?? '') . ' ' . ($cliente->apellido_materno ?? ''))
                : '—';
            $hora = $ev->hora instanceof \Carbon\Carbon
                ? $ev->hora->format('H:i')
                : (is_string($ev->hora) ? $ev->hora : '—');
            return [
                'id' => $ev->id,
                'fecha' => $ev->fecha?->format('Y-m-d'),
                'hora' => $hora,
                'estado' => $ev->estado ?? 'agendada',
                'cliente_id' => $cliente?->id,
                'cliente_nombre' => $nombre,
            ];
        });

        return response()->json(['datos' => $datos->values()->all()]);
    }

    /**
     * Generar o regenerar link de registro.
     */
    public function generarLinkRegistro(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;
        
        $token = $coach->generarTokenRegistro();
        $link = $coach->obtenerLinkRegistro();
        
        return response()->json([
            'mensaje' => 'Link de registro generado correctamente.',
            'datos' => [
                'token' => $token,
                'link' => $link,
            ],
        ]);
    }

    /**
     * Activar o desactivar link de registro.
     */
    public function toggleLinkRegistro(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;
        
        $coach->update([
            'link_registro_activo' => !$coach->link_registro_activo,
        ]);
        
        return response()->json([
            'mensaje' => $coach->link_registro_activo 
                ? 'Link de registro activado.' 
                : 'Link de registro desactivado.',
            'datos' => [
                'link_registro_activo' => $coach->link_registro_activo,
            ],
        ]);
    }

    /**
     * Obtener o crear configuración del coach y devolverla (GET).
     */
    public function configuracion(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;
        $coach->load('configuracion');
        $config = $coach->configuracion;
        if (!$config) {
            $config = ConfiguracionCoach::create(['coach_id' => $coach->id]);
        }
        return response()->json([
            'datos' => [
                'nombre_cuenta' => $config->nombre_cuenta,
                'banco' => $config->banco,
                'clave' => $config->clave,
                'semanas_entre_evaluaciones' => $config->semanas_entre_evaluaciones,
            ],
        ]);
    }

    /**
     * Actualizar configuración del coach (PUT).
     */
    public function actualizarConfiguracion(Request $request): JsonResponse
    {
        $request->validate([
            'nombre_cuenta' => 'nullable|string|max:255',
            'banco' => 'nullable|string|max:255',
            'clave' => 'nullable|string|max:255',
            'semanas_entre_evaluaciones' => 'nullable|integer|min:1|max:52',
        ]);

        $coach = $request->user()->coach;
        $coach->load('configuracion');
        $config = $coach->configuracion;
        if (!$config) {
            $config = ConfiguracionCoach::create(['coach_id' => $coach->id]);
        }
        $config->update($request->only(['nombre_cuenta', 'banco', 'clave', 'semanas_entre_evaluaciones']));
        return response()->json([
            'mensaje' => 'Configuración actualizada.',
            'datos' => [
                'nombre_cuenta' => $config->nombre_cuenta,
                'banco' => $config->banco,
                'clave' => $config->clave,
                'semanas_entre_evaluaciones' => $config->semanas_entre_evaluaciones,
            ],
        ]);
    }

    /**
     * Listar clientes próximos a evaluación (ventana 5 días antes del margen).
     */
    public function evaluacionProxima(Request $request): JsonResponse
    {
        $coach = $request->user()->coach;
        $clientes = $this->obtenerClientesProximoAEvaluacion($coach)->take(100);
        $datos = $clientes->map(function (Cliente $c) {
            $nombre = trim(($c->nombre ?? '') . ' ' . ($c->apellido_paterno ?? '') . ' ' . ($c->apellido_materno ?? ''));
            return [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'apellido_paterno' => $c->apellido_paterno,
                'apellido_materno' => $c->apellido_materno,
                'nombre_completo' => $nombre ?: '—',
                'email' => $c->usuario?->email ?? null,
            ];
        });
        return response()->json(['datos' => $datos->values()->all()]);
    }

    /**
     * Clientes del coach que están en ventana "próximo a evaluación":
     * - Tienen suscripción activa.
     * - No tienen evaluación en estado agendada/confirmada/reagendar.
     * - Días desde fecha_referencia >= (semanas_entre_evaluaciones * 7 - 5).
     * Fecha referencia = última evaluación completada o fecha_inicio de suscripción activa.
     */
    private function obtenerClientesProximoAEvaluacion($coach): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        $coach->load('configuracion');
        $config = $coach->configuracion;
        $semanasDefault = $config ? (int) $config->semanas_entre_evaluaciones : 4;

        $clientes = Cliente::with(['suscripciones' => fn($q) => $q->where('estado', 'activa')->with('evaluaciones')])
            ->where('creado_por', $coach->id)
            ->get();

        $hoy = now()->startOfDay();
        $resultado = collect();

        foreach ($clientes as $cliente) {
            $suscripcionActiva = $cliente->suscripciones->first();
            if (!$suscripcionActiva) {
                continue;
            }
            $tieneEvaluacionPendiente = $suscripcionActiva->evaluaciones
                ->contains(fn($e) => in_array($e->estado ?? '', ['agendada', 'confirmada', 'reagendar']));
            if ($tieneEvaluacionPendiente) {
                continue;
            }
            $ultimaCompletada = $suscripcionActiva->evaluaciones
                ->where('estado', 'completada')
                ->sortByDesc('fecha')
                ->first();
            $fechaRef = $ultimaCompletada?->fecha ?? $suscripcionActiva->fecha_inicio;
            if (!$fechaRef) {
                continue;
            }
            $fechaRef = $fechaRef instanceof \Carbon\Carbon ? $fechaRef : \Carbon\Carbon::parse($fechaRef);
            $semanas = $cliente->semanas_entre_evaluaciones ?? $semanasDefault;
            $diasLimite = (int) $semanas * 7 - 5;
            $diasDesde = $fechaRef->startOfDay()->diffInDays($hoy, false);
            if ($diasDesde >= $diasLimite) {
                $resultado->push($cliente);
            }
        }

        return $resultado;
    }
}
