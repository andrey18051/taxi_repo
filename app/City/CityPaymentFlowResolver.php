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
     */
    public static function resolve(?string $cityName, ?string $application, ?string $server = null): int
    {
        if ($cityName !== null && $cityName !== '' && $application !== null && $application !== '') {
            try {
                $cityArr = (new CityController())->maxPayValueApp($cityName, $application);

                return PaymentFlow::normalize($cityArr['payment_flow'] ?? 0);
            } catch (Throwable $e) {
                // fall through to server lookup
            }
        }

        if ($server !== null && $server !== '') {
            return self::resolveByServerAddress($server);
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
}
