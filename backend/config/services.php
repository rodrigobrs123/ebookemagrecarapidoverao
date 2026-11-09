<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'asaas' => [
        'url' => env('ASAAS_BASE_URL', 'https://sandbox.asaas.com/api/v3'),
        'key' => env('ASAAS_API_KEY'),
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
        'ebook_path' => env('EBOOK_PATH', storage_path('app/private/ebook.pdf')),
    ],

    'ngrok' => [
        'api' => env('NGROK_API_URL', 'http://ngrok:4040'),
        'url' => env('NGROK_URL'),
        'port' => (int) env('HTTP_PORT', 8080),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'sender_email' => env('BREVO_SENDER_EMAIL'),
        'sender_name' => env('BREVO_SENDER_NAME', 'Loja Virtual RBS'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
