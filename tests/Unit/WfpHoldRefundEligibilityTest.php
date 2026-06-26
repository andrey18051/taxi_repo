<?php

namespace Tests\Unit;

use App\Services\DispatchOrderCancelService;
use App\Services\WfpHoldRefundEligibility;
use PHPUnit\Framework\TestCase;

class WfpHoldRefundEligibilityTest extends TestCase
{
    private WfpHoldRefundEligibility $eligibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eligibility = new WfpHoldRefundEligibility(new DispatchOrderCancelService());
    }

    public function test_refund_allowed_when_dispatch_close_reason_is_one(): void
    {
        $this->assertTrue($this->eligibility->allSnapshotsSettledForRefund([
            ['close_reason' => 1],
        ]));
    }

    public function test_refund_allowed_when_order_is_archived(): void
    {
        $this->assertTrue($this->eligibility->allSnapshotsSettledForRefund([
            ['close_reason' => -1, 'order_is_archive' => true],
        ]));
    }

    public function test_refund_blocked_when_dispatch_still_pending(): void
    {
        $this->assertFalse($this->eligibility->allSnapshotsSettledForRefund([
            ['close_reason' => -1, 'execution_status' => 'SearchesForCar'],
        ]));
        $this->assertFalse($this->eligibility->allSnapshotsSettledForRefund([
            ['close_reason' => 0],
        ]));
    }

    public function test_refund_blocked_when_fork_nal_leg_still_active(): void
    {
        $this->assertFalse($this->eligibility->allSnapshotsSettledForRefund([
            ['close_reason' => 1],
            ['close_reason' => -1],
        ]));
    }

    public function test_refund_blocked_when_no_snapshots(): void
    {
        $this->assertFalse($this->eligibility->allSnapshotsSettledForRefund([]));
    }

    public function test_gp_rebind_allowed_when_no_invoice_metadata(): void
    {
        $order = new \App\Models\Orderweb();
        $order->dispatching_order_uid = 'current-uid';
        $order->created_at = now();

        $this->assertTrue($this->eligibility->mayRebindGooglePayHold($order, 'V_NEW', null));
        $this->assertTrue($this->eligibility->mayVoidSupersededGooglePayHold($order, 'V_OLD'));
    }
}
