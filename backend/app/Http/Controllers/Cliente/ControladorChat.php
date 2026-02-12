<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SolicitudEnviarMensaje;
use App\Http\Resources\ArchivoMensajeResource;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MensajeResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\ArchivoMensaje;
use App\Models\Chat;
use App\Models\Mensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorChat extends Controller
{
    private function getCliente(Request $request)
    {
        return $request->user()->cliente;
    }

    /**
     * Obtener o crear el chat del cliente con su coach.
     * Siempre usa firstOrCreate con ambas claves (coach_id + cliente_id)
     * para garantizar un único chat por par coach-cliente.
     */
    private function obtenerOCrearChat($cliente)
    {
        return Chat::firstOrCreate(
            ['coach_id' => $cliente->creado_por, 'cliente_id' => $cliente->id],
            ['creado_en' => now()]
        );
    }

    public function mostrar(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        $chat = $this->obtenerOCrearChat($cliente);
        $chat->load(['coach.usuario', 'mensajes.archivos']);

        // Marcar mensajes como leídos
        $chat->marcarComoLeido('cliente');

        return response()->json([
            'datos' => new ChatResource($chat),
        ]);
    }

    public function mensajes(Request $request): PaginacionCollection
    {
        $cliente = $this->getCliente($request);

        // Buscar chat por ambas claves para evitar confusión con duplicados
        $chat = Chat::where('cliente_id', $cliente->id)
            ->where('coach_id', $cliente->creado_por)
            ->firstOrFail();

        $mensajes = $chat->mensajes()
            ->with('archivos')
            ->orderBy('enviado_en', 'desc')
            ->paginate(50);

        return new PaginacionCollection($mensajes, MensajeResource::class);
    }

    public function enviarMensaje(SolicitudEnviarMensaje $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        // Usar firstOrCreate para garantizar consistencia
        $chat = $this->obtenerOCrearChat($cliente);

        $mensaje = Mensaje::create([
            'chat_id' => $chat->id,
            'emisor_tipo' => 'cliente',
            'mensaje' => $request->input('mensaje', ''),
            'enviado_en' => now(),
            'leido' => false,
        ]);

        // Procesar archivos si existen
        if ($request->hasFile('archivos')) {
            $this->procesarArchivos($mensaje, $request->file('archivos'), $chat->id);
        }

        $chat->touch();

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
    public function descargarArchivo(Request $request, int $archivoId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $cliente = $this->getCliente($request);

        // Buscar chat por ambas claves
        $chat = Chat::where('cliente_id', $cliente->id)
            ->where('coach_id', $cliente->creado_por)
            ->firstOrFail();

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

    public function marcarLeido(Request $request): JsonResponse
    {
        $cliente = $this->getCliente($request);

        // Buscar chat por ambas claves
        $chat = Chat::where('cliente_id', $cliente->id)
            ->where('coach_id', $cliente->creado_por)
            ->firstOrFail();

        $chat->marcarComoLeido('cliente');

        return response()->json(['mensaje' => 'Mensajes marcados como leídos.']);
    }
}
