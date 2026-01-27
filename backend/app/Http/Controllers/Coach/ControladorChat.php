<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Cliente;
use App\Models\Mensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorChat extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chats = Chat::with(['cliente.usuario'])
            ->where('coach_id', $coach->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $chats->map(fn($c) => [
                'id' => $c->id,
                'cliente' => [
                    'id' => $c->cliente->id,
                    'email' => $c->cliente->usuario->email,
                ],
                'ultimo_mensaje' => $c->ultimoMensaje()?->mensaje,
                'mensajes_no_leidos' => $c->mensajesNoLeidos('coach'),
                'actualizado_el' => $c->updated_at->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'total' => $chats->total(),
                'por_pagina' => $chats->perPage(),
                'pagina_actual' => $chats->currentPage(),
            ],
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
        ]);

        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenece al coach
        $cliente = Cliente::where('creado_por', $coach->id)->findOrFail($request->cliente_id);

        // Verificar si ya existe un chat
        $chat = Chat::where('coach_id', $coach->id)
            ->where('cliente_id', $cliente->id)
            ->first();

        if ($chat) {
            return response()->json([
                'mensaje' => 'Ya existe un chat con este cliente.',
                'datos' => ['id' => $chat->id],
            ]);
        }

        $chat = Chat::create([
            'coach_id' => $coach->id,
            'cliente_id' => $cliente->id,
            'creado_en' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Chat creado correctamente.',
            'datos' => ['id' => $chat->id],
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::with(['cliente.usuario', 'mensajes' => fn($q) => $q->latest()->limit(50)])
            ->where('coach_id', $coach->id)
            ->findOrFail($id);

        // Marcar mensajes como leídos
        $chat->marcarComoLeido('coach');

        return response()->json([
            'datos' => [
                'id' => $chat->id,
                'cliente' => [
                    'id' => $chat->cliente->id,
                    'email' => $chat->cliente->usuario->email,
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

    public function mensajes(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

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

    public function enviarMensaje(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'mensaje' => 'required|string|max:5000',
        ]);

        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

        $mensaje = Mensaje::create([
            'chat_id' => $chat->id,
            'emisor_tipo' => 'coach',
            'mensaje' => $request->mensaje,
            'enviado_en' => now(),
            'leido' => false,
        ]);

        $chat->touch(); // Actualizar updated_at del chat

        return response()->json([
            'mensaje' => 'Mensaje enviado.',
            'datos' => [
                'id' => $mensaje->id,
                'mensaje' => $mensaje->mensaje,
                'enviado_en' => $mensaje->enviado_en->format('Y-m-d H:i'),
            ],
        ], 201);
    }

    public function marcarLeido(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

        $chat->marcarComoLeido('coach');

        return response()->json(['mensaje' => 'Mensajes marcados como leídos.']);
    }
}
