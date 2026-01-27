<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ControladorStripe extends Controller
{
    /**
     * Manejar webhooks de Stripe.
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        // TODO: Verificar firma de Stripe
        // try {
        //     $event = \Stripe\Webhook::constructEvent(
        //         $payload, $sigHeader, $endpointSecret
        //     );
        // } catch (\Exception $e) {
        //     Log::error('Webhook Stripe: Firma inválida', ['error' => $e->getMessage()]);
        //     return response('Firma inválida', 400);
        // }

        $event = json_decode($payload, true);
        $eventType = $event['type'] ?? null;

        Log::info('Webhook Stripe recibido', ['type' => $eventType]);

        switch ($eventType) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event['data']['object']);
                break;

            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event['data']['object']);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event['data']['object']);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event['data']['object']);
                break;

            case 'invoice.paid':
                $this->handleInvoicePaid($event['data']['object']);
                break;

            default:
                Log::info('Webhook Stripe: Evento no manejado', ['type' => $eventType]);
        }

        return response('OK', 200);
    }

    private function handlePaymentSucceeded(array $paymentIntent): void
    {
        Log::info('Pago exitoso', ['payment_intent' => $paymentIntent['id']]);
        // TODO: Implementar lógica de pago exitoso
    }

    private function handlePaymentFailed(array $paymentIntent): void
    {
        Log::warning('Pago fallido', ['payment_intent' => $paymentIntent['id']]);
        // TODO: Implementar lógica de pago fallido
    }

    private function handleSubscriptionCreated(array $subscription): void
    {
        Log::info('Suscripción creada', ['subscription' => $subscription['id']]);
        // TODO: Implementar lógica de suscripción creada
    }

    private function handleSubscriptionUpdated(array $subscription): void
    {
        Log::info('Suscripción actualizada', ['subscription' => $subscription['id']]);
        // TODO: Implementar lógica de suscripción actualizada
    }

    private function handleSubscriptionDeleted(array $subscription): void
    {
        Log::info('Suscripción cancelada', ['subscription' => $subscription['id']]);
        // TODO: Implementar lógica de suscripción cancelada
    }

    private function handleInvoicePaid(array $invoice): void
    {
        Log::info('Factura pagada', ['invoice' => $invoice['id']]);
        // TODO: Implementar lógica de factura pagada
    }
}
