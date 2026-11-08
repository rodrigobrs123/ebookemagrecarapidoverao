<?php

namespace App\Console\Commands;

use App\Services\AsaasWebhookSync;
use App\Services\PublicUrl;
use Illuminate\Console\Command;
use RuntimeException;

class SyncNgrokWebhook extends Command
{
    protected $signature = 'asaas:sync-ngrok';

    protected $description = 'Publica a URL HTTPS do Ngrok no webhook do ASAAS';

    public function handle(PublicUrl $publicUrl, AsaasWebhookSync $webhooks): int
    {
        try {
            $url = $webhooks->sync();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Webhook do ASAAS apontando para '.$url);
        $this->line('URL pública: '.$publicUrl->https());

        return self::SUCCESS;
    }
}
