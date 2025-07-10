<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => '834246822304-e6vofmt6sbvu7c4n0dhvn6ni0jilhpoa.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect' => '/auth/google/callback',

    ],

    /**
     * Facebook
     */
    'facebook' => [
        'client_id' => '652484086549567',
        'client_secret' => 'd42d737772102bfd9d8ec694395256d3',
        'redirect' => 'https://m.easy-order-taxi.site/auth/facebook/callback',
    ],


    /**
     * Linkedin
     */
    'linkedin' => [
        'client_id' => '77u506g57pz2vx',
        'client_secret' => 'SdNOUJMZ3gZxscIP',
        'redirect' => 'https://m.easy-order-taxi.site/auth/linkedin/callback',
    ],

    /**
     * Github
     */
    'github' => [
        'client_id' => '1522b13c28b84a7e6f86',
        'client_secret' => '04e51fca26b5514aaaefaf03bb5f59d98af11ae7',
        'redirect' => '/auth/github/callback',
    ],

    /**
     * Twitter
     * Bearer Token
     * AAAAAAAAAAAAAAAAAAAAAAqXjAEAAAAA5SyAPUTr5dnGEjDdBTI7dzlptgc%3DYIeMkzm92UAh7X4U3G3QFVxE6d6T00kwoOrxgxwzj7kwQ7JJss
     */
    'twitter' => [
        'client_id' => '8bMImTM999qg1KcrUOPCZNCQF',
        'client_secret' => '9yXk05UyYgTnoZsTA0Fa0vHnoLHs66YonDio0iGP2xOxpaK7p7',
        'redirect' => 'https://m.easy-order-taxi.site/auth/twitter/callback',
    ],

    'telegram' => [
        'bot' => env('TELEGRAM_BOT_NAME'),  // The bot's username
        'client_id' => null,
        'client_secret' => env('TELEGRAM_TOKEN'),
        'redirect' => env('TELEGRAM_REDIRECT_URI'),
    ],

    'city_app_order' => [
        'cache_ttl' => 300, // Время жизни кэша в секундах
        'curl_timeout' => 6, // Таймаут подключения cURL в секундах
    ],
];
