<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use PHPUnit\Framework\TestCase;

class ShouldNotifyClientOrderCanceledTest extends TestCase
{
    public function test_dispatcher_cancel_blocks_fork_restore(): void
    {
        $snapshot = [
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ];

        $this->assertTrue(
            OrderStatusController::shouldSkipForkRestoreAfterDispatcherCancel($snapshot, 'Canceled')
        );
    }

    public function test_technical_fork_cancel_allows_restore(): void
    {
        $snapshot = [
            'execution_status' => 'Canceled',
            'close_reason' => 4,
        ];

        $this->assertFalse(
            OrderStatusController::shouldSkipForkRestoreAfterDispatcherCancel($snapshot, 'Canceled')
        );
    }

    public function test_negative_one_close_reason_allows_restore(): void
    {
        $snapshot = [
            'execution_status' => 'Canceled',
            'close_reason' => -1,
        ];

        $this->assertFalse(
            OrderStatusController::shouldSkipForkRestoreAfterDispatcherCancel($snapshot, 'Canceled')
        );
    }
}
