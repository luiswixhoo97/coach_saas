<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\SolicitudAlmacenarParametroCliente;
use App\Http\Requests\Coach\SolicitudActualizarParametroCliente;
use App\Http\Resources\ParametroClienteResource;
use App\Models\Cliente;
use App\Models\ParametroCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorParametroCliente extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    /**
     * Obtener historial de parámetros de un cliente.
     */
    public function index(Request $request, int $cliente): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenezca al coach
        $clienteModel = Cliente::where('creado_por', $coach->id)
            ->findOrFail($cliente);

        // Obtener historial de parámetros ordenado por fecha descendente
        $parametros = ParametroCliente::with('parametro')
            ->where('cliente_id', $clienteModel->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'datos' => ParametroClienteResource::collection($parametros),
        ]);
    }

    /**
     * Agregar parámetro al historial del cliente.
     */
    public function almacenar(SolicitudAlmacenarParametroCliente $request, int $cliente): JsonResponse
    {
        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenezca al coach
        $clienteModel = Cliente::where('creado_por', $coach->id)
            ->findOrFail($cliente);

        // Verificar que el parámetro esté disponible para el coach
        $parametro = \App\Models\Parametro::where(function ($q) use ($coach) {
            $q->where('es_predeterminado', true)
              ->orWhere('coach_id', $coach->id);
        })->findOrFail($request->parametro_id);

        $parametroCliente = ParametroCliente::create([
            'cliente_id' => $clienteModel->id,
            'parametro_id' => $parametro->id,
            'valor' => $request->valor,
            'fecha' => $request->fecha ?? now()->toDateString(), // Usar fecha actual si no se proporciona
            'notas' => $request->notas,
        ]);

        $parametroCliente->load('parametro');

        return response()->json([
            'mensaje' => 'Parámetro agregado correctamente.',
            'datos' => new ParametroClienteResource($parametroCliente),
        ], 201);
    }

    /**
     * Actualizar parámetro del historial.
     */
    public function actualizar(SolicitudActualizarParametroCliente $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $parametroCliente = ParametroCliente::with('parametro')
            ->whereHas('cliente', fn($q) => $q->where('creado_por', $coach->id))
            ->findOrFail($id);

        $parametroCliente->update($request->only(['valor', 'fecha', 'notas']));

        return response()->json([
            'mensaje' => 'Parámetro actualizado correctamente.',
            'datos' => new ParametroClienteResource($parametroCliente),
        ]);
    }

    /**
     * Eliminar parámetro del historial.
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $parametroCliente = ParametroCliente::whereHas('cliente', fn($q) => $q->where('creado_por', $coach->id))
            ->findOrFail($id);

        $parametroCliente->delete();

        return response()->json([
            'mensaje' => 'Parámetro eliminado correctamente.',
        ]);
    }
}
