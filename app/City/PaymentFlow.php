<?php

namespace App\City;

final class PaymentFlow
{
    public const OFF = 0;
    /** Пара заказов, опрос статусов матрицей (после 12.06.2026). */
    public const FORK = 1;
    /** Одиночный безнал, без вилки. */
    public const SIMPLE = 2;
    /** Пара заказов, опрос статусов вложенным switch до рефакторинга 12.06.2026. */
    public const FORK_LEGACY = 3;

    public static function normalize($value): int
    {
        $normalized = (int) $value;

        if (in_array($normalized, [self::OFF, self::FORK, self::SIMPLE, self::FORK_LEGACY], true)) {
            return $normalized;
        }

        return self::OFF;
    }

    public static function usesLegacyForkStatusPoll($value): bool
    {
        return self::normalize($value) === self::FORK_LEGACY;
    }
}
