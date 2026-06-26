<?php

namespace Tests\Unit;

use App\Services\OrderPaymentNotificationHelper;
use PHPUnit\Framework\TestCase;

class OrderPaymentNotificationHelperTest extends TestCase
{
    public function test_pay_type_labels(): void
    {
        $this->assertSame('Google Pay', OrderPaymentNotificationHelper::payTypeLabel('google_pay_payment'));
        $this->assertSame('Карта', OrderPaymentNotificationHelper::payTypeLabel('wfp_payment'));
    }

    public function test_new_order_pay_type_line(): void
    {
        $this->assertSame(
            'Оплата наличными.',
            OrderPaymentNotificationHelper::formatNewOrderPayTypeLine('wfp_payment', 0)
        );
        $this->assertSame(
            'Оплата Google Pay.',
            OrderPaymentNotificationHelper::formatNewOrderPayTypeLine('google_pay_payment', 1)
        );
        $this->assertSame(
            'Оплата картой.',
            OrderPaymentNotificationHelper::formatNewOrderPayTypeLine('wfp_payment', 1)
        );
    }

    public function test_wfp_product_name_contains_uid_and_type(): void
    {
        $name = OrderPaymentNotificationHelper::buildWfpProductName(
            'f5c07818a1ff49c4b645ee8bf9291360',
            'google_pay_payment',
            7
        );

        $this->assertStringContainsString('uid=f5c07818', $name);
        $this->assertStringContainsString('GP', $name);
        $this->assertStringContainsString('7UAH', $name);
    }

    public function test_payment_bind_message(): void
    {
        $order = (object) [
            'dispatching_order_uid' => 'abc123',
            'pay_system' => 'wfp_payment',
            'web_cost' => 7,
            'client_cost' => 7,
            'finish_cost' => 0,
        ];

        $message = OrderPaymentNotificationHelper::buildPaymentBindTelegramMessage(
            $order,
            'V_20260626112400123_AB12'
        );

        $this->assertStringContainsString('abc123', $message);
        $this->assertStringContainsString('V_20260626112400123_AB12', $message);
        $this->assertStringContainsString('Карта', $message);
    }

    public function test_append_wfp_reference_to_cancel_message(): void
    {
        $base = 'Клиент отменил заказ.';
        $this->assertSame(
            $base . ' Оплата WFP: V_TEST.',
            OrderPaymentNotificationHelper::appendWfpReferenceToCancelMessage($base, 'V_TEST')
        );
        $this->assertSame($base, OrderPaymentNotificationHelper::appendWfpReferenceToCancelMessage($base, null));
    }
}
