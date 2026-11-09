<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use Tests\TestCase;

class OrderStatusUidStampTest extends TestCase
{
    public function test_fork_search_status_keeps_card_uid_instead_of_cash_leg(): void
    {
        $cashLeg = [
            'dispatching_order_uid' => '62bfdb9c56fc46d2a781fd130fd60e65',
            'execution_status' => 'SearchesForCar',
            'order_cost' => '7',
            'close_reason' => -1,
        ];

        $stamped = OrderStatusController::stampClientOrderUid(
            $cashLeg,
            'Поиск авто',
            '1f7c6f637b7e42839dd8c8984ff11683'
        );

        $this->assertSame('1f7c6f637b7e42839dd8c8984ff11683', $stamped['dispatching_order_uid']);
        $this->assertSame('1f7c6f637b7e42839dd8c8984ff11683', $stamped['uid']);
        $this->assertSame('Поиск авто', $stamped['action']);
        $this->assertSame('7', $stamped['order_cost']);
        $this->assertSame('SearchesForCar', $stamped['execution_status']);
    }

    public function test_empty_uid_does_not_wipe_dispatching_order_uid(): void
    {
        $stamped = OrderStatusController::stampClientOrderUid(
            ['dispatching_order_uid' => 'keep-me', 'order_cost' => '5'],
            'Поиск авто',
            ''
        );

        $this->assertSame('keep-me', $stamped['dispatching_order_uid']);
        $this->assertSame('', $stamped['uid']);
    }
}
