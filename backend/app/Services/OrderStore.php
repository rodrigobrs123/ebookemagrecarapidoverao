<?php

namespace App\Services;

use App\Support\Order;
use Illuminate\Support\Facades\File;

class OrderStore
{
    public function create(array $attributes): Order
    {
        $order = Order::make($attributes);
        $this->save($order);

        return $order;
    }

    public function save(Order $order): void
    {
        $data = $order->toArray();
        $publicId = (string) ($data['public_id'] ?? '');
        if ($publicId === '') {
            return;
        }

        $path = $this->path($publicId);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function findByPublicId(string $publicId): ?Order
    {
        if ($publicId === '' || ! is_file($this->path($publicId))) {
            return null;
        }

        $data = json_decode((string) File::get($this->path($publicId)), true);

        return is_array($data) ? Order::make($data) : null;
    }

    public function findByCheckoutId(string $checkoutId): ?Order
    {
        return $this->firstWhere(fn (array $data) => ($data['asaas_checkout_id'] ?? null) === $checkoutId);
    }

    public function findByDownloadToken(string $token): ?Order
    {
        return $this->firstWhere(fn (array $data) => ($data['download_token'] ?? null) === $token);
    }

    private function firstWhere(callable $matcher): ?Order
    {
        $dir = storage_path('app/orders');
        if (! is_dir($dir)) {
            return null;
        }

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $data = json_decode((string) File::get($file->getPathname()), true);
            if (is_array($data) && $matcher($data)) {
                return Order::make($data);
            }
        }

        return null;
    }

    private function path(string $publicId): string
    {
        return storage_path('app/orders/'.$publicId.'.json');
    }
}
