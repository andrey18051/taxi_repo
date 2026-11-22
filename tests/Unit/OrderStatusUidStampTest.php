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

    /** Mantis #124: после legacy fork recreate клиенту нужен hold, не Vod-нога. */
    public function test_client_facing_uid_prefers_hold_over_recreated_bonus_leg(): void
    {
        $row = (object) [
            'uid_bonusOrderHold' => '88e4f94ffac34c54b250c6b0a971f924',
            'uid_bonusOrder' => '36257ff8cfe54284a5dd0f85052ee597',
            'uid_doubleOrder' => '89e1c59954cf492ebcadfaf4368c0b10',
        ];

        $this->assertSame(
            '88e4f94ffac34c54b250c6b0a971f924',
            OrderStatusController::clientFacingUidFromHistory($row, 'fallback')
        );
    }

    public function test_client_facing_uid_falls_back_to_bonus_when_hold_empty(): void
    {
        $row = (object) [
            'uid_bonusOrderHold' => '',
            'uid_bonusOrder' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ];

        $this->assertSame(
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            OrderStatusController::clientFacingUidFromHistory($row, 'fallback')
        );
    }
}
