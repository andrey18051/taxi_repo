<?php

namespace Tests\Unit;

use App\Services\CardChargeSelector;
use App\Services\OrderPaymentNotificationHelper;
use PHPUnit\Framework\TestCase;

class CardChargeSelectorTest extends TestCase
{
    public function test_active_card_wins_over_newer_inactive(): void
    {
        $picked = CardChargeSelector::pick([
            ['id' => 391, 'active' => 0, 'rectoken' => 'test-merchant'],
            ['id' => 392, 'active' => 1, 'rectoken' => 'other-merchant'],
        ]);

        $this->assertSame(392, $picked['id']);
    }

    public function test_latest_card_used_when_none_marked_active(): void
    {
        $picked = CardChargeSelector::pick([
            ['id' => 391, 'active' => 0, 'rectoken' => 'test-merchant'],
        ]);

        $this->assertSame(391, $picked['id']);
        $this->assertSame('test-merchant', $picked['rectoken']);
    }

    public function test_empty_list_has_no_card(): void
    {
        $this->assertNull(CardChargeSelector::pick([]));
    }

    public function test_bind_message_only_after_hold(): void
    {
        $this->assertTrue(OrderPaymentNotificationHelper::isConfirmedHoldStatus('WaitingAuthComplete'));
        $this->assertTrue(OrderPaymentNotificationHelper::isConfirmedHoldStatus('Approved'));
        $this->assertFalse(OrderPaymentNotificationHelper::isConfirmedHoldStatus('Declined'));
        $this->assertFalse(OrderPaymentNotificationHelper::isConfirmedHoldStatus(null));
    }
}
