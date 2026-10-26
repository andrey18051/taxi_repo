<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Кто запросил отмену на нашем беке. Пока метка есть, внезапное снятие
 * с сервера такси не пересоздаёт заказ (клиент, неоплата, таймер).
 */
final class OrderCancelOrigin
{
    public const CLIENT = 'client';

    public const PAYMENT_TIMEOUT = 'payment_timeout';

    public const AUTO_CANCEL = 'auto_cancel';

    private const PREFIX = 'order_cancel_origin:';

    public static function normalize(?string $initiator): string
    {
        if ($initiator === self::PAYMENT_TIMEOUT || $initiator === self::AUTO_CANCEL) {
            return $initiator;
        }

        return self::CLIENT;
    }

    public static function mark(string $uid, ?string $origin = null): void
    {
        $uid = trim($uid);
        if ($uid === '') {
            return;
        }

        Cache::put(self::PREFIX . $uid, self::normalize($origin), now()->addHours(3));
    }

    public static function blocksRecreate(string $uid): bool
    {
        $uid = trim($uid);
        if ($uid === '') {
            return false;
        }

        $value = Cache::get(self::PREFIX . $uid);

        return is_string($value) && $value !== '';
    }
}
