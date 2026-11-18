<?php

namespace App\Support;

use App\Services\OrderStatusMessageResolver;
use Symfony\Component\HttpFoundation\Response;

/**
 * getOrderStatusMessageResultPush возвращает JsonResponse.
 * json_decode() от объекта берёт строку с HTTP-заголовками и не видит action.
 */
final class DispatchStatusPayload
{
    /**
     * @param mixed $response
     * @return array<string, mixed>|null
     */
    public static function decode($response): ?array
    {
        if ($response instanceof Response) {
            $decoded = json_decode($response->getContent(), true);

            return is_array($decoded) ? $decoded : null;
        }

        if (is_array($response)) {
            return $response;
        }

        if (!is_string($response) || trim($response) === '') {
            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param mixed $response
     */
    public static function action($response): ?string
    {
        $payload = self::decode($response);
        $action = $payload['action'] ?? null;

        return is_string($action) && $action !== '' ? $action : null;
    }

    /**
     * @param mixed $response
     */
    public static function isCanceled($response): bool
    {
        return self::action($response) === OrderStatusMessageResolver::ACTION_CANCELED;
    }
}
