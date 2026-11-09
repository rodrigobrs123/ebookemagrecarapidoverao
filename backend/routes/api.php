<?php

use App\Http\Controllers\AsaasWebhookController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PublicUrlController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/ngrok', PublicUrlController::class);
Route::post('/checkout', [CheckoutController::class, 'store']);
Route::get('/orders/{publicId}', [CheckoutController::class, 'show']);
Route::get('/downloads/{token}', DownloadController::class);
Route::post('/webhooks/asaas', AsaasWebhookController::class);
