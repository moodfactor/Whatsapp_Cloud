<?php

return [
    // Default single config (for backward compatibility)
    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v16.0/'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', null),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', null),
    'webhook_secret' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', env('WHATSAPP_WEBHOOK_SECRET', null)),

    'retry' => [
        'max_attempts' => env('WHATSAPP_RETRY_MAX', 3),
        'backoff_factor' => env('WHATSAPP_RETRY_BACKOFF', 2),
    ],

    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING', true),
        'log_level' => env('WHATSAPP_LOG_LEVEL', 'error'),
    ],

    // Multi-account configurations. Define one or more accounts by key.
    'accounts' => [
        'default' => [
            'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v16.0/'),
            'access_token' => env('WHATSAPP_ACCESS_TOKEN', null),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', null),
        ],
        // Example for a second account:
        // 'account2' => [
        //     'api_url' => 'https://graph.facebook.com/v16.0/',
        //     'access_token' => 'second_access_token',
        //     'phone_number_id' => 'second_phone_number_id',
        // ],
    ],
];
