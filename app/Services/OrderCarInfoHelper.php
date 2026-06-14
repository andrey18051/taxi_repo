<?php

namespace App\Services;

/**
 * Форматирование orderweb.auto для ответа приложению.
 * Поддерживает JSON из PAS и строку vod «номер, цвет, марка модель, телефон».
 */
class OrderCarInfoHelper
{
    /**
     * @return array{order_car_info: string, driver_phone: ?string}|null
     */
    public static function formatForApp(?string $storedAuto): ?array
    {
        if ($storedAuto === null) {
            return null;
        }

        $trimmed = trim($storedAuto);
        if ($trimmed === '' || self::isMalformedPlaceholder($trimmed)) {
            return null;
        }

        $decoded = json_decode($storedAuto, true);
        if (is_array($decoded) && !empty($decoded['number'])) {
            $number = trim((string) $decoded['number']);
            $color = trim((string) ($decoded['color'] ?? ''));
            $brand = trim((string) ($decoded['brand'] ?? ''));
            $model = trim((string) ($decoded['model'] ?? ''));
            $phone = isset($decoded['phoneNumber']) ? (string) $decoded['phoneNumber'] : null;

            return [
                'order_car_info' => self::buildDisplayString($number, $color, trim("$brand $model")),
                'driver_phone' => $phone,
            ];
        }

        if (preg_match('/, цвет\s+/u', $trimmed)) {
            return [
                'order_car_info' => $trimmed,
                'driver_phone' => self::extractPhoneFromCommaString($trimmed),
            ];
        }

        $parts = array_map('trim', explode(',', $storedAuto));
        if ($parts === [] || $parts[0] === '') {
            return null;
        }

        $number = $parts[0];
        $color = $parts[1] ?? '';
        $brandModel = $parts[2] ?? '';
        $phone = $parts[3] ?? null;

        return [
            'order_car_info' => self::buildDisplayString($number, $color, $brandModel),
            'driver_phone' => $phone !== null && $phone !== '' ? $phone : null,
        ];
    }

    public static function actionFromCloseReason($closeReason): string
    {
        switch ((string) $closeReason) {
            case '0':
            case '8':
            case '9':
                return OrderStatusMessageResolver::ACTION_COMPLETED;
            case '101':
                return OrderStatusMessageResolver::ACTION_CAR_FOUND;
            case '102':
                return OrderStatusMessageResolver::ACTION_AT_ADDRESS;
            case '103':
                return OrderStatusMessageResolver::ACTION_IN_ROUTE;
            case '104':
                return OrderStatusMessageResolver::ACTION_COMPLETED;
            default:
                return OrderStatusMessageResolver::ACTION_SEARCH;
        }
    }

    public static function isCachedStageCloseReason($closeReason): bool
    {
        return in_array((string) $closeReason, ['0', '8', '9', '100', '101', '102', '103', '104'], true);
    }

    private static function buildDisplayString(string $number, string $color, string $brandModel): string
    {
        return trim("$number, цвет $color  $brandModel.");
    }

    private static function isMalformedPlaceholder(string $value): bool
    {
        return preg_match('/^,\s*цвет\s*\.?$/u', $value) === 1;
    }

    private static function extractPhoneFromCommaString(string $value): ?string
    {
        $parts = array_map('trim', explode(',', $value));
        if (count($parts) < 4) {
            return null;
        }

        $phone = $parts[count($parts) - 1];

        return $phone !== '' ? $phone : null;
    }
}
