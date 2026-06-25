<?php

namespace Tests\Unit;

use App\Support\DispatchOrderCancelSchedule;
use PHPUnit\Framework\TestCase;

class DispatchOrderCancelScheduleTest extends TestCase
{
    public function test_offset_seconds_matches_unified_cancel_spec(): void
    {
        $this->assertSame(0, DispatchOrderCancelSchedule::offsetSecondsForAttempt(1));
        $this->assertSame(5, DispatchOrderCancelSchedule::offsetSecondsForAttempt(2));
        $this->assertSame(30, DispatchOrderCancelSchedule::offsetSecondsForAttempt(3));
        $this->assertSame(60, DispatchOrderCancelSchedule::offsetSecondsForAttempt(4));
        $this->assertSame(120, DispatchOrderCancelSchedule::offsetSecondsForAttempt(5));
        $this->assertSame(180, DispatchOrderCancelSchedule::offsetSecondsForAttempt(6));
    }

    public function test_problem_telegram_after_ten_minutes(): void
    {
        $this->assertSame(600, DispatchOrderCancelSchedule::PROBLEM_TELEGRAM_AFTER_SECONDS);
    }

    public function test_delay_until_next_attempt(): void
    {
        $startedAt = 1_000_000;
        $this->assertSame(5, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 2, $startedAt));
        $this->assertSame(0, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 2, $startedAt + 10));
        $this->assertSame(25, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 3, $startedAt + 5));
        $this->assertSame(30, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 4, $startedAt + 30));
    }
}
