<?php

namespace Tests\Unit;

use App\City\GooglePayOrderReferenceResolver;
use PHPUnit\Framework\TestCase;

class GooglePayOrderReferenceResolverTest extends TestCase
{
    public function test_keeps_app_reference_when_invoice_already_bound(): void
    {
        $result = GooglePayOrderReferenceResolver::resolveForOrderBind(
            'V_WFHE',
            7,
            [
                [
                    'orderReference' => 'V_WFHE',
                    'amount' => '7',
                    'transactionStatus' => 'WaitingAuthComplete',
                    'dispatching_order_uid' => 'uid-1',
                ],
            ]
        );

        $this->assertSame('V_WFHE', $result);
    }

    public function test_rebinds_to_orphan_hold_with_same_amount(): void
    {
        $result = GooglePayOrderReferenceResolver::resolveForOrderBind(
            'V_WFHE',
            7,
            [
                [
                    'orderReference' => 'V_EMDH',
                    'amount' => '7',
                    'transactionStatus' => 'WaitingAuthComplete',
                    'dispatching_order_uid' => null,
                ],
            ]
        );

        $this->assertSame('V_EMDH', $result);
    }

    public function test_keeps_app_reference_when_no_orphan_hold(): void
    {
        $result = GooglePayOrderReferenceResolver::resolveForOrderBind(
            'V_WFHE',
            7,
            []
        );

        $this->assertSame('V_WFHE', $result);
    }
}
