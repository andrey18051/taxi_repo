<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Centrifugo configuration
    |--------------------------------------------------------------------------
    */

    'api_url' => env('CENTRIFUGO_API_URL', 'http://localhost:8008'),

    'api_key' => env('CENTRIFUGO_API_KEY', '0oBHyGSqni09Pzk-Hx5bHxdhjWPI1cV8Or-1UFF0IRtSgumKHqBEHaBWLps6KHu9_1SE-ZCyCfCHnr3f8IhSmQ'),

    'secret' => env('CENTRIFUGO_SECRET', 'my-secret-key'),

    'verify_ssl' => env('CENTRIFUGO_VERIFY_SSL', false),

    'timeout' => env('CENTRIFUGO_TIMEOUT', 5),
];
