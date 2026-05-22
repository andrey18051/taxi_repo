<?php

namespace App\Helpers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class TimeHelper
{
    public const KYIV_TIMEZONE = 'Europe/Kiev';

    /** Slug для City-Info API (как normalizeCityName в PAS_4). */
    private const CITY_SLUG_MAP = [
        'Kyiv City' => 'kiev',
        'city_kiev' => 'kiev',
        'kiev' => 'kiev',
        'Dnipropetrovsk Oblast' => 'dnipro',
        'Odessa' => 'odesa',
        'OdessaTest' => 'odesa',
        'Zaporizhzhia' => 'zaporizhia',
        'Cherkasy Oblast' => 'cherkasy',
        'Lviv' => 'lviv',
        'Ivano_frankivsk' => 'ivano-frankivsk',
        'Vinnytsia' => 'vinnytsia',
        'Poltava' => 'poltava',
        'Sumy' => 'sumy',
        'Kharkiv' => 'kharkov',
        'Chernihiv' => 'chernihiv',
        'Rivne' => 'rivne',
        'Ternopil' => 'ternopil',
        'Khmelnytskyi' => 'khmelnytskyi',
        'Zakarpattya' => 'zakarpattya',
        'Zhytomyr' => 'zhytomyr',
        'Kropyvnytskyi' => 'kropyvnytskyi',
        'Mykolaiv' => 'mykolaiv',
        'Chernivtsi' => 'chernivtsi',
        'Lutsk' => 'lutsk',
    ];

    /**
     * Проверяет, осталось ли до смены часа 15 секунд.
     */
    public static function isFifteenSecondsToNextHour(): int
    {
        $currentTime = Carbon::now();
        $secondsToNextHour = 3600 - ($currentTime->minute * 60 + $currentTime->second);

        return $secondsToNextHour;
    }

    /**
     * Комендантский час (как homeWelcome / AutoCancelJob для Киева).
     */
    public static function isCurfewActive(?Carbon $at = null): bool
    {
        $at = ($at ?? now())->timezone(self::KYIV_TIMEZONE);
        $currentMinutes = $at->hour * 60 + $at->minute;
        $startMinutes = self::timeToMinutes(config('app.start_time', '00:00'));
        $endMinutes = self::timeToMinutes(config('app.end_time', '05:00'));

        if ($startMinutes <= $endMinutes) {
            return $currentMinutes >= $startMinutes && $currentMinutes <= $endMinutes;
        }

        return $currentMinutes >= $startMinutes || $currentMinutes <= $endMinutes;
    }

    public static function getCurfewStatus(?Carbon $at = null): array
    {
        $at = ($at ?? now())->timezone(self::KYIV_TIMEZONE);

        return [
            'timezone' => self::KYIV_TIMEZONE,
            'current_time' => $at->format('H:i:s'),
            'current_datetime' => $at->toIso8601String(),
            'start_time' => config('app.start_time', '00:00'),
            'end_time' => config('app.end_time', '05:00'),
            'curfew_active' => self::isCurfewActive($at),
        ];
    }

    /**
     * Данные City-Info (как CityInfoHelper в PAS_4): reb_active, air_alarm.
     */
    public static function fetchCityInfo(string $city = 'kiev', string $lang = 'uk'): array
    {
        $slug = self::normalizeCitySlug($city);
        $cityInfoConfig = config('services.city_info', []);
        $baseUrl = rtrim((string) ($cityInfoConfig['base_url'] ?? 'https://city-info.utax.top/api/data/'), '/') . '/';
        $token = $cityInfoConfig['token'] ?? env('UTAX_API_KEY');

        if (empty($token)) {
            return [
                'ok' => false,
                'error' => 'UTAX_API_KEY не задан в .env (Bearer для City-Info, как utaxKey в PAS_4)',
                'city_slug' => $slug,
            ];
        }

        $client = new Client([
            'timeout' => (float) ($cityInfoConfig['timeout'] ?? 5),
            'connect_timeout' => 3,
        ]);

        $url = $baseUrl . $slug . '?lang=' . $lang . '&_=' . time();

        try {
            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept-Language' => $lang,
                    'Cache-Control' => 'no-cache, no-store',
                    'Pragma' => 'no-cache',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (!is_array($body)) {
                return [
                    'ok' => false,
                    'error' => 'Некорректный JSON от City-Info',
                    'city_slug' => $slug,
                    'http_status' => $response->getStatusCode(),
                ];
            }

            return [
                'ok' => true,
                'city_slug' => $slug,
                'http_status' => $response->getStatusCode(),
                'reb_active' => (bool) ($body['reb_active'] ?? false),
                'air_alarm' => (bool) ($body['air_alarm'] ?? false),
                'weather' => $body['weather'] ?? null,
                'temperature' => $body['temperature'] ?? null,
                'time_stamp' => $body['time_stamp'] ?? null,
                'raw' => $body,
            ];
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            Log::warning('TimeHelper: City-Info request failed', [
                'city' => $slug,
                'status' => $status,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'city_slug' => $slug,
                'http_status' => $status,
            ];
        }
    }

    public static function getKyivState(string $lang = 'uk', bool $fetchCityInfo = true): array
    {
        $curfew = self::getCurfewStatus();
        $cityInfo = $fetchCityInfo
            ? self::fetchCityInfo('kiev', $lang)
            : ['ok' => false, 'skipped' => true];

        $rebActive = ($cityInfo['ok'] ?? false) && ($cityInfo['reb_active'] ?? false);
        $airAlarm = ($cityInfo['ok'] ?? false) && ($cityInfo['air_alarm'] ?? false);

        return [
            'city' => 'Kyiv City',
            'city_slug' => 'kiev',
            'curfew' => $curfew,
            'city_info' => $cityInfo,
            'reb_active' => $rebActive,
            'air_alarm' => $airAlarm,
            'alerts' => array_values(array_filter([
                $airAlarm ? 'air_alarm' : null,
                $rebActive ? 'reb_active' : null,
                $curfew['curfew_active'] ? 'curfew' : null,
            ])),
            'pas4_source' => 'City-Info.utax.top reb_active',
        ];
    }

    public static function normalizeCitySlug(string $city): string
    {
        if (isset(self::CITY_SLUG_MAP[$city])) {
            return self::CITY_SLUG_MAP[$city];
        }

        $lower = strtolower(trim($city));

        return self::CITY_SLUG_MAP[$lower] ?? ($lower ?: 'kiev');
    }

    private static function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }
}
