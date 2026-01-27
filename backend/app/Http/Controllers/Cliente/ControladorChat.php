<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Mensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorChat extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    public function mostrar(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $chat = Chat::with(['coach', 'mensajes' => fn($q) => $q->latest()->limit(50)])
            ->where('cliente_id', $cliente->id)
            ->first();

        if (!$chat) {
            // Crear chat automáticamente si no existe
            $chat = Chat::create([
                'coach_id' => $cliente->creado_por,
                'cliente_id' => $cliente->id,
                'creado_en' => now(),
            ]);
            $chat->load('coach');
        }

        // Marcar mensajes como leídos
        $chat->marcarComoLeido('cliente');

        return response()->json([
            'datos' => [
                'id' => $chat->id,
                'coach' => [
                    'id' => $chat->coach->id,
                    'nombre' => $chat->coach->nombre,
                ],
                'mensajes' => $chat->mensajes->map(fn($m) => [
                    'id' => $m->id,
                    'emisor_tipo' => $m->emisor_tipo,
                    'mensaje' => $m->mensaje,
                    'enviado_en' => $m->enviado_en->format('Y-m-d H:i'),
                    'leido' => $m->leido,
                ]),
            ],
        ]);
    }

    public function mensajes(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $chat = Chat::where('cliente_id', $cliente->id)->firstOrFail();

        $mensajes = $chat->mensajes()
            ->orderBy('enviado_en', 'desc')
            ->paginate(50);

        return response()->json([
            'datos' => $mensajes->map(fn($m) => [
                'id' => $m->id,
                'emisor_tipo' => $m->emisor_tipo,
                'mensaje' => $m->mensaje,
                'enviado_en' => $m->enviado_en->format('Y-m-d H:i'),
                'leido' => $m->leido,
            ]),
            'meta' => [
                'total' => $mensajes->total(),
                'por_pagina' => $mensajes->perPage(),
                'pagina_actual' => $mensajes->currentPage(),
            ],
        ]);
    }

    public function enviarMensaje(Request $request): JsonResponse
    {
        $request->validate([
            'mensaje' => 'required|string|max:5000',
        ]);

        $cliente = $this->getCliente($request);

        $chat = Chat::where('cliente_id', $cliente->id)->first();

        if (!$chat) {
            $chat = Chat::create([
                'coach_id' => $cliente->creado_por,
                'cliente_id' => $cliente->id,
                'creado_en' => now(),
            ]);
        }

        $mensaje = Mensaje::create([
            'chat_id' => $chat->id,
            'emisor_tipo' => 'cliente',
            'mensaje' => $request->mensaje,
            'enviado_en' => now(),
            'leido' => false,
        ]);

        $chat->touch();

        return response()->json([
            'mensaje' => 'Mensaje enviado.',
            'datos' => [
                'id' => $mensaje->id,
                'mensaje' => $mensaje->mensaje,
                'enviado_en' => $mensaje->enviado_en->format('Y-m-d H:i'),
            ],
        ], 201);
    }

    public function marcarLeido(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $chat = Chat::where('cliente_id', $cliente->id)->firstOrFail();

        $chat->marcarComoLeido('cliente');

        return response()->json(['mensaje' => 'Mensajes marcados como leídos.']);
    }
}
