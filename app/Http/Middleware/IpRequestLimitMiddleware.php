<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class IpRequestLimitMiddleware
{
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        $key = 'ip_request_limit_' . $ip;
        $maxRequests = 20; // Максимальное количество запросов за час

        // Используем RateLimiter для установки ограничения
        RateLimiter::for($key, function () use ($maxRequests, $ip) {
            return Limit::perHour($maxRequests)->response(function () {
                return response('Too many requests.', 429);
            });
        });

        // Проверяем, не превышено ли максимальное количество попыток
        if (RateLimiter::tooManyAttempts($key, $maxRequests)) {
            Log::info('IP Request Limiter Middleware', [
                'IP' => $ip,
                'Attempts' => RateLimiter::attempts($key),
                'Blocked' => true,
            ]);

            return response('Too many requests.', 429); // HTTP 429 - Too Many Requests
        }

        // Увеличиваем счетчик попыток
        RateLimiter::hit($key);

        Log::info('IP Request Limiter Middleware', [
            'IP' => $ip,
            'Attempts' => RateLimiter::attempts($key),
            'Blocked' => false,
        ]);

        return $next($request);
    }
}

