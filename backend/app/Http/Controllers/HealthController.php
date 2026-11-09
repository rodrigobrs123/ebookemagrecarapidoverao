<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbOk = false;
        $ordersOk = false;
        $dbError = null;

        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $ordersOk = Schema::hasTable('orders');
        } catch (\Throwable $exception) {
            $dbError = class_basename($exception).': '.$exception->getMessage();
        }

        $status = $dbOk && $ordersOk ? 200 : 503;

        return response()->json([
            'ok' => $dbOk && $ordersOk,
            'app' => config('app.name'),
            'env' => config('app.env'),
            'db_connection' => config('database.default'),
            'database_url_set' => filled((string) config('database.connections.mysql.url')),
            'db_ok' => $dbOk,
            'orders_table' => $ordersOk,
            'ebook' => is_file((string) (app(\App\Services\EbookPath::class)->resolve() ?? '')),
            'cover' => is_file(storage_path('app/checkout-cover.jpg')),
            'db_error' => $dbError,
        ], $status);
    }
}
