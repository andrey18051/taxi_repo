<?php

namespace App\City;

use App\Http\Controllers\CityController;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\City_PAS5;
use Throwable;

final class CityPaymentFlowResolver
{
    /** @var array<string, list<string>> */
    private const INTERNAL_CITY_CANDIDATES = [
        'city_odessa' => ['OdessaTest', 'Odessa'],
        'city_kiev' => ['Kyiv City'],
        'city_cherkassy' => ['Cherkasy Oblast'],
        'city_zaporizhzhia' => ['Zaporizhzhia'],
        'city_dnipro' => ['Dnipropetrovsk Oblast', 'DniproTest'],
    ];

    public static function applicationFromIdentificationId(?string $identificationId): ?string
    {
        if ($identificationId === null || $identificationId === '') {
            return null;
        }

        $map = [
            config('app.X-WO-API-APP-ID-PAS1') => 'PAS1',
            config('app.X-WO-API-APP-ID-PAS2') => 'PAS2',
            config('app.X-WO-API-APP-ID-PAS3') => 'PAS3',
            config('app.X-WO-API-APP-ID-PAS4') => 'PAS4',
            config('app.X-WO-API-APP-ID-PAS5') => 'PAS5',
            config('app.X-WO-API-APP-ID-TEST') => 'PAS4',
        ];

        return $map[$identificationId] ?? null;
    }

    /**
     * Snapshot payment_flow for a new order from city map settings.
     *
     * @param string|null $cityName   Internal code (city_odessa) or display name
     * @param string|null $cityApp    Display name from the app (OdessaTest) — preferred
     */
    public static function resolve(
        ?string $cityName,
        ?string $application,
        ?string $server = null,
        ?string $cityApp = null
    ): int {
        if ($application === null || $application === '') {
            return PaymentFlow::OFF;
        }

        foreach (self::cityLookupCandidates($cityApp, $cityName) as $candidate) {
            $flow = self::resolveFromAppCity($candidate, $application);
            if ($flow !== null) {
                return $flow;
            }
        }

        if ($server !== null && $server !== '' && $server !== 'my_server_api') {
            $fromServer = self::resolveByServerAddress($server);
            if ($fromServer !== PaymentFlow::OFF) {
                return $fromServer;
            }
        }

        return PaymentFlow::OFF;
    }

    public static function resolveByServerAddress(string $server): int
    {
        $address = preg_replace('#^https?://#', '', $server);

        foreach ([City_PAS1::class, City_PAS2::class, City_PAS4::class, City_PAS5::class] as $modelClass) {
            $city = $modelClass::where('address', $address)->first();
            if ($city !== null) {
                return PaymentFlow::normalize($city->payment_flow ?? 0);
            }
        }

        return PaymentFlow::OFF;
    }

    /**
     * @return list<string>
     */
    private static function cityLookupCandidates(?string $cityApp, ?string $cityName): array
    {
        $candidates = [];

        if ($cityApp !== null && $cityApp !== '') {
            $candidates[] = $cityApp;
        }

        if ($cityName !== null && $cityName !== '' && !self::isInternalCityCode($cityName)) {
            $candidates[] = $cityName;
        }

        if ($cityName !== null && self::isInternalCityCode($cityName)) {
            foreach (self::INTERNAL_CITY_CANDIDATES[$cityName] ?? [] as $mapped) {
                $candidates[] = $mapped;
            }
        }

        return array_values(array_unique($candidates));
    }

    private static function isInternalCityCode(string $cityName): bool
    {
        return str_starts_with($cityName, 'city_');
    }

    private static function resolveFromAppCity(string $cityApp, string $application): ?int
    {
        try {
            $cityArr = (new CityController())->maxPayValueApp($cityApp, $application);

            return PaymentFlow::normalize($cityArr['payment_flow'] ?? 0);
        } catch (Throwable $e) {
            return null;
        }
    }
}
