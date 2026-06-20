<?php

namespace Tests\Unit;

use App\Models\WfpInvoice;
use Tests\TestCase;

class WfpInvoiceModelTest extends TestCase
{
    public function test_fillable_allows_order_reference_for_first_or_new(): void
    {
        $invoice = new WfpInvoice();
        $invoice->fill(['orderReference' => 'V_TEST_REF']);

        $this->assertSame('V_TEST_REF', $invoice->orderReference);
    }

    public function test_fillable_allows_google_pay_hold_fields(): void
    {
        $invoice = new WfpInvoice();
        $invoice->fill([
            'orderReference' => 'V_TEST_REF',
            'amount' => '7',
            'transactionStatus' => 'WaitingAuthComplete',
            'merchantAccount' => 'm_easy_order_taxi_site',
        ]);

        $this->assertSame('7', $invoice->amount);
        $this->assertSame('WaitingAuthComplete', $invoice->transactionStatus);
        $this->assertSame('m_easy_order_taxi_site', $invoice->merchantAccount);
    }
}
