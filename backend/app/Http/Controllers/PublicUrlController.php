<?php

namespace App\Http\Controllers;

use App\Services\PublicUrl;
use Illuminate\Http\JsonResponse;

class PublicUrlController extends Controller
{
    public function __invoke(PublicUrl $publicUrl): JsonResponse
    {
        $port = $publicUrl->port();
        $https = $publicUrl->https();
        $ngrok = $publicUrl->isNgrok();

        return response()->json([
            'port' => $port,
            'localUrl' => 'http://localhost:'.$port,
            'publicUrl' => $ngrok ? $https : null,
            'webhookUrl' => $ngrok ? $https.'/api/webhooks/asaas' : null,
            'ngrokCommand' => 'ngrok http '.$port,
        ]);
    }
}
