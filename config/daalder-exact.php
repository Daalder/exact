<?php

return [
    'callback_url' => env('EXACT_CLIENT_CALLBACK_URL'),
    'client_id' => env('EXACT_CLIENT_ID'),
    'client_secret' => env('EXACT_CLIENT_SECRET'),
    'division' => env('EXACT_DIVISION'),
    'base_url' => env('EXACT_BASE_URL', 'https://start.exactonline.nl'),
    'webhook_secret' => env('EXACT_WEBHOOK_SECRET'),
];
