<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->ensureOrdersSchema();
    }

    /**
     * EthioDeploy: se o Start Command não rodar migrate, cria as tabelas na 1ª request.
     */
    private function ensureOrdersSchema(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        if (config('database.default') !== 'mysql') {
            return;
        }

        try {
            if (Schema::hasTable('orders')) {
                return;
            }

            Artisan::call('migrate', ['--force' => true]);
            Log::info('Migrations aplicadas automaticamente (tabela orders ausente).', [
                'output' => Artisan::output(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Falha ao aplicar migrations automaticamente.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
