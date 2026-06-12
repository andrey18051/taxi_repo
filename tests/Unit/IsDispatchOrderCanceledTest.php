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
}
