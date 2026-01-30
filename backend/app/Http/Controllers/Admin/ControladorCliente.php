<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorCliente extends Controller
{
    /**
     * Listar todos los clientes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cliente::with(['usuario', 'coach']);

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('coach_id')) {
            $query->where('creado_por', $request->coach_id);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('usuario', fn($q) => $q->where('email', 'like', "%{$buscar}%"));
        }

        $clientes = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'datos' => $clientes->map(fn($cliente) => $this->formatearCliente($cliente)),
            'meta' => [
                'total' => $clientes->total(),
                'por_pagina' => $clientes->perPage(),
                'pagina_actual' => $clientes->currentPage(),
                'ultima_pagina' => $clientes->lastPage(),
            ],
        ]);
    }

    /**
     * Mostrar detalle de un cliente.
     */
    public function mostrar(int $id): JsonResponse
    {
        $cliente = Cliente::with(['usuario', 'coach', 'suscripciones.plan'])
            ->findOrFail($id);

        return response()->json([
            'datos' => array_merge(
                $this->formatearCliente($cliente),
                [
                    'suscripciones' => $cliente->suscripciones->map(fn($s) => [
                        'id' => $s->id,
                        'plan' => $s->plan->nombre ?? null,
                        'estado' => $s->estado,
                        'fecha_inicio' => $s->fecha_inicio->format('Y-m-d'),
                        'fecha_fin' => $s->fecha_fin->format('Y-m-d'),
                    ]),
                ]
            ),
        ]);
    }

    /**
     * Formatear cliente para respuesta.
     */
    private function formatearCliente(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id,
            'email' => $cliente->usuario->email ?? null,
            'nombre' => $cliente->nombre,
            'apellido_paterno' => $cliente->apellido_paterno,
            'apellido_materno' => $cliente->apellido_materno,
            'sexo' => $cliente->sexo,
            'fecha_nacimiento' => $cliente->fecha_nacimiento?->format('Y-m-d'),
            'altura' => $cliente->altura,
            'objetivo' => $cliente->objetivo,
            'activo' => $cliente->activo,
            'coach' => $cliente->coach ? [
                'id' => $cliente->coach->id,
                'nombre' => $cliente->coach->nombre,
                'apellido_paterno' => $cliente->coach->apellido_paterno,
                'apellido_materno' => $cliente->coach->apellido_materno,
            ] : null,
            'created_at' => $cliente->created_at->format('Y-m-d H:i'),
        ];
    }
}
