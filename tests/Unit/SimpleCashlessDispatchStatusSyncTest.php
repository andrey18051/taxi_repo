<?php

namespace Tests\Unit;

use App\City\PaymentFlow;
use App\City\SimpleCashlessDispatchStatusSync;
use App\Models\Orderweb;
use App\Services\OrderStatusMessageResolver;
use Tests\TestCase;

class SimpleCashlessDispatchStatusSyncTest extends TestCase
{
    public function test_should_live_sync_for_dispatch_simple_cashless(): void
    {
        $order = new Orderweb();
        $order->payment_flow_mode = PaymentFlow::SIMPLE;
        $order->payment_type = 1;
        $order->pay_system = 'wfp_payment';
        $order->server = 'http://188.190.245.102:7303';
        $order->closeReason = '-1';

        $this->assertTrue(SimpleCashlessDispatchStatusSync::shouldLiveSync($order));
    }

    public function test_should_live_sync_for_google_pay(): void
    {
        $order = new Orderweb();
        $order->payment_flow_mode = PaymentFlow::SIMPLE;
        $order->payment_type = 1;
        $order->pay_system = 'google_pay_payment';
        $order->server = 'http://188.190.245.102:7303';
        $order->closeReason = '101';

        $this->assertTrue(SimpleCashlessDispatchStatusSync::shouldLiveSync($order));
    }

    public function test_should_live_sync_when_payment_type_zero_but_wfp_pay_system(): void
    {
        $order = new Orderweb();
        $order->payment_flow_mode = PaymentFlow::SIMPLE;
        $order->payment_type = 0;
        $order->pay_system = 'wfp_payment';
        $order->server = 'http://188.190.245.102:7303';
        $order->closeReason = '-1';

        $this->assertTrue(SimpleCashlessDispatchStatusSync::shouldLiveSync($order));
    }

    public function test_should_not_live_sync_for_fork_mode(): void
    {
        $order = new Orderweb();
        $order->payment_flow_mode = PaymentFlow::FORK;
        $order->payment_type = 1;
        $order->pay_system = 'wfp_payment';
        $order->server = 'http://188.190.245.102:7303';
        $order->closeReason = '-1';

        $this->assertFalse(SimpleCashlessDispatchStatusSync::shouldLiveSync($order));
    }

    public function test_should_not_live_sync_when_trip_completed(): void
    {
        $order = new Orderweb();
        $order->payment_flow_mode = PaymentFlow::SIMPLE;
        $order->payment_type = 1;
        $order->pay_system = 'wfp_payment';
        $order->server = 'http://188.190.245.102:7303';
        $order->closeReason = '104';

        $this->assertFalse(SimpleCashlessDispatchStatusSync::shouldLiveSync($order));
    }

    public function test_single_leg_resolver_matches_car_found_snapshot(): void
    {
        $snapshot = [
            'execution_status' => 'CarFound',
            'close_reason' => -1,
            'order_car_info' => [
                'number' => 'AA1234BB',
                'color' => 'білий',
                'brand' => 'Toyota',
                'model' => 'Camry',
                'phoneNumber' => '+380931112233',
            ],
        ];

        $resolved = (new OrderStatusMessageResolver())->resolve(
            'CarFound',
            'CarFound',
            $snapshot,
            $snapshot
        );

        $this->assertSame(OrderStatusMessageResolver::ACTION_CAR_FOUND, $resolved['action']);
        $this->assertSame('101', $resolved['orderweb_close_reason']);
    }
}
