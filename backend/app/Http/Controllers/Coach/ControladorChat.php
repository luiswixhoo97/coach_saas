<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SolicitudEnviarMensaje;
use App\Http\Resources\ArchivoMensajeResource;
use App\Http\Resources\ChatResource;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\MensajeResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\ArchivoMensaje;
use App\Models\Chat;
use App\Models\Cliente;
use App\Models\Mensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorChat extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        // Obtener todos los clientes del coach
        $clientes = Cliente::with('usuario')
            ->where('creado_por', $coach->id)
            ->get();

        // Crear/obtener chat para cada cliente usando firstOrCreate
        // Esto evita duplicados y siempre obtiene el mismo chat
        $chats = $clientes->map(function ($cliente) use ($coach) {
            $chat = Chat::firstOrCreate(
                ['coach_id' => $coach->id, 'cliente_id' => $cliente->id],
                ['creado_en' => now()]
            );
            $chat->load(['cliente.usuario']);
            return $chat;
        });

        // Ordenar por updated_at descendente
        $chats = $chats->sortByDesc('updated_at')->values();

        // Paginar manualmente
        $page = $request->input('page', 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        $items = $chats->slice($offset, $perPage)->values();
        $total = $chats->count();

        // Crear paginador manual
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return new PaginacionCollection($paginator, ChatResource::class);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
        ]);

        $coach = $this->getCoach($request);

        // Verificar que el cliente pertenece al coach
        $cliente = Cliente::where('creado_por', $coach->id)->findOrFail($request->cliente_id);

        // Usar firstOrCreate para evitar duplicados
        $chat = Chat::firstOrCreate(
            ['coach_id' => $coach->id, 'cliente_id' => $cliente->id],
            ['creado_en' => now()]
        );

        $wasRecentlyCreated = $chat->wasRecentlyCreated;

        return response()->json([
            'mensaje' => $wasRecentlyCreated ? 'Chat creado correctamente.' : 'Ya existe un chat con este cliente.',
            'datos' => ['id' => $chat->id],
        ], $wasRecentlyCreated ? 201 : 200);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::with(['cliente.usuario', 'mensajes.archivos'])
            ->where('coach_id', $coach->id)
            ->findOrFail($id);

        // Marcar mensajes como leídos
        $chat->marcarComoLeido('coach');

        return response()->json([
            'datos' => new ChatResource($chat),
        ]);
    }

    public function mensajes(Request $request, int $id): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

        $mensajes = $chat->mensajes()
            ->with('archivos')
            ->orderBy('enviado_en', 'desc')
            ->paginate(50);

        return new PaginacionCollection($mensajes, MensajeResource::class);
    }

    public function enviarMensaje(SolicitudEnviarMensaje $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

        $mensaje = Mensaje::create([
            'chat_id' => $chat->id,
            'emisor_tipo' => 'coach',
            'mensaje' => $request->input('mensaje', ''),
            'enviado_en' => now(),
            'leido' => false,
        ]);

        // Procesar archivos si existen
        if ($request->hasFile('archivos')) {
            $this->procesarArchivos($mensaje, $request->file('archivos'), $chat->id);
        }

        $chat->touch(); // Actualizar updated_at del chat

        // Cargar archivos para la respuesta
        $mensaje->load('archivos');

        return response()->json([
            'mensaje' => 'Mensaje enviado.',
            'datos' => new MensajeResource($mensaje),
        ], 201);
    }

    /**
     * Procesar y guardar archivos adjuntos
     */
    private function procesarArchivos(Mensaje $mensaje, array $archivos, int $chatId): void
    {
        foreach ($archivos as $archivo) {
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = strtolower($archivo->getClientOriginalExtension());
            $mimeType = $archivo->getMimeType();

            // Determinar tipo
            $tiposImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $tipo = in_array($extension, $tiposImagen) ? 'imagen' : 'documento';

            // Sanitizar nombre
            $nombreSanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
            $nombreFinal = $nombreSanitizado . '_' . time() . '_' . uniqid() . '.' . $extension;

            // Guardar archivo
            $ruta = $archivo->storeAs("chat/{$chatId}", $nombreFinal, 'public');

            // Crear registro en BD
            ArchivoMensaje::create([
                'mensaje_id' => $mensaje->id,
                'tipo' => $tipo,
                'nombre_original' => $nombreOriginal,
                'ruta' => $ruta,
                'tamaño' => $archivo->getSize(),
                'mime_type' => $mimeType,
            ]);
        }
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo(Request $request, int $id, int $archivoId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

        // Verificar que el archivo pertenece a un mensaje de este chat
        $archivo = ArchivoMensaje::whereHas('mensaje', function ($query) use ($chat) {
            $query->where('chat_id', $chat->id);
        })->findOrFail($archivoId);

        // Verificar que el archivo existe
        if (!Storage::disk('public')->exists($archivo->ruta)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->download(
            $archivo->ruta,
            $archivo->nombre_original
        );
    }

    public function marcarLeido(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $chat = Chat::where('coach_id', $coach->id)->findOrFail($id);

        $chat->marcarComoLeido('coach');

        return response()->json(['mensaje' => 'Mensajes marcados como leídos.']);
    }

    /**
     * Total de mensajes no leídos del coach (para el contador del menú).
     */
    public function mensajesNoLeidos(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $total = Mensaje::whereHas('chat', fn($q) => $q->where('coach_id', $coach->id))
            ->where('emisor_tipo', 'cliente')
            ->where('leido', false)
            ->count();

        return response()->json(['datos' => ['total' => $total]]);
    }
}
