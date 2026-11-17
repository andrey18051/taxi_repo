<?php

namespace Tests\Unit;

use App\Support\ForkUnpaidCancelClock;
use PHPUnit\Framework\TestCase;

class ForkUnpaidCancelClockTest extends TestCase
{
    public function test_recreated_pair_does_not_move_the_deadline(): void
    {
        $firstOrder = strtotime('2026-11-17 18:19:56');
        $recreatedPair = strtotime('2026-11-17 18:21:47');
        $oneMinuteLater = $firstOrder + 60;

        $clock = ForkUnpaidCancelClock::clockTimestamp($firstOrder, $recreatedPair);

        $this->assertSame($firstOrder, $clock);
        $this->assertFalse(ForkUnpaidCancelClock::stillInsideWindow($clock, $oneMinuteLater));
    }

    public function test_fresh_order_stays_inside_the_window(): void
    {
        $createdAt = strtotime('2026-11-17 18:21:27');

        $this->assertTrue(ForkUnpaidCancelClock::stillInsideWindow($createdAt, $createdAt + 20));
        $this->assertFalse(ForkUnpaidCancelClock::stillInsideWindow($createdAt, $createdAt + 60));
    }

    public function test_missing_latest_timestamp_keeps_the_original_order(): void
    {
        $firstOrder = '2026-11-17 18:21:27';

        $this->assertSame(
            strtotime($firstOrder),
            ForkUnpaidCancelClock::clockTimestamp($firstOrder, null)
        );
    }
}
