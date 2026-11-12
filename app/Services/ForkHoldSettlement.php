<?php

namespace App\Services;

/**
 * Холд вилки закрывается только когда закрыт каждый заказ вилки, не первая пара.
 */
final class ForkHoldSettlement
{
    public const KEEP = 'keep';
    public const SETTLE = 'settle';
    public const REFUND = 'refund';

    /**
     * @param list<int|string|null> $closeReasons
     */
    public static function decide(array $closeReasons): string
    {
        if ($closeReasons === []) {
            return self::KEEP;
        }

        $hasSuccess = false;
        foreach ($closeReasons as $reason) {
            $normalized = trim((string) $reason);
            if ($normalized === '' || $normalized === '-1') {
                return self::KEEP;
            }
            if ($normalized === '0' || $normalized === '8') {
                $hasSuccess = true;
            }
        }

        return $hasSuccess ? self::SETTLE : self::REFUND;
    }
}
