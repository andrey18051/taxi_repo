<?php

namespace Tests\Unit;

use App\City\CityPaymentFlowResolver;
use App\City\PaymentFlow;
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
}
