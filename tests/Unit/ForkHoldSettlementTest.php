<?php

namespace Tests\Unit;

use App\Services\ForkHoldSettlement;
use PHPUnit\Framework\TestCase;

class ForkHoldSettlementTest extends TestCase
{
    public function test_keeps_hold_while_any_fork_order_is_open(): void
    {
        $this->assertSame(ForkHoldSettlement::KEEP, ForkHoldSettlement::decide(['1', '1', '-1']));
        $this->assertSame(ForkHoldSettlement::KEEP, ForkHoldSettlement::decide(['0', '-1']));
        $this->assertSame(ForkHoldSettlement::KEEP, ForkHoldSettlement::decide(['0', '8', '1', '-1']));
    }

    public function test_keeps_hold_when_there_are_no_orders(): void
    {
        $this->assertSame(ForkHoldSettlement::KEEP, ForkHoldSettlement::decide([]));
    }

    public function test_settles_only_after_every_fork_order_is_closed_and_one_is_done(): void
    {
        $this->assertSame(ForkHoldSettlement::SETTLE, ForkHoldSettlement::decide(['1', '0', '1']));
        $this->assertSame(ForkHoldSettlement::SETTLE, ForkHoldSettlement::decide(['8', '1', '4']));
    }

    public function test_refunds_only_after_every_fork_order_is_closed_without_a_completed_trip(): void
    {
        $this->assertSame(ForkHoldSettlement::REFUND, ForkHoldSettlement::decide(['1', '1', '4']));
        $this->assertSame(ForkHoldSettlement::REFUND, ForkHoldSettlement::decide([1, 6, 9]));
    }
}
