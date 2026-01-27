<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorEvaluacion extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function index(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $evaluaciones = Evaluacion::whereHas('suscripcion', fn($q) => $q->where('cliente_id', $cliente->id))
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $evaluaciones->map(fn($e) => [
                'id' => $e->id,
                'fecha' => $e->fecha->format('Y-m-d'),
                'modo' => $e->modo,
                'fuente' => $e->fuente,
            ]),
            'meta' => [
                'total' => $evaluaciones->total(),
                'por_pagina' => $evaluaciones->perPage(),
                'pagina_actual' => $evaluaciones->currentPage(),
            ],
        ]);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $evaluacion = Evaluacion::with(['parametrosEvaluacion.parametro', 'fotos'])
            ->whereHas('suscripcion', fn($q) => $q->where('cliente_id', $cliente->id))
            ->findOrFail($id);

        return response()->json([
            'datos' => [
                'id' => $evaluacion->id,
                'fecha' => $evaluacion->fecha->format('Y-m-d'),
                'modo' => $evaluacion->modo,
                'fuente' => $evaluacion->fuente,
                'notas' => $evaluacion->notas,
                'parametros' => $evaluacion->parametrosEvaluacion->map(fn($pe) => [
                    'nombre' => $pe->parametro->nombre,
                    'valor' => $pe->valor,
                    'unidad' => $pe->parametro->unidad_medida,
                ]),
                'fotos' => $evaluacion->fotos->map(fn($f) => [
                    'tipo' => $f->tipo,
                    'url' => Storage::disk('public')->url($f->url),
                ]),
            ],
        ]);
    }

    public function progreso(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $evaluaciones = Evaluacion::with(['parametrosEvaluacion.parametro'])
            ->whereHas('suscripcion', fn($q) => $q->where('cliente_id', $cliente->id))
            ->orderBy('fecha', 'asc')
            ->get();

        // Agrupar por parámetro para gráficas
        $progresoPorParametro = [];
        
        foreach ($evaluaciones as $evaluacion) {
            foreach ($evaluacion->parametrosEvaluacion as $pe) {
                $nombreParametro = $pe->parametro->nombre;
                
                if (!isset($progresoPorParametro[$nombreParametro])) {
                    $progresoPorParametro[$nombreParametro] = [
                        'nombre' => $nombreParametro,
                        'unidad' => $pe->parametro->unidad_medida,
                        'datos' => [],
                    ];
                }
                
                $progresoPorParametro[$nombreParametro]['datos'][] = [
                    'fecha' => $evaluacion->fecha->format('Y-m-d'),
                    'valor' => $pe->valorNumerico() ?? $pe->valor,
                ];
            }
        }

        return response()->json([
            'datos' => array_values($progresoPorParametro),
        ]);
    }
}
