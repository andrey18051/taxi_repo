<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\Orderweb;
use Tests\TestCase;

class IsDispatchOrderCanceledTest extends TestCase
{
    public function test_canceled_with_close_reason_minus_one_is_not_real_cancel(): void
    {
        $order = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];

        $this->assertFalse(OrderStatusController::isDispatchOrderCanceled($order));
    }

    public function test_canceled_with_real_close_reason_is_cancel(): void
    {
        $order = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];

        $this->assertTrue(OrderStatusController::isDispatchOrderCanceled($order));
    }

    public function test_fork_card_canceled_does_not_trigger_early_cancel_when_nal_missing(): void
    {
        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];

        $this->assertTrue(OrderStatusController::hasActiveDispatchLeg($cardOrder, null));
    }

    public function test_both_legs_truly_canceled_means_no_active_leg(): void
    {
        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];
        $nalOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];

        $this->assertFalse(OrderStatusController::hasActiveDispatchLeg($cardOrder, $nalOrder));
    }

    public function test_cost_calculation_allows_fork_recreate(): void
    {
        $this->assertTrue(OrderStatusController::isLegClosedForForkRecreate(
            ['execution_status' => 'CostCalculation', 'close_reason' => 102],
            'CostCalculation'
        ));
    }

    public function test_searches_for_car_blocks_fork_recreate(): void
    {
        $this->assertFalse(OrderStatusController::isLegClosedForForkRecreate(
            ['execution_status' => 'SearchesForCar', 'close_reason' => -1],
            'SearchesForCar'
        ));
    }

    public function test_fake_canceled_blocks_fork_recreate(): void
    {
        $order = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];

        $this->assertFalse(OrderStatusController::isLegClosedForForkRecreate($order, 'Canceled'));
    }

    public function test_real_canceled_allows_fork_recreate(): void
    {
        $order = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];

        $this->assertTrue(OrderStatusController::isLegClosedForForkRecreate($order, 'Canceled'));
    }

    public function test_should_cascade_hold_dispatch_cancel_after_grace_period(): void
    {
        $orderweb = new Orderweb();
        $orderweb->created_at = now()->subSeconds(120);

        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
            'order_car_info' => null,
        ];
        $nalOrder = [
            'execution_status' => 'SearchesForCar',
            'close_reason' => -1,
            'order_car_info' => null,
        ];

        $this->assertTrue(OrderStatusController::shouldCascadeForkHoldDispatchCancel(
            $cardOrder,
            $nalOrder,
            $orderweb
        ));
    }

    public function test_should_not_cascade_hold_dispatch_cancel_within_grace_period(): void
    {
        $orderweb = new Orderweb();
        $orderweb->created_at = now()->subSeconds(30);

        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];
        $nalOrder = [
            'execution_status' => 'SearchesForCar',
            'close_reason' => -1,
        ];

        $this->assertFalse(OrderStatusController::shouldCascadeForkHoldDispatchCancel(
            $cardOrder,
            $nalOrder,
            $orderweb
        ));
    }

    public function test_should_cascade_immediately_when_card_has_real_close_reason(): void
    {
        $orderweb = new Orderweb();
        $orderweb->created_at = now()->subSeconds(10);

        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];
        $nalOrder = [
            'execution_status' => 'SearchesForCar',
            'close_reason' => -1,
        ];

        $this->assertTrue(OrderStatusController::shouldCascadeForkHoldDispatchCancel(
            $cardOrder,
            $nalOrder,
            $orderweb
        ));
    }
}
