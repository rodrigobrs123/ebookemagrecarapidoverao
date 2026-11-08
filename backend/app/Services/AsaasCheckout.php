<?php

namespace App\Services;

use App\Support\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AsaasCheckout
{
    public function __construct(private readonly PublicUrl $publicUrl) {}

    public function create(Order $order, ?string $origin = null): array
    {
        $cover = storage_path('app/checkout-cover.jpg');
        if (! is_file($cover)) {
            throw new RuntimeException('Imagem do produto não encontrada.');
        }

        try {
            app(AsaasWebhookSync::class)->sync($origin);
        } catch (RuntimeException) {
            // O PIX abre na mesma; o webhook usa a URL pública quando ela existir.
        }

        $response = Http::withHeaders([
            'access_token' => (string) config('services.asaas.key'),
            'User-Agent' => 'ebook-verao',
            'Accept' => 'application/json',
        ])->acceptJson()->post($this->endpoint('/checkouts'), [
            'billingTypes' => ['PIX'],
            'chargeTypes' => ['DETACHED'],
            'minutesToExpire' => 60,
            'externalReference' => $order->public_id,
            'callback' => [
                'successUrl' => $this->appUrl('/pagamento/'.$order->public_id, $origin),
                'cancelUrl' => $this->appUrl('/pagamento/cancelado', $origin),
                'expiredUrl' => $this->appUrl('/pagamento/expirado', $origin),
                'autoRedirect' => true,
            ],
            'items' => [[
                'name' => 'E-book Emagrecer Verao',
                'description' => 'Guia pratico em PDF para homens',
                'quantity' => 1,
                'value' => 12.99,
                'imageBase64' => base64_encode((string) file_get_contents($cover)),
            ]],
        ]);

        if ($response->failed()) {
            $message = collect($response->json('errors') ?? [])
                ->pluck('description')
                ->filter()
                ->implode(' ');

            throw new RuntimeException($message !== '' ? $message : 'Não foi possível abrir o checkout.');
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['id'])) {
            throw new RuntimeException('O ASAAS não devolveu o identificador do checkout.');
        }

        return $data;
    }

    public function syncPayer(Order $order, ?string $customerId = null): void
    {
        if ($order->customer_name && $order->email && $order->cpf_cnpj) {
            return;
        }

        try {
            $paymentId = $order->asaas_payment_id;
            $payment = null;
            if (! is_string($customerId) || $customerId === '') {
                $customerId = null;
                $payment = $this->findPayment($order);
                if ($payment !== null) {
                    $customerId = is_string($payment['customer'] ?? null) ? $payment['customer'] : null;
                    $paymentId = is_string($payment['id'] ?? null) ? $payment['id'] : $paymentId;
                }
            }

            if (! is_string($customerId) || $customerId === '') {
                return;
            }

            $customer = $this->get('/customers/'.$customerId);
            if ($customer === null) {
                return;
            }

            $name = $this->text($customer['name'] ?? null);
            $email = $this->text($customer['email'] ?? null);
            $document = preg_replace('/\D/', '', (string) ($customer['cpfCnpj'] ?? '')) ?? '';

            if ($name !== null) {
                $order->customer_name = $name;
            }
            if ($email !== null) {
                $order->email = $email;
            }
            if ($document !== '') {
                $order->cpf_cnpj = substr($document, 0, 14);
            }
            if (is_string($paymentId) && $paymentId !== '') {
                $order->asaas_payment_id = $paymentId;
            }

            $order->save();
        } catch (\Throwable) {
            // A página do pedido continua disponível se o ASAAS não responder.
        }
    }

    private function findPayment(Order $order): ?array
    {
        $candidates = [];

        if (is_string($order->asaas_payment_id) && $order->asaas_payment_id !== '') {
            $candidates[] = $this->get('/payments/'.$order->asaas_payment_id);
        }

        if (is_string($order->asaas_checkout_id) && $order->asaas_checkout_id !== '') {
            $candidates[] = $this->firstPayment('/payments?checkoutSession='.rawurlencode($order->asaas_checkout_id).'&limit=1');
            if (! str_contains($order->asaas_checkout_id, '-')) {
                $candidates[] = $this->firstPayment('/payments?paymentLink='.rawurlencode($order->asaas_checkout_id).'&limit=1');
            }
        }

        if (is_string($order->public_id) && $order->public_id !== '') {
            $candidates[] = $this->firstPayment('/payments?externalReference='.rawurlencode($order->public_id).'&limit=1');
        }

        foreach ($candidates as $payment) {
            if (is_array($payment) && $this->paymentBelongsToOrder($order, $payment)) {
                return $payment;
            }
        }

        return null;
    }

    private function paymentBelongsToOrder(Order $order, array $payment): bool
    {
        $paymentId = is_string($payment['id'] ?? null) ? $payment['id'] : null;
        $checkoutId = is_string($order->asaas_checkout_id) ? $order->asaas_checkout_id : '';
        $publicId = is_string($order->public_id) ? $order->public_id : '';

        $sameCheckout = $checkoutId !== '' && (
            ($payment['checkoutSession'] ?? null) === $checkoutId
            || ($payment['paymentLink'] ?? null) === $checkoutId
        );
        $sameReference = $publicId !== '' && ($payment['externalReference'] ?? null) === $publicId;
        $sameSavedId = $paymentId !== null
            && $paymentId === $order->asaas_payment_id
            && ($sameCheckout || $sameReference);

        return $sameCheckout || $sameReference || $sameSavedId;
    }

    private function firstPayment(string $path): ?array
    {
        $list = $this->get($path);
        $payment = is_array($list) ? ($list['data'][0] ?? null) : null;

        return is_array($payment) ? $payment : null;
    }

    private function get(string $path): ?array
    {
        $response = Http::withHeaders([
            'access_token' => (string) config('services.asaas.key'),
            'User-Agent' => 'ebook-verao',
            'Accept' => 'application/json',
        ])->acceptJson()->get($this->endpoint($path));

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.asaas.url'), '/').$path;
    }

    private function appUrl(string $path, ?string $origin = null): string
    {
        return rtrim($this->publicUrl->https($origin), '/').$path;
    }
}
