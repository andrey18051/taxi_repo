<?php

namespace Tests\Unit;

use App\Helpers\OrderHelper;
use Tests\TestCase;

class OrderHelperForkAddCostTest extends TestCase
{
    public function test_card_and_cash_fork_use_different_add_cost_for_same_client_price(): void
    {
        $clientCost = 7;
        $baseAddCost = 0;
        $costCorrection = 0;

        $cardAddCost = OrderHelper::resolveAddCostBalanceFromQuote(
            $clientCost,
            65,
            $baseAddCost,
            $costCorrection
        );
        $forkAddCost = OrderHelper::resolveAddCostBalanceFromQuote(
            $clientCost,
            60,
            $baseAddCost,
            $costCorrection
        );

        $this->assertSame(-58, $cardAddCost);
        $this->assertSame(-53, $forkAddCost);
        $this->assertSame(7, 65 + $cardAddCost);
        $this->assertSame(7, 60 + $forkAddCost);
    }

    public function test_reusing_card_add_cost_on_cash_quote_produces_wrong_fork_price(): void
    {
        $clientCost = 7;
        $cardAddCost = -58;

        $this->assertSame(2, 60 + $cardAddCost);
        $this->assertNotSame($clientCost, 60 + $cardAddCost);
    }

    public function test_add_cost_increase_uses_different_fork_add_cost_for_twelve_uah(): void
    {
        $clientCost = 12;
        $baseAddCost = 0;
        $costCorrection = 0;

        $cardAddCost = OrderHelper::resolveAddCostBalanceFromQuote(
            $clientCost,
            72,
            $baseAddCost,
            $costCorrection
        );
        $forkAddCost = OrderHelper::resolveAddCostBalanceFromQuote(
            $clientCost,
            67,
            $baseAddCost,
            $costCorrection
        );

        $this->assertSame(-60, $cardAddCost);
        $this->assertSame(-55, $forkAddCost);
        $this->assertSame(12, 72 + $cardAddCost);
        $this->assertSame(12, 67 + $forkAddCost);
        $this->assertNotSame($cardAddCost, $forkAddCost);
    }
}
