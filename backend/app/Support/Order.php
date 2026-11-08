<?php

namespace App\Support;

use App\Services\OrderStore;

class Order
{
    public function __construct(private array $attributes = []) {}

    public static function make(array $attributes): self
    {
        return new self($attributes);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    public function isPaid(): bool
    {
        return ($this->attributes['status'] ?? null) === 'paid';
    }

    public function save(): void
    {
        app(OrderStore::class)->save($this);
    }

    public function refresh(): self
    {
        $fresh = app(OrderStore::class)->findByPublicId((string) ($this->attributes['public_id'] ?? ''));
        if ($fresh !== null) {
            $this->attributes = $fresh->toArray();
        }

        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
