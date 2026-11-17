<?php

namespace Tests\Unit;

use App\Support\PaymentTypeColumn;
use PHPUnit\Framework\TestCase;

class PaymentTypeColumnTest extends TestCase
{
    public function testCardPaySystemIsStoredAsStringOne(): void
    {
        $stored = PaymentTypeColumn::store('wfp_payment', 0);

        $this->assertSame('1', $stored);
    }

    public function testGooglePayIsStoredAsStringOne(): void
    {
        $this->assertSame('1', PaymentTypeColumn::store('google_pay_payment', 0));
    }

    public function testNumericOneIsStoredAsStringOne(): void
    {
        $this->assertSame('1', PaymentTypeColumn::store('nal_payment', 1));
    }

    public function testCashIsStoredAsStringZero(): void
    {
        $this->assertSame('0', PaymentTypeColumn::store('nal_payment', 0));
    }
}
