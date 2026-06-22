<?php

namespace Tests\Unit;

use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\WfpController;
use Tests\TestCase;

class TokenPaymentFlowTest extends TestCase
{
    public function test_should_skip_invoice_update_when_wfp_order_not_found_yet(): void
    {
        $this->assertTrue(WfpController::shouldSkipCheckStatusInvoiceUpdate([
            'reasonCode' => 1127,
            'transactionStatus' => 'Declined',
            'reason' => 'Order Not Found',
        ]));
    }

    public function test_should_persist_invoice_update_when_wfp_returns_real_decline(): void
    {
        $this->assertFalse(WfpController::shouldSkipCheckStatusInvoiceUpdate([
            'reasonCode' => 1101,
            'transactionStatus' => 'Declined',
            'reason' => 'Declined To Card Issuer',
        ]));
    }

    public function test_should_persist_invoice_update_when_reason_code_missing(): void
    {
        $this->assertFalse(WfpController::shouldSkipCheckStatusInvoiceUpdate([
            'transactionStatus' => 'Approved',
        ]));
    }

    public function test_save_order_and_charge_runs_in_correct_order(): void
    {
        $callOrder = [];

        /** @var UniversalAndroidFunctionController&\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder(UniversalAndroidFunctionController::class)
            ->onlyMethods(['saveOrder', 'orderIdMemoryToken', 'chargeLinkedCardAfterOrderReference'])
            ->getMock();

        $controller->expects($this->once())
            ->method('saveOrder')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'saveOrder';

                return 6549;
            });

        $controller->expects($this->once())
            ->method('orderIdMemoryToken')
            ->with('V_TEST_FPK9', 6549, 'wfp_payment')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'orderIdMemoryToken';
            });

        $controller->expects($this->once())
            ->method('chargeLinkedCardAfterOrderReference')
            ->with(
                'wfp_payment',
                'PAS4',
                'OdessaTest',
                'V_TEST_FPK9',
                6,
                'taxialfa@gmail.com',
                '+380933464747',
                '430'
            )
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'charge';
            });

        $params = [
            'pay_system' => 'wfp_payment',
            'email' => 'taxialfa@gmail.com',
            'user_phone' => '+380933464747',
        ];

        $orderId = $controller->saveOrderAndChargeTokenBeforeAppNotify(
            $params,
            'PAS4-ident',
            'V_TEST_FPK9',
            'PAS4',
            'OdessaTest',
            6,
            '430'
        );

        $this->assertSame(6549, $orderId);
        $this->assertSame(['saveOrder', 'orderIdMemoryToken', 'charge'], $callOrder);
    }

    public function test_save_order_skips_token_charge_when_wfp_invoice_is_star(): void
    {
        /** @var UniversalAndroidFunctionController&\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder(UniversalAndroidFunctionController::class)
            ->onlyMethods(['saveOrder', 'orderIdMemoryToken', 'chargeLinkedCardAfterOrderReference'])
            ->getMock();

        $controller->expects($this->once())->method('saveOrder')->willReturn(100);
        $controller->expects($this->never())->method('orderIdMemoryToken');
        $controller->expects($this->never())->method('chargeLinkedCardAfterOrderReference');

        $orderId = $controller->saveOrderAndChargeTokenBeforeAppNotify(
            ['pay_system' => 'wfp_payment', 'email' => 'a@test.com', 'user_phone' => '+380'],
            'PAS4-ident',
            '*',
            'PAS4',
            'OdessaTest',
            6
        );

        $this->assertSame(100, $orderId);
    }

    public function test_save_order_skips_token_charge_when_wfp_invoice_is_null(): void
    {
        /** @var UniversalAndroidFunctionController&\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder(UniversalAndroidFunctionController::class)
            ->onlyMethods(['saveOrder', 'orderIdMemoryToken', 'chargeLinkedCardAfterOrderReference'])
            ->getMock();

        $controller->expects($this->once())->method('saveOrder')->willReturn(101);
        $controller->expects($this->never())->method('orderIdMemoryToken');
        $controller->expects($this->never())->method('chargeLinkedCardAfterOrderReference');

        $orderId = $controller->saveOrderAndChargeTokenBeforeAppNotify(
            ['pay_system' => 'nal_payment', 'email' => 'a@test.com', 'user_phone' => '+380'],
            'PAS4-ident',
            null,
            'PAS4',
            'OdessaTest',
            6
        );

        $this->assertSame(101, $orderId);
    }

    public function test_save_order_binds_google_pay_invoice_and_delegates_charge_wrapper(): void
    {
        /** @var UniversalAndroidFunctionController&\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder(UniversalAndroidFunctionController::class)
            ->onlyMethods(['saveOrder', 'orderIdMemoryToken', 'chargeLinkedCardAfterOrderReference'])
            ->getMock();

        $controller->expects($this->once())->method('saveOrder')->willReturn(102);
        $controller->expects($this->once())
            ->method('orderIdMemoryToken')
            ->with('V_GPAY_1', 102, 'google_pay_payment');
        $controller->expects($this->once())
            ->method('chargeLinkedCardAfterOrderReference')
            ->with(
                'google_pay_payment',
                'PAS4',
                'OdessaTest',
                'V_GPAY_1',
                7,
                'a@test.com',
                '+380',
                null
            );

        $controller->saveOrderAndChargeTokenBeforeAppNotify(
            ['pay_system' => 'google_pay_payment', 'email' => 'a@test.com', 'user_phone' => '+380'],
            'PAS4-ident',
            'V_GPAY_1',
            'PAS4',
            'OdessaTest',
            7
        );
    }
}
