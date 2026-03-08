<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Centrifugo configuration
    |--------------------------------------------------------------------------
    */

    'api_url' => env('CENTRIFUGO_API_URL', 'http://localhost:8008/api'),

    'api_key' => env('CENTRIFUGO_API_KEY', 'my-api-key'),

    'secret' => env('CENTRIFUGO_SECRET', 'my-secret-key'),

    'verify_ssl' => env('CENTRIFUGO_VERIFY_SSL', false),

    'timeout' => env('CENTRIFUGO_TIMEOUT', 5),
];
