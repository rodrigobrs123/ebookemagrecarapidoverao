<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'public_id',
        'download_token',
        'customer_name',
        'email',
        'cpf_cnpj',
        'amount',
        'status',
        'asaas_checkout_id',
        'asaas_payment_id',
        'paid_at',
        'email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(AsaasWebhook::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
