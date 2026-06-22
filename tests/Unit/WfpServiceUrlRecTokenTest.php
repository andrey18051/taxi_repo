<?php

namespace Tests\Unit;

use App\Http\Controllers\WfpController;
use Tests\TestCase;

class WfpServiceUrlRecTokenTest extends TestCase
{
    public function test_should_not_persist_rec_token_for_google_pay_callback(): void
    {
        $this->assertFalse(WfpController::shouldPersistRecTokenFromServiceUrl([
            'paymentSystem' => 'googlePay',
            'recToken' => 'token-from-gpay',
            'email' => 'taxialfa@gmail.com',
        ]));
    }

    public function test_should_persist_rec_token_for_manual_card_callback(): void
    {
        $this->assertTrue(WfpController::shouldPersistRecTokenFromServiceUrl([
            'paymentSystem' => 'card',
            'recToken' => 'token-from-card',
            'email' => 'taxialfa@gmail.com',
        ]));
    }

    public function test_should_not_persist_when_rec_token_missing(): void
    {
        $this->assertFalse(WfpController::shouldPersistRecTokenFromServiceUrl([
            'paymentSystem' => 'card',
            'email' => 'taxialfa@gmail.com',
        ]));
    }
}
