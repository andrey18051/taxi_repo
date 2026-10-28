<?php

namespace Tests\Unit;

use App\City\PaymentFlow;
use App\Services\OrderCancelOrigin;
use App\Services\Pas2SimpleDispatchRecreate;
use App\Services\Pas2SimpleDispatchRecreateGate;
use App\Models\Orderweb;
use Tests\TestCase;

class Pas2SimpleDispatchRecreateGateTest extends TestCase
{
    private function context(array $overrides = []): array
    {
        return array_merge([
            'is_pas2' => true,
            'payment_flow' => PaymentFlow::SIMPLE,
            'server' => 'http://188.190.245.102:7303',
            'is_fork' => false,
            'close_reason' => 2,
            'order_is_archive' => true,
            'execution_status' => 'Canceled',
            'previous_close_reason' => '-1',
            'recover_stored' => false,
            'has_car' => false,
            'cancel_requested' => false,
            'payment_confirmed' => true,
            'recreate_count' => 0,
            'add_cost_in_progress' => false,
        ], $overrides);
    }

    public function test_recreates_pas2_simple_search_when_dispatch_closes_without_client(): void
    {
        $this->assertTrue(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context()));
    }

    public function test_recreates_cash_and_card_the_same_way_when_payment_is_confirmed(): void
    {
        $this->assertTrue(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'close_reason' => 5,
        ])));
    }

    public function test_does_not_recreate_other_apps(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'is_pas2' => false,
        ])));
    }

    public function test_does_not_recreate_fork_orders(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'payment_flow' => PaymentFlow::FORK,
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'is_fork' => true,
        ])));
    }

    public function test_archive_flag_is_required_for_foreign_close(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'order_is_archive' => false,
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'order_is_archive' => null,
        ])));
        $this->assertTrue(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'close_reason' => 5,
            'execution_status' => 'CostCalculation',
            'order_is_archive' => true,
        ])));
        $this->assertTrue(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'close_reason' => 4,
            'execution_status' => 'Canceled',
            'order_is_archive' => true,
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'close_reason' => 8,
            'execution_status' => 'Canceled',
            'order_is_archive' => true,
        ])));
    }

    public function test_holding_search_response_does_not_look_canceled(): void
    {
        $order = new Orderweb();
        $order->dispatching_order_uid = 'new-uid';
        $order->web_cost = '60';

        $payload = Pas2SimpleDispatchRecreate::holdingSearchResponse($order);

        $this->assertSame('new-uid', $payload['dispatching_order_uid']);
        $this->assertSame(-1, $payload['close_reason']);
        $this->assertSame('SearchesForCar', $payload['execution_status']);
        $this->assertFalse($payload['order_is_archive']);
        $this->assertSame('60', $payload['order_cost']);
        $this->assertTrue(Pas2SimpleDispatchRecreate::isArchivedFlag(true));
        $this->assertFalse(Pas2SimpleDispatchRecreate::isArchivedFlag(false));
    }

    public function test_stored_dispatch_url_is_not_doubled(): void
    {
        $stored = 'http://188.190.245.102:7303/api/weborders';

        $this->assertSame(
            $stored,
            Pas2SimpleDispatchRecreate::webordersEndpoint($stored, 'http://188.190.245.102:7303')
        );
        $this->assertSame(
            $stored,
            Pas2SimpleDispatchRecreate::webordersEndpoint('http://188.190.245.102:7303/', '')
        );
        $this->assertSame(
            $stored,
            Pas2SimpleDispatchRecreate::webordersEndpoint('', 'http://188.190.245.102:7303')
        );
        $this->assertSame('', Pas2SimpleDispatchRecreate::webordersEndpoint('', ''));
    }

    public function test_client_close_reasons_stay_canceled(): void
    {
        foreach ([1, 6, 7, 9] as $reason) {
            $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
                'close_reason' => $reason,
            ])));
        }
    }

    public function test_our_cancel_marker_blocks_recreate(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'cancel_requested' => true,
        ])));
    }

    public function test_unpaid_card_is_not_recreated(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'payment_confirmed' => false,
        ])));
    }

    public function test_does_not_recreate_after_car_is_assigned(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'has_car' => true,
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'previous_close_reason' => '101',
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'execution_status' => 'Running',
        ])));
    }

    public function test_cap_stops_the_series(): void
    {
        $this->assertTrue(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'recreate_count' => 1,
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'recreate_count' => Pas2SimpleDispatchRecreateGate::MAX_RECREATES,
        ])));
    }

    public function test_add_cost_recreation_is_left_alone(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'add_cost_in_progress' => true,
        ])));
    }

    public function test_stored_foreign_reason_can_be_recovered_once(): void
    {
        $this->assertTrue(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'close_reason' => 4,
            'previous_close_reason' => '4',
            'recover_stored' => true,
            'execution_status' => 'WaitingCarSearch',
        ])));
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'close_reason' => 4,
            'previous_close_reason' => '4',
            'recover_stored' => false,
        ])));
    }

    public function test_my_server_api_is_not_recreated_on_dispatch(): void
    {
        $this->assertFalse(Pas2SimpleDispatchRecreateGate::shouldRecreate($this->context([
            'server' => 'my_server_api',
        ])));
    }

    public function test_live_poll_is_only_pas2_simple_cash_in_search(): void
    {
        $pas2Cash = new Orderweb();
        $pas2Cash->comment = 'taxi_easy_ua_pas2';
        $pas2Cash->payment_flow_mode = PaymentFlow::SIMPLE;
        $pas2Cash->pay_system = 'nal_payment';
        $pas2Cash->server = 'http://188.190.245.102:7303';
        $pas2Cash->closeReason = '-1';
        $this->assertTrue(Pas2SimpleDispatchRecreate::shouldLivePoll($pas2Cash));

        $pas4 = new Orderweb();
        $pas4->comment = 'taxi_easy_ua_pas4';
        $pas4->payment_flow_mode = PaymentFlow::SIMPLE;
        $pas4->pay_system = 'nal_payment';
        $pas4->server = 'http://188.190.245.102:7303';
        $pas4->closeReason = '-1';
        $this->assertFalse(Pas2SimpleDispatchRecreate::shouldLivePoll($pas4));

        $card = new Orderweb();
        $card->comment = 'taxi_easy_ua_pas2';
        $card->payment_flow_mode = PaymentFlow::SIMPLE;
        $card->pay_system = 'wfp_payment';
        $card->server = 'http://188.190.245.102:7303';
        $card->closeReason = '-1';
        $this->assertFalse(Pas2SimpleDispatchRecreate::shouldLivePoll($card));
    }

    public function test_cancel_origin_marks_block_a_later_recreate_check(): void
    {
        OrderCancelOrigin::mark('uid-pay', OrderCancelOrigin::PAYMENT_TIMEOUT);
        OrderCancelOrigin::mark('uid-timer', OrderCancelOrigin::AUTO_CANCEL);
        OrderCancelOrigin::mark('uid-client', OrderCancelOrigin::CLIENT);

        $this->assertTrue(OrderCancelOrigin::blocksRecreate('uid-pay'));
        $this->assertTrue(OrderCancelOrigin::blocksRecreate('uid-timer'));
        $this->assertTrue(OrderCancelOrigin::blocksRecreate('uid-client'));
        $this->assertFalse(OrderCancelOrigin::blocksRecreate('uid-dispatch'));
    }
}
