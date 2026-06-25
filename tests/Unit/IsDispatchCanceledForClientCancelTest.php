<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use PHPUnit\Framework\TestCase;

class IsDispatchCanceledForClientCancelTest extends TestCase
{
    public function test_real_cancel_close_reason_counts(): void
    {
        $this->assertTrue(OrderStatusController::isDispatchCanceledForClientCancel(
            ['close_reason' => 1, 'execution_status' => 'Canceled'],
            'any-uid'
        ));
    }

    public function test_canceled_execution_without_campaign_is_not_client_cancel(): void
    {
        $this->assertFalse(OrderStatusController::isDispatchCanceledForClientCancel(
            ['close_reason' => -1, 'execution_status' => 'Canceled'],
            'any-uid'
        ));
    }

    public function test_active_search_is_not_cancel(): void
    {
        $this->assertFalse(OrderStatusController::isDispatchCanceledForClientCancel(
            ['close_reason' => -1, 'execution_status' => 'SearchesForCar'],
            'any-uid'
        ));
    }
}
