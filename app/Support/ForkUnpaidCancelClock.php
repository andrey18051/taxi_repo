<?php

namespace App\Support;

/**
 * Неоплаченная вилка снимается через минуту от первого заказа клиента.
 * Новая пара после отказа водителя или диспетчера этот отсчёт не сдвигает.
 */
class ForkUnpaidCancelClock
{
    public const THRESHOLD_SECONDS = 50;

    /**
     * @param mixed $originCreatedAt
     * @param mixed $latestCreatedAt
     */
    public static function clockTimestamp($originCreatedAt, $latestCreatedAt): ?int
    {
        $times = [];
        foreach ([$originCreatedAt, $latestCreatedAt] as $value) {
            $timestamp = self::toTimestamp($value);
            if ($timestamp !== null) {
                $times[] = $timestamp;
            }
        }

        return $times === [] ? null : min($times);
    }

    public static function stillInsideWindow(?int $createdAtTimestamp, int $nowTimestamp): bool
    {
        if ($createdAtTimestamp === null || $createdAtTimestamp <= 0) {
            return false;
        }

        return ($nowTimestamp - $createdAtTimestamp) <= self::THRESHOLD_SECONDS;
    }

    /**
     * @param mixed $value
     */
    private static function toTimestamp($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $timestamp = (int) $value;

            return $timestamp > 0 ? $timestamp : null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false || $timestamp <= 0 ? null : $timestamp;
    }
}
