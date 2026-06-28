<?php

namespace Tests\Unit;

use App\Http\Controllers\WfpController;
use Tests\TestCase;

class WfpCreateInvoicePaymentSystemsTest extends TestCase
{
    public function test_create_invoice_excludes_google_pay_for_card_binding(): void
    {
        $paymentSystems = WfpController::paymentSystemsForCreateInvoice();

        $this->assertStringNotContainsString('googlePay', $paymentSystems);
        $this->assertStringContainsString('card', $paymentSystems);
        $this->assertSame('card;privat24', $paymentSystems);
    }
}
