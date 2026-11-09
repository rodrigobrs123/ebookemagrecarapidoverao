<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('download_token', 80)->unique();
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable();
            $table->string('cpf_cnpj', 14)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->string('asaas_checkout_id')->nullable()->index();
            $table->string('asaas_payment_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asaas_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->nullable()->index();
            $table->string('event_id')->nullable()->unique();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asaas_webhooks');
        Schema::dropIfExists('orders');
    }
};
