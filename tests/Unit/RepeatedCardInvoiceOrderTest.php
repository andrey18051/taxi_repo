<?php

namespace Tests\Unit;

use App\Support\RepeatedCardInvoiceOrder;
use PHPUnit\Framework\TestCase;

class RepeatedCardInvoiceOrderTest extends TestCase
{
    public function testSameInvoiceRefusesASecondDispatchOrder(): void
    {
        $this->assertTrue(RepeatedCardInvoiceOrder::shouldRefuseNewDispatch(
            'V_20261117203042568_37PO',
            true
        ));
    }

    public function testFirstUseOfAnInvoiceIsAllowed(): void
    {
        $this->assertFalse(RepeatedCardInvoiceOrder::shouldRefuseNewDispatch(
            'V_20261117203042568_37PO',
            false
        ));
    }

    public function testEmptyInvoiceIsNotARepeat(): void
    {
        $this->assertFalse(RepeatedCardInvoiceOrder::shouldRefuseNewDispatch('*', true));
        $this->assertFalse(RepeatedCardInvoiceOrder::shouldRefuseNewDispatch('', true));
        $this->assertFalse(RepeatedCardInvoiceOrder::shouldRefuseNewDispatch('no', true));
    }
}
