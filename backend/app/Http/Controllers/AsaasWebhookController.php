<?php

namespace App\Http\Controllers;

use App\Models\AsaasWebhook;
use App\Models\Order;
use App\Services\AsaasCheckout;
use App\Services\BrevoMailer;
use App\Services\OrderStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        AsaasCheckout $asaas,
        BrevoMailer $brevo,
        OrderStore $orders,
    ): JsonResponse {
        $expected = (string) config('services.asaas.webhook_token');
        $received = (string) $request->header('asaas-access-token', '');

        if ($expected === '' || ! hash_equals($expected, $received)) {
            return response()->json(['message' => 'Token do webhook inválido.'], 401);
        }

        $payload = $request->json()->all();
        $order = $this->findOrder($orders, $payload);
        $event = isset($payload['event']) ? (string) $payload['event'] : null;
        $eventId = data_get($payload, 'id');

        if (is_string($eventId) && $eventId !== '') {
            AsaasWebhook::query()->updateOrCreate(
                ['event_id' => $eventId],
                [
                    'order_id' => $order?->id,
                    'event' => $event,
                    'payload' => $payload,
                ],
            );
        } else {
            AsaasWebhook::query()->create([
                'order_id' => $order?->id,
                'event' => $event,
                'payload' => $payload,
            ]);
        }

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
                        'public_id' => $order->public_id,
                        'message' => $exception->getMessage(),
                    ]);
                }
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

    private function findOrder(OrderStore $orders, array $payload): ?Order
    {
        $checkoutId = data_get($payload, 'checkout.id')
            ?? data_get($payload, 'payment.checkoutSession');
        $external = data_get($payload, 'checkout.externalReference')
            ?? data_get($payload, 'payment.externalReference');
        $paymentLink = data_get($payload, 'payment.paymentLink');

        if (is_string($checkoutId) && $checkoutId !== '') {
            $order = $orders->findByCheckoutId($checkoutId);
            if ($order !== null) {
                return $order;
            }
        }

        if (is_string($paymentLink) && $paymentLink !== '') {
            $order = $orders->findByCheckoutId($paymentLink);
            if ($order !== null) {
                return $order;
            }
        }

        if (is_string($external) && $external !== '') {
            return $orders->findByPublicId($external);
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
