<?php

namespace Tests\Unit;

use App\Support\DispatchOrderCancelSchedule;
use PHPUnit\Framework\TestCase;

class DispatchOrderCancelScheduleTest extends TestCase
{
    public function test_offset_seconds_for_attempts(): void
    {
        $this->assertSame(0, DispatchOrderCancelSchedule::offsetSecondsForAttempt(1));
        $this->assertSame(5, DispatchOrderCancelSchedule::offsetSecondsForAttempt(2));
        $this->assertSame(30, DispatchOrderCancelSchedule::offsetSecondsForAttempt(3));
        $this->assertSame(60, DispatchOrderCancelSchedule::offsetSecondsForAttempt(4));
        $this->assertSame(120, DispatchOrderCancelSchedule::offsetSecondsForAttempt(5));
        $this->assertSame(180, DispatchOrderCancelSchedule::offsetSecondsForAttempt(6));
    }

    public function test_delay_until_next_attempt_from_campaign_start(): void
    {
        $startedAt = 1_000_000;

        $this->assertSame(0, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 1, $startedAt));
        $this->assertSame(5, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 2, $startedAt));
        $this->assertSame(25, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 3, $startedAt + 5));
        $this->assertSame(0, DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 4, $startedAt + 60));
    }

    public function test_problem_telegram_threshold_is_ten_minutes(): void
    {
        $this->assertSame(600, DispatchOrderCancelSchedule::PROBLEM_TELEGRAM_AFTER_SECONDS);
    }
}
