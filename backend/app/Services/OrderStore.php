<?php

namespace App\Services;

use App\Models\Order;

class OrderStore
{
    public function create(array $attributes): Order
    {
        return Order::query()->create($attributes);
    }

    public function findByPublicId(string $publicId): ?Order
    {
        if ($publicId === '') {
            return null;
        }

        return Order::query()->where('public_id', $publicId)->first();
    }

    public function findByCheckoutId(string $checkoutId): ?Order
    {
        if ($checkoutId === '') {
            return null;
        }

        return Order::query()->where('asaas_checkout_id', $checkoutId)->first();
    }

    public function findByDownloadToken(string $token): ?Order
    {
        if ($token === '') {
            return null;
        }

        return Order::query()->where('download_token', $token)->first();
    }
}
