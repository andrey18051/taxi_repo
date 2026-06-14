<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
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

    public function test_fork_card_canceled_is_not_active_dispatch_leg(): void
    {
        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];

        $this->assertFalse(OrderStatusController::hasActiveDispatchLeg($cardOrder, null));
    }

    public function test_running_nal_with_fork_canceled_card_is_active(): void
    {
        $cardOrder = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];
        $nalOrder = [
            'execution_status' => 'Running',
            'close_reason' => -1,
        ];

        $this->assertTrue(OrderStatusController::hasActiveDispatchLeg($cardOrder, $nalOrder));
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
}
