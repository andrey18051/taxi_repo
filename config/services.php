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

    /**
     * Google online
     */
    /*'google' => [
        'client_id' => '834246822304-e6vofmt6sbvu7c4n0dhvn6ni0jilhpoa.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect' => '/auth/google/callback',

    ],*/

    /**
     * Google local
     */
    'google' => [
        'client_id' => '834246822304-1aclo0nle41o9q02crkrvc79ik6gu55d.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect' => '/auth/google/callback',
    ],

    /**
     * Facebook
     */
    'facebook' => [
        'client_id' => '652484086549567',
        'client_secret' => 'd42d737772102bfd9d8ec694395256d3',
        'redirect' => '/auth/facebook/callback',
    ],


];
