<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

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
     * Уже отформатированную строку dd.MM.yyyy не трогаем (идемпотентно).
     */
    public static function formatOrderCreatedAt($value): string
    {
        if ($value === null || $value === '' || $value === '*') {
            return is_string($value) ? $value : '';
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)
                ->utc()
                ->setTimezone(self::KYIV)
                ->format('d.m.Y H:i:s');
        }

        $str = trim((string) $value);
        if (preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2}$/', $str)) {
            return $str;
        }

        try {
            return Carbon::parse($str, 'UTC')
                ->setTimezone(self::KYIV)
                ->format('d.m.Y H:i:s');
        } catch (\Exception $e) {
            return $str;
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

        if (preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}(:\d{2})?$/', trim((string) $value))) {
            $normalized = trim((string) $value);
            return strlen($normalized) > 16 ? substr($normalized, 0, 16) : $normalized;
        }

        try {
            return Carbon::parse($value, self::KYIV)->format('d.m.Y H:i');
        } catch (\Exception $e) {
            return '';
        }
    }
}
