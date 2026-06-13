<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Форматирование UTC-меток заказов для API приложений (часовой пояс Киева).
 */
final class KievDateTimeFormatter
{
    private const KYIV = 'Europe/Kiev';

    private function __construct()
    {
    }

    /**
     * created_at из БД (UTC) → dd.MM.yyyy HH:mm:ss по Киеву.
     */
    public static function formatOrderCreatedAt($value): string
    {
        if ($value === null || $value === '' || $value === '*') {
            return is_string($value) ? $value : '';
        }

        try {
            return Carbon::parse($value, 'UTC')->setTimezone(self::KYIV)->format('d.m.Y H:i:s');
        } catch (\Exception $e) {
            return is_string($value) ? (string) $value : '';
        }
    }

    /**
     * required_time (локальное время подачи с клиента) → dd.MM.yyyy HH:mm.
     */
    public static function formatRequiredTime($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value, self::KYIV)->format('d.m.Y H:i');
        } catch (\Exception $e) {
            return '';
        }
    }
}
