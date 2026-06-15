<?php

namespace Tests\Unit;

use App\City\PaymentFlow;
use App\City\SimpleCashlessPaymentWatch;
use App\Models\Orderweb;
use Tests\TestCase;

class SimpleCashlessPaymentWatchTest extends TestCase
{
    public function test_should_watch_my_server_api_linked_card(): void
    {
        $order = new Orderweb();
        $order->server = 'my_server_api';
        $order->payment_type = 1;
        $order->pay_system = 'wfp_payment';

        $this->assertTrue(SimpleCashlessPaymentWatch::shouldWatch($order));
    }

    public function test_should_not_watch_dispatch_simple_linked_card(): void
    {
        $order = new Orderweb();
        $order->server = 'http://142.132.213.111:8072';
        $order->payment_flow_mode = PaymentFlow::SIMPLE;
        $order->payment_type = 1;
        $order->pay_system = 'wfp_payment';

        $this->assertFalse(SimpleCashlessPaymentWatch::shouldWatch($order));
    }

    public function test_should_not_watch_google_pay(): void
    {
        $order = new Orderweb();
        $order->server = 'my_server_api';
        $order->payment_flow_mode = PaymentFlow::SIMPLE;
        $order->payment_type = 1;
        $order->pay_system = 'google_pay_payment';

        $this->assertFalse(SimpleCashlessPaymentWatch::shouldWatch($order));
    }

    public function test_resolve_city_display_name_from_internal_city_code(): void
    {
        $order = new Orderweb();
        $order->city = 'city_kiev';
        $order->server = 'http://example.test';

        $this->assertSame('Kyiv City', SimpleCashlessPaymentWatch::resolveCityDisplayName($order));
    }
}
