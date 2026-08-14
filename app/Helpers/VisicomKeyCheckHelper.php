<?php

namespace App\Helpers;

use Carbon\Carbon;

class VisicomKeyCheckHelper
{
    public const DEFAULT_EXPIRES_AT = '2026-09-12';

    /**
     * Сколько полных дней осталось до даты окончания ключа (Киев).
     * 0 = истекает сегодня, отрицательное = уже истёк.
     */
    public static function remainingDays(?string $expiresAt, ?Carbon $now = null): ?int
    {
        $expiresAt = self::normalizeExpiresAt($expiresAt);
        if ($expiresAt === null) {
            return null;
        }

        try {
            $expires = Carbon::parse($expiresAt, TimeHelper::KYIV_TIMEZONE)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        $now = ($now ?? Carbon::now(TimeHelper::KYIV_TIMEZONE))->startOfDay();

        return (int) $now->diffInDays($expires, false);
    }

    public static function formatRemainingText(?string $expiresAt, ?Carbon $now = null): string
    {
        $expiresAt = self::normalizeExpiresAt($expiresAt);
        $remaining = self::remainingDays($expiresAt, $now);
        if ($remaining === null || $expiresAt === null) {
            return 'срок действия ключа не задан (APP_KEY_VISICOM_EXPIRES_AT)';
        }

        try {
            $date = Carbon::parse($expiresAt, TimeHelper::KYIV_TIMEZONE)->format('d.m.Y');
        } catch (\Throwable $e) {
            $date = $expiresAt;
        }

        if ($remaining === 1) {
            return 'остался 1 день (до ' . $date . ')';
        }
        if ($remaining > 0) {
            return 'осталось ' . $remaining . ' ' . self::daysWord($remaining) . ' (до ' . $date . ')';
        }
        if ($remaining === 0) {
            return 'срок истекает сегодня (' . $date . ')';
        }

        $ago = abs($remaining);

        return 'срок истёк ' . $ago . ' ' . self::daysWord($ago) . ' назад (был до ' . $date . ')';
    }

    public static function buildTelegramMessage(
        bool $ok,
        int $httpStatus,
        string $environmentLabel,
        string $remainingText
    ): string {
        $check = $ok
            ? 'успешна'
            : 'ошибка HTTP ' . $httpStatus;

        return 'Проверка ключа Visicom (' . $environmentLabel . '): ' . $check . '. ' . $remainingText . '.';
    }

    public static function environmentLabel(?string $appName, ?string $appUrl): string
    {
        $host = parse_url((string) $appUrl, PHP_URL_HOST);
        $name = trim((string) $appName);
        if ($host) {
            return $name !== '' ? $name . ', ' . $host : $host;
        }

        return $name !== '' ? $name : 'unknown';
    }

    public static function daysWord(int $days): string
    {
        $n = abs($days) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return 'дней';
        }
        if ($n1 === 1) {
            return 'день';
        }
        if ($n1 >= 2 && $n1 <= 4) {
            return 'дня';
        }

        return 'дней';
    }

    public static function normalizeExpiresAt(?string $expiresAt): ?string
    {
        $expiresAt = trim((string) $expiresAt);
        if ($expiresAt === '') {
            return self::DEFAULT_EXPIRES_AT;
        }

        return $expiresAt;
    }
}
