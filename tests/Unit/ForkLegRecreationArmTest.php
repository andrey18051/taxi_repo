<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use Tests\Support\ForkUidHistoryStub;
use Tests\TestCase;

class ForkLegRecreationArmTest extends TestCase
{
    public function test_arm_and_clear_bonus_leg(): void
    {
        $uidHistory = new ForkUidHistoryStub();

        $this->assertFalse(OrderStatusController::isForkLegRecreationArmed($uidHistory, 'bonus'));

        OrderStatusController::armForkLegRecreation($uidHistory, 'bonus');
        $this->assertTrue(OrderStatusController::isForkLegRecreationArmed($uidHistory, 'bonus'));

        OrderStatusController::clearForkLegRecreationArm($uidHistory, 'bonus');
        $this->assertFalse(OrderStatusController::isForkLegRecreationArmed($uidHistory, 'bonus'));
    }

    public function test_mark_dispatcher_cancel_clears_arm(): void
    {
        $uidHistory = new ForkUidHistoryStub();
        OrderStatusController::armForkLegRecreation($uidHistory, 'double');

        OrderStatusController::markForkLegDispatcherCanceled($uidHistory, 'double');

        $this->assertTrue(OrderStatusController::isForkLegDispatcherCanceled($uidHistory, 'double'));
        $this->assertFalse(OrderStatusController::isForkLegRecreationArmed($uidHistory, 'double'));
    }

    public function test_arm_unknown_leg_is_noop(): void
    {
        $uidHistory = new ForkUidHistoryStub();

        $this->assertFalse(OrderStatusController::armForkLegRecreation($uidHistory, 'unknown'));
        $this->assertFalse(OrderStatusController::isForkLegRecreationArmed($uidHistory, 'unknown'));
    }
}
