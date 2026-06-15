<?php

namespace Tests\Unit;

use App\City\CityPaymentFlowResolver;
use App\City\PaymentFlow;
use App\Http\Controllers\CityController;
use Tests\TestCase;

class CityPaymentFlowResolverTest extends TestCase
{
    public function test_application_from_identification_id_maps_pas_apps(): void
    {
        $this->assertSame(
            'PAS2',
            CityPaymentFlowResolver::applicationFromIdentificationId(config('app.X-WO-API-APP-ID-PAS2'))
        );
        $this->assertSame(
            'PAS4',
            CityPaymentFlowResolver::applicationFromIdentificationId(config('app.X-WO-API-APP-ID-PAS4'))
        );
    }

    public function test_application_from_identification_id_returns_null_for_unknown(): void
    {
        $this->assertNull(CityPaymentFlowResolver::applicationFromIdentificationId('unknown-app-id'));
        $this->assertNull(CityPaymentFlowResolver::applicationFromIdentificationId(null));
    }

    public function test_resolve_without_city_or_server_returns_off(): void
    {
        $this->assertSame(PaymentFlow::OFF, CityPaymentFlowResolver::resolve(null, null, null));
        $this->assertSame(PaymentFlow::OFF, CityPaymentFlowResolver::resolve('', 'PAS2', null));
    }

    public function test_resolve_prefers_city_app_over_internal_code(): void
    {
        try {
            $cityArr = (new CityController())->maxPayValueApp('OdessaTest', 'PAS4');
        } catch (\Throwable $e) {
            $this->markTestSkipped('OdessaTest missing in test DB: ' . $e->getMessage());
        }

        if (PaymentFlow::normalize($cityArr['payment_flow'] ?? 0) !== PaymentFlow::SIMPLE) {
            $this->markTestSkipped('OdessaTest payment_flow is not SIMPLE in test DB');
        }

        $flow = CityPaymentFlowResolver::resolve(
            'city_odessa',
            'PAS4',
            'my_server_api',
            'OdessaTest'
        );

        $this->assertSame(PaymentFlow::SIMPLE, $flow);
    }

    public function test_resolve_skips_my_server_api_server_lookup(): void
    {
        $this->assertSame(
            PaymentFlow::OFF,
            CityPaymentFlowResolver::resolve('unknown_city_xyz', 'PAS4', 'my_server_api', null)
        );
    }
}
