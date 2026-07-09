<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use Tests\TestCase;

class ShouldPersistDispatchCloseReasonTest extends TestCase
{
    public function test_does_not_persist_client_cancel_on_active_order_after_add_cost_mapping(): void
    {
        $orderweb = (object) [
            'closeReason' => '-1',
            'dispatching_order_uid' => 'c7fa1ab7782f46cfb0127c50d5833345',
        ];

        $snapshot = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];

        $this->assertFalse(OrderStatusController::shouldPersistDispatchCloseReasonToOrderweb(
            $orderweb,
            $snapshot,
            '16902baacc404e0680f029edddc92bf5',
            true
        ));
    }

    public function test_persists_real_cancel_when_no_recreation_mapping(): void
    {
        $orderweb = (object) [
            'closeReason' => '-1',
            'dispatching_order_uid' => 'c7fa1ab7782f46cfb0127c50d5833345',
        ];

        $snapshot = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];

        $this->assertTrue(OrderStatusController::shouldPersistDispatchCloseReasonToOrderweb(
            $orderweb,
            $snapshot,
            'c7fa1ab7782f46cfb0127c50d5833345',
            false
        ));
    }
}
