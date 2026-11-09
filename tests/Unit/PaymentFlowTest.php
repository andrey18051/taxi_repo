<?php

namespace Tests\Unit;

use App\City\PaymentFlow;
use PHPUnit\Framework\TestCase;

class PaymentFlowTest extends TestCase
{
    public function test_normalize_accepts_valid_values(): void
    {
        $this->assertSame(PaymentFlow::OFF, PaymentFlow::normalize(0));
        $this->assertSame(PaymentFlow::FORK, PaymentFlow::normalize(1));
        $this->assertSame(PaymentFlow::SIMPLE, PaymentFlow::normalize(2));
        $this->assertSame(PaymentFlow::FORK_LEGACY, PaymentFlow::normalize(3));
        $this->assertSame(PaymentFlow::FORK, PaymentFlow::normalize('1'));
        $this->assertSame(PaymentFlow::FORK_LEGACY, PaymentFlow::normalize('3'));
    }

    public function test_normalize_rejects_invalid_values(): void
    {
        $this->assertSame(PaymentFlow::OFF, PaymentFlow::normalize(4));
        $this->assertSame(PaymentFlow::OFF, PaymentFlow::normalize(-1));
        $this->assertSame(PaymentFlow::OFF, PaymentFlow::normalize('fork'));
    }

    public function test_legacy_fork_poll_only_for_mode_three(): void
    {
        $this->assertTrue(PaymentFlow::usesLegacyForkStatusPoll(3));
        $this->assertTrue(PaymentFlow::usesLegacyForkStatusPoll('3'));
        $this->assertFalse(PaymentFlow::usesLegacyForkStatusPoll(PaymentFlow::FORK));
        $this->assertFalse(PaymentFlow::usesLegacyForkStatusPoll(PaymentFlow::SIMPLE));
        $this->assertFalse(PaymentFlow::usesLegacyForkStatusPoll(PaymentFlow::OFF));
        $this->assertFalse(PaymentFlow::usesLegacyForkStatusPoll(9));
    }
}
