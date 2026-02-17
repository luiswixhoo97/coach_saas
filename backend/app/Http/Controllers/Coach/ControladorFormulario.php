<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\AlmacenarFormularioRequest;
use App\Http\Requests\Coach\ActualizarFormularioRequest;
use App\Http\Requests\Coach\SolicitudResponderFormularioPorCliente;
use App\Http\Resources\FormularioResource;
use App\Models\Cliente;
use App\Models\Formulario;
use App\Models\FormularioRespuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorFormulario extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Solo mostrar el formulario estándar (el primero/único)
        $formularioEstandar = null;
        if ($coach->tieneFormularioInicial()) {
            $formularioEstandar = Formulario::withCount('respuestas')
                ->where('id', $coach->formulario_inicial_id)
                ->first();
        }

        // Si no hay formulario estándar, buscar el primero creado
        if (!$formularioEstandar) {
            $formularioEstandar = Formulario::withCount('respuestas')
                ->where('coach_id', $coach->id)
                ->orderBy('created_at', 'asc')
                ->first();
            
            // Si existe, asignarlo como estándar
            if ($formularioEstandar) {
                $coach->update(['formulario_inicial_id' => $formularioEstandar->id]);
            }
        }

        $datos = $formularioEstandar ? [new FormularioResource($formularioEstandar)] : [];

        return response()->json([
            'datos' => $datos,
            'meta' => [
                'total' => $formularioEstandar ? 1 : 0,
                'por_pagina' => 15,
                'pagina_actual' => 1,
            ],
        ]);
    }

    public function almacenar(AlmacenarFormularioRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Si el coach no tiene un formulario estándar, este será el primero y se asigna automáticamente
        $esPrimerFormulario = !$coach->tieneFormularioInicial();

        $formulario = Formulario::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'preguntas' => $request->preguntas,
            'activo' => true,
        ]);

        // Si es el primer formulario, asignarlo automáticamente como formulario estándar
        if ($esPrimerFormulario) {
            $coach->update(['formulario_inicial_id' => $formulario->id]);
        }

        return response()->json([
            'mensaje' => 'Formulario creado correctamente.',
            'datos' => new FormularioResource($formulario),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        return response()->json([
            'datos' => new FormularioResource($formulario),
        ]);
    }

    public function actualizar(ActualizarFormularioRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $formulario->update($request->only(['nombre', 'preguntas', 'activo']));

        return response()->json([
            'mensaje' => 'Formulario actualizado correctamente.',
            'datos' => new FormularioResource($formulario),
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $formulario->delete();

        return response()->json(['mensaje' => 'Formulario eliminado correctamente.']);
    }

    public function enviar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'exists:clientes,id',
        ]);

        // TODO: Implementar notificación a clientes
        return response()->json([
            'mensaje' => 'Formulario enviado a los clientes seleccionados.',
        ]);
    }

    public function respuestas(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $formulario = Formulario::where('coach_id', $coach->id)->findOrFail($id);

        $respuestas = FormularioRespuesta::with(['cliente.usuario'])
            ->where('formulario_id', $formulario->id)
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $respuestas->map(fn($r) => [
                'id' => $r->id,
                'cliente' => $r->cliente->usuario->email,
                'fecha' => $r->fecha->format('Y-m-d H:i'),
                'respuestas' => $r->respuestas,
            ]),
            'meta' => [
                'total' => $respuestas->total(),
                'por_pagina' => $respuestas->perPage(),
                'pagina_actual' => $respuestas->currentPage(),
            ],
        ]);
    }

    /**
     * Permite al coach llenar el formulario estándar en nombre del cliente.
     * Usa el formulario estándar del coach (formulario_inicial_id).
     * Permite valores null para preguntas no contestadas.
     */
    public function responderPorCliente(SolicitudResponderFormularioPorCliente $request, int $cliente): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenezca al coach
        $clienteModel = Cliente::where('creado_por', $coach->id)
            ->findOrFail($cliente);

        // Usar el formulario estándar del coach
        if (!$coach->tieneFormularioInicial()) {
            return response()->json([
                'mensaje' => 'No tienes un formulario estándar configurado.',
            ], 400);
        }

        $formularioEstandar = $coach->formularioInicial;

        // Convertir strings vacíos a null para mantener consistencia
        $respuestas = array_map(function($respuesta) {
            return $respuesta === '' || $respuesta === null ? null : $respuesta;
        }, $request->respuestas);

        // Crear o actualizar respuesta
        $respuesta = FormularioRespuesta::updateOrCreate(
            [
                'formulario_id' => $formularioEstandar->id,
                'cliente_id' => $clienteModel->id,
            ],
            [
                'fecha' => now(),
                'respuestas' => $respuestas,
            ]
        );

        return response()->json([
            'mensaje' => 'Formulario completado correctamente.',
            'datos' => [
                'id' => $respuesta->id,
                'formulario_id' => $respuesta->formulario_id,
                'cliente_id' => $respuesta->cliente_id,
                'fecha' => $respuesta->fecha->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
