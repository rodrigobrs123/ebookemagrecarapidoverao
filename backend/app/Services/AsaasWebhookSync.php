<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AsaasWebhookSync
{
    private const WEBHOOK_NAME = 'WEBHOOK_ASAAS_EBOOK_FITNESS';

    public function __construct(private readonly PublicUrl $publicUrl) {}

    public function sync(?string $origin = null): string
    {
        $base = $this->publicUrl->https($origin);
        $webhookUrl = $base.'/api/webhooks/asaas';
        $host = (string) parse_url($base, PHP_URL_HOST);

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('URL pública indisponível.');
        }

        $payload = [
            'name' => self::WEBHOOK_NAME,
            'url' => $webhookUrl,
            'email' => $this->accountEmail(),
            'enabled' => true,
            'interrupted' => false,
            'apiVersion' => 3,
            'authToken' => (string) config('services.asaas.webhook_token'),
            'sendType' => 'SEQUENTIALLY',
            'events' => [
                'PAYMENT_CREATED',
                'PAYMENT_UPDATED',
                'PAYMENT_CONFIRMED',
                'PAYMENT_RECEIVED',
                'PAYMENT_OVERDUE',
                'PAYMENT_DELETED',
                'PAYMENT_RESTORED',
                'PAYMENT_REFUNDED',
                'CHECKOUT_CANCELED',
                'CHECKOUT_EXPIRED',
            ],
        ];

        $existing = $this->findConfigured();
        if ($existing !== null) {
            $id = (string) $existing['id'];
            $sameUrl = ($existing['url'] ?? null) === $webhookUrl;
            $sameEvents = $this->sameEvents($existing['events'] ?? [], $payload['events']);
            $sameVersion = (int) ($existing['apiVersion'] ?? 0) === 3;
            if ($sameUrl && $sameEvents && $sameVersion && ($existing['enabled'] ?? false) === true) {
                return $webhookUrl;
            }
            $this->request('PUT', '/webhooks/'.$id, $payload);
        } else {
            $this->request('POST', '/webhooks', $payload);
        }

        return $webhookUrl;
    }

    private function findConfigured(): ?array
    {
        $list = $this->request('GET', '/webhooks');
        foreach ($list['data'] ?? [] as $webhook) {
            $name = (string) ($webhook['name'] ?? '');
            if ($name === self::WEBHOOK_NAME || $name === 'ebook-verao') {
                return $webhook;
            }
        }

        return null;
    }

    private function sameEvents(mixed $current, array $expected): bool
    {
        if (! is_array($current)) {
            return false;
        }

        $left = array_values(array_unique(array_map('strval', $current)));
        $right = array_values(array_unique(array_map('strval', $expected)));
        sort($left);
        sort($right);

        return $left === $right;
    }

    private function accountEmail(): string
    {
        $account = $this->request('GET', '/myAccount');
        $email = $account['email'] ?? null;

        return is_string($email) && $email !== '' ? $email : 'webhook@example.com';
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $pending = Http::withHeaders([
            'access_token' => (string) config('services.asaas.key'),
            'User-Agent' => 'ebook-verao',
            'Accept' => 'application/json',
        ])->acceptJson();

        $url = rtrim((string) config('services.asaas.url'), '/').$path;
        $response = $body === null
            ? $pending->send($method, $url)
            : $pending->send($method, $url, ['json' => $body]);

        if ($response->failed()) {
            $message = collect($response->json('errors') ?? [])
                ->pluck('description')
                ->filter()
                ->implode(' ');

            throw new RuntimeException($message !== '' ? $message : 'Não foi possível configurar o webhook do ASAAS.');
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }
}
