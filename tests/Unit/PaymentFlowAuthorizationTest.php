<?php

namespace Tests\Unit;

use App\City\PaymentFlow;
use App\City\PaymentFlowAuthorization;
use PHPUnit\Framework\TestCase;

class PaymentFlowAuthorizationTest extends TestCase
{
    public function test_fork_mode_keeps_bonus_and_double(): void
    {
        $input = [
            'authorization' => 'base-auth',
            'payment_type' => 1,
            'authorizationBonus' => 'bonus-auth',
            'authorizationDouble' => 'double-auth',
        ];

        $result = PaymentFlowAuthorization::apply($input, PaymentFlow::FORK);

        $this->assertSame('bonus-auth', $result['authorizationBonus']);
        $this->assertSame('double-auth', $result['authorizationDouble']);
        $this->assertSame('base-auth', $result['authorization']);
    }

    public function test_simple_mode_uses_single_card_authorization(): void
    {
        $input = [
            'authorization' => 'base-auth',
            'payment_type' => 1,
            'authorizationBonus' => 'bonus-auth',
            'authorizationDouble' => 'double-auth',
        ];

        $result = PaymentFlowAuthorization::apply($input, PaymentFlow::SIMPLE);

        $this->assertSame('bonus-auth', $result['authorization']);
        $this->assertNull($result['authorizationBonus']);
        $this->assertNull($result['authorizationDouble']);
        $this->assertSame(1, $result['payment_type']);
    }

    public function test_off_mode_forces_cash(): void
    {
        $input = [
            'authorization' => 'base-auth',
            'payment_type' => 1,
            'authorizationBonus' => 'bonus-auth',
            'authorizationDouble' => 'double-auth',
        ];

        $result = PaymentFlowAuthorization::apply($input, PaymentFlow::OFF);

        $this->assertSame(0, $result['payment_type']);
        $this->assertNull($result['authorizationBonus']);
        $this->assertNull($result['authorizationDouble']);
    }
}
