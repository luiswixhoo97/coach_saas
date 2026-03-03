<?php

namespace App\Services\Notificaciones;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class ServicioNotificacionesPush
{
    private ?Messaging $messaging = null;

    private function getMessaging(): ?Messaging
    {
        if ($this->messaging !== null) {
            return $this->messaging;
        }
        $path = config('services.firebase.credentials');
        if (!$path || !is_file($path)) {
            return null;
        }
        try {
            $this->messaging = app(Messaging::class);
            return $this->messaging;
        } catch (\Throwable $e) {
            Log::warning('Firebase Messaging no disponible: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Envía una notificación push a los dispositivos de un usuario.
     */
    public function enviarAUsuario(User $user, string $titulo, string $cuerpo, array $data = []): void
    {
        $messaging = $this->getMessaging();
        if (!$messaging) {
            return;
        }

        $tokens = $user->dispositivos()->pluck('fcm_token')->filter()->values()->all();
        if (empty($tokens)) {
            return;
        }

        $notification = Notification::create($titulo, $cuerpo);

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);

                $messaging->send($message);
            } catch (\Throwable $e) {
                Log::warning('Error enviando push a token: ' . $e->getMessage());
            }
        }
    }

    /**
     * Envía notificación de nuevo mensaje de chat al destinatario.
     */
    public function notificarNuevoMensajeChat(User $destinatario, string $nombreRemitente, string $previewMensaje, int $chatId): void
    {
        $this->enviarAUsuario($destinatario, 'Nuevo mensaje de ' . $nombreRemitente, $previewMensaje, [
            'tipo' => 'chat',
            'chat_id' => (string) $chatId,
        ]);
    }
}
