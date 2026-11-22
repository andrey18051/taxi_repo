<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use Tests\TestCase;

class ShouldRestoreLegacyForkLegOnDriverRefusalTest extends TestCase
{
    /** @return array<string, mixed> */
    private function snap(string $status, $closeReason): array
    {
        return [
            'execution_status' => $status,
            'close_reason' => $closeReason,
        ];
    }

    public function test_restores_cashless_when_card_refused_and_nal_already_client_canceled(): void
    {
        $this->assertTrue(OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
            'bonus',
            $this->snap('Canceled', 1),
            $this->snap('Canceled', 2),
            $this->snap('Canceled', 1),
            $this->snap('Running', -1)
        ));
    }

    public function test_restores_nal_when_double_refused_and_card_already_client_canceled(): void
    {
        $this->assertTrue(OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
            'double',
            $this->snap('Canceled', 3),
            $this->snap('Canceled', 1),
            $this->snap('CarFound', -1),
            $this->snap('Canceled', 1)
        ));
    }

    public function test_all_driver_refusal_codes_for_cashless_restore(): void
    {
        foreach ([2, 3, 4, 5] as $code) {
            $this->assertTrue(
                OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
                    'bonus',
                    $this->snap('Canceled', 1),
                    $this->snap('Canceled', $code),
                    $this->snap('Canceled', 1),
                    $this->snap('SearchesForCar', -1)
                ),
                'close_reason ' . $code
            );
        }
    }

    public function test_false_when_refused_leg_is_client_cancel(): void
    {
        $this->assertFalse(OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
            'bonus',
            $this->snap('Canceled', 1),
            $this->snap('Canceled', 1),
            $this->snap('Canceled', 1),
            $this->snap('Running', -1)
        ));
    }

    public function test_false_when_previous_refused_leg_was_not_live(): void
    {
        $this->assertFalse(OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
            'bonus',
            $this->snap('Canceled', 1),
            $this->snap('Canceled', 2),
            $this->snap('Canceled', 1),
            $this->snap('Canceled', -1)
        ));
    }

    public function test_false_when_sibling_current_close_is_not_one(): void
    {
        $this->assertFalse(OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
            'double',
            $this->snap('Canceled', 2),
            $this->snap('Canceled', 9),
            $this->snap('WaitingCarSearch', -1),
            $this->snap('Canceled', 1)
        ));
    }

    public function test_false_for_unknown_leg(): void
    {
        $this->assertFalse(OrderStatusController::shouldRestoreLegacyForkLegOnDriverRefusal(
            'hold',
            $this->snap('Canceled', 1),
            $this->snap('Canceled', 2),
            $this->snap('Canceled', 1),
            $this->snap('Running', -1)
        ));
    }
}
