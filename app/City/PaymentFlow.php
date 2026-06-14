<?php

namespace App\City;

final class PaymentFlow
{
    public const OFF = 0;
    public const FORK = 1;
    public const SIMPLE = 2;

    public static function normalize($value): int
    {
        $normalized = (int) $value;

        if (in_array($normalized, [self::OFF, self::FORK, self::SIMPLE], true)) {
            return $normalized;
        }

        return self::OFF;
    }
}
