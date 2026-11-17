<?php

namespace Tests\Unit;

use App\Support\AddCostClientPayload;
use PHPUnit\Framework\TestCase;

class AddCostClientPayloadTest extends TestCase
{
    public function test_recreated_payload_keeps_charged_total_when_dispatch_price_is_lower(): void
    {
        $order = (object) [
            'routefrom' => 'Щорса 129',
            'startLat' => '46.43',
            'startLan' => '30.68',
            'routeto' => 'по місту',
            'to_lat' => '46.43',
            'to_lng' => '30.68',
            'pay_system' => 'wfp_payment',
        ];

        $payload = AddCostClientPayload::recreated('79541d71', '10', 12, $order);

        $this->assertSame(12, $payload['client_cost']);
        $this->assertSame('10', $payload['web_cost']);
        $this->assertSame('79541d71', $payload['uid']);
        $this->assertSame('WaitingAuthComplete', $payload['transactionStatus']);
        $this->assertSame('wfp_payment', $payload['pay_system']);
    }

    public function test_pending_cancel_is_not_a_hard_decline(): void
    {
        $payload = AddCostClientPayload::pendingCancel();

        $this->assertSame('dispatch_cancel_pending', $payload['response']);
        $this->assertSame(AddCostClientPayload::STATUS_PROCESSING, $payload['transactionStatus']);
        $this->assertArrayNotHasKey('client_cost', $payload);
    }
}
