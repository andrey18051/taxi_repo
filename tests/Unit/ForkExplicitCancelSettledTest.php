<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use PHPUnit\Framework\TestCase;

class ForkExplicitCancelSettledTest extends TestCase
{
    public function test_both_legs_settled_when_archived_or_close_reason_one(): void
    {
        $card = ['close_reason' => 1, 'execution_status' => 'Canceled'];
        $nal = ['close_reason' => -1, 'order_is_archive' => true, 'execution_status' => 'Canceled'];

        $this->assertTrue(OrderStatusController::areForkLegSnapshotsCancelSettled($card, $nal));
    }

    public function test_not_settled_when_card_still_searching(): void
    {
        $card = ['close_reason' => -1, 'execution_status' => 'SearchesForCar'];
        $nal = ['close_reason' => 1, 'execution_status' => 'Canceled'];

        $this->assertFalse(OrderStatusController::areForkLegSnapshotsCancelSettled($card, $nal));
    }

    public function test_not_settled_when_no_legs(): void
    {
        $this->assertFalse(OrderStatusController::areForkLegSnapshotsCancelSettled(null, null));
    }
}
