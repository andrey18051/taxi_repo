<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use PHPUnit\Framework\TestCase;

/**
 * Gate для finalize: вилка «жива», пока hasActiveDispatchLeg; иначе можно чистить Firestore.
 */
class DispatchCancelFinalizeGateTest extends TestCase
{
    public function test_fork_with_active_leg_blocks_finalize_semantics(): void
    {
        $card = ['close_reason' => 1, 'execution_status' => 'Canceled'];
        $nal = ['close_reason' => -1, 'execution_status' => 'SearchesForCar'];

        $this->assertTrue(OrderStatusController::hasActiveDispatchLeg($card, $nal));
    }

    public function test_fork_both_legs_inactive_allows_finalize_semantics(): void
    {
        $card = ['close_reason' => 1, 'execution_status' => 'Canceled'];
        $nal = ['close_reason' => 1, 'execution_status' => 'Canceled'];

        $this->assertFalse(OrderStatusController::hasActiveDispatchLeg($card, $nal));
    }

    public function test_empty_legs_are_not_live(): void
    {
        $this->assertFalse(OrderStatusController::hasActiveDispatchLeg(null, null));
    }

    public function test_stale_searching_snapshots_block_until_both_settled(): void
    {
        $staleCard = ['close_reason' => -1, 'execution_status' => 'SearchesForCar'];
        $staleNal = ['close_reason' => -1, 'execution_status' => 'SearchesForCar'];
        $this->assertTrue(OrderStatusController::hasActiveDispatchLeg($staleCard, $staleNal));

        $settledCard = ['close_reason' => 1, 'execution_status' => 'Canceled'];
        $settledNal = ['close_reason' => 1, 'execution_status' => 'Canceled'];
        $this->assertTrue(OrderStatusController::areForkLegSnapshotsCancelSettled($settledCard, $settledNal));
        $this->assertFalse(OrderStatusController::hasActiveDispatchLeg($settledCard, $settledNal));
    }
}
