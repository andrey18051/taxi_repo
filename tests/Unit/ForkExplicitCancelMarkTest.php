<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use Tests\Support\ForkDispatchRecorder;
use Tests\TestCase;

class ForkExplicitCancelMarkTest extends TestCase
{
    public function test_mark_explicit_fork_cancel_sets_flag_once(): void
    {
        $history = ForkDispatchRecorder::makeUidHistoryStub();
        $this->assertFalse(OrderStatusController::isExplicitForkCancelRequested($history));

        $this->assertTrue(OrderStatusController::markExplicitForkCancelRequested($history));
        $this->assertTrue(OrderStatusController::isExplicitForkCancelRequested($history));

        $this->assertFalse(OrderStatusController::markExplicitForkCancelRequested($history));
    }

    public function test_mark_explicit_fork_cancel_noop_for_null(): void
    {
        $this->assertFalse(OrderStatusController::markExplicitForkCancelRequested(null));
    }
}
