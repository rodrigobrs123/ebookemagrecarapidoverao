<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PublicUrl
{
    public function https(?string $hint = null): string
    {
        foreach ([$hint, config('services.ngrok.url')] as $candidate) {
            $url = rtrim((string) $candidate, '/');
            if ($this->looksPublic($url)) {
                return $url;
            }
        }

        $fromRequest = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        if ($this->looksPublic($fromRequest)) {
            return $fromRequest;
        }

        $fromNgrok = $this->fromNgrok();
        if ($fromNgrok !== null) {
            return $fromNgrok;
        }

        $base = rtrim((string) config('app.url'), '/');
        if (parse_url($base, PHP_URL_HOST) === 'localhost') {
            $base = str_replace('://localhost', '://127.0.0.1', $base);
        }

        return $base;
    }

    public function port(): int
    {
        $port = (int) config('services.ngrok.port');

        return $port > 0 ? $port : 8080;
    }

    public function isNgrok(): bool
    {
        return $this->looksPublic($this->https());
    }

    private function looksPublic(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return false;
        }

        return str_contains($host, 'ngrok')
            || str_contains($host, 'ethiodeploy.app')
            || str_starts_with($url, 'https://');
    }

    private function fromNgrok(): ?string
    {
        foreach ($this->apis() as $api) {
            try {
                $response = Http::timeout(2)->acceptJson()->get($api.'/api/tunnels');
                if ($response->failed()) {
                    continue;
                }

                foreach ($response->json('tunnels') ?? [] as $tunnel) {
                    $url = rtrim((string) ($tunnel['public_url'] ?? ''), '/');
                    if (str_starts_with($url, 'https://')) {
                        return $url;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function apis(): array
    {
        return array_values(array_unique(array_filter([
            rtrim((string) config('services.ngrok.api'), '/'),
            'http://ngrok:4040',
            'http://host.docker.internal:4040',
        ])));
    }
}
