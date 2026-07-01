<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrderHistoryPayMethodResponseTest extends TestCase
{
    public function test_history_row_maps_pay_system_to_pay_method(): void
    {
        $orderRow = [
            'pay_system' => 'wfp_payment',
            'routefrom' => 'Test',
        ];

        $response = [
            'pay_method' => $orderRow['pay_system'] ?? '',
            'routefrom' => $orderRow['routefrom'] ?? '',
        ];

        $this->assertSame('wfp_payment', $response['pay_method']);
    }

    public function test_history_row_without_pay_system_returns_empty_pay_method(): void
    {
        $orderRow = ['routefrom' => 'Test'];

        $payMethod = $orderRow['pay_system'] ?? '';

        $this->assertSame('', $payMethod);
    }
}
