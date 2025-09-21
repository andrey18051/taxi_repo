<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/webhook',
        '/webhookViber',
        '/webhookViberCustoms',
        '/server-callback',
        '/serviceUrl',
        '/serviceUrl/verify',
        'upload-log',
    ];

    protected function tokensMatch($request)
    {
        $match = parent::tokensMatch($request);
        if (!$match) {
            Log::error('CSRF token mismatch', [
                'request_token' => $request->session()->token(),
                'form_token' => $request->input('_token'),
            ]);
        }
        return $match;
    }

}
