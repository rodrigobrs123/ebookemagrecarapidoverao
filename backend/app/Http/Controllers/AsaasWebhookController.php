<?php

namespace App\Http\Controllers;

use App\Models\AsaasWebhook;
use App\Models\Order;
use App\Services\AsaasCheckout;
use App\Services\BrevoMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function __invoke(Request $request, AsaasCheckout $asaas, BrevoMailer $brevo): JsonResponse
    {
        $expected = (string) config('services.asaas.webhook_token');
        $received = (string) $request->header('asaas-access-token', '');

        if ($expected === '' || ! hash_equals($expected, $received)) {
            return response()->json(['message' => 'Token do webhook inválido.'], 401);
        }

        $payload = $request->json()->all();
        $eventId = isset($payload['id']) ? (string) $payload['id'] : null;

        if ($eventId !== null && AsaasWebhook::query()->where('event_id', $eventId)->exists()) {
            return response()->json(['ok' => true]);
        }

        $order = $this->findOrder($payload);
        $event = isset($payload['event']) ? (string) $payload['event'] : null;

        $webhook = AsaasWebhook::create([
            'order_id' => $order?->id,
            'event' => $event,
            'event_id' => $eventId,
            'payload' => $payload,
        ]);

        if ($order !== null) {
            $paymentId = data_get($payload, 'payment.id');
            if (is_string($paymentId) && $paymentId !== '') {
                $order->asaas_payment_id = $paymentId;
            }

            $becamePaid = false;
            if ($this->paymentReceived($payload) && ! $order->isPaid()) {
                $order->status = 'paid';
                $order->paid_at = now();
                $becamePaid = true;
            } elseif (! $order->isPaid() && in_array($event, ['CHECKOUT_CANCELED', 'CHECKOUT_EXPIRED'], true)) {
                $order->status = $event === 'CHECKOUT_EXPIRED' ? 'expired' : 'canceled';
            }

            $order->save();
            $asaas->syncPayer($order, $this->customerId($payload));
            $order->refresh();

            if (($becamePaid || $order->isPaid()) && $order->email_sent_at === null) {
                try {
                    $brevo->sendPurchaseEmail($order);
                } catch (\Throwable $exception) {
                    Log::warning('E-mail Brevo não enviado', [
                        'order_id' => $order->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            if ($webhook->order_id === null) {
                $webhook->order_id = $order->id;
                $webhook->save();
            }
        }

        return response()->json(['ok' => true]);
    }

    private function paymentReceived(array $payload): bool
    {
        $event = (string) ($payload['event'] ?? '');
        $paymentStatus = (string) data_get($payload, 'payment.status', '');

        return $event === 'PAYMENT_RECEIVED'
            || $paymentStatus === 'RECEIVED';
    }

    private function findOrder(array $payload): ?Order
    {
        $checkoutId = data_get($payload, 'checkout.id')
            ?? data_get($payload, 'payment.checkoutSession');
        $external = data_get($payload, 'checkout.externalReference')
            ?? data_get($payload, 'payment.externalReference');
        $paymentId = data_get($payload, 'payment.id');
        $paymentLink = data_get($payload, 'payment.paymentLink');

        if (is_string($checkoutId) && $checkoutId !== '') {
            $order = Order::query()->where('asaas_checkout_id', $checkoutId)->first();
            if ($order !== null) {
                return $order;
            }
        }

        if (is_string($paymentLink) && $paymentLink !== '') {
            $order = Order::query()->where('asaas_checkout_id', $paymentLink)->first();
            if ($order !== null) {
                return $order;
            }
        }

        if (is_string($external) && $external !== '') {
            return Order::query()->where('public_id', $external)->first();
        }

        return null;
    }

    private function customerId(array $payload): ?string
    {
        $customer = data_get($payload, 'checkout.customer') ?? data_get($payload, 'payment.customer');
        if (is_string($customer) && $customer !== '') {
            return $customer;
        }
        if (is_array($customer) && is_string($customer['id'] ?? null) && $customer['id'] !== '') {
            return $customer['id'];
        }

        return null;
    }
}
