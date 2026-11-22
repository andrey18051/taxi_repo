<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\Uid_history;
use Tests\TestCase;

class ResolveForkExecutionStatusForClientListTest extends TestCase
{
    public function test_null_history_returns_null(): void
    {
        $this->assertNull(OrderStatusController::resolveForkExecutionStatusForClientList(null));
    }

    public function test_history_cancel_flag_means_canceled(): void
    {
        $uidHistory = new Uid_history();
        $uidHistory->cancel = 1;
        $uidHistory->double_status = json_encode([
            'execution_status' => 'SearchesForCar',
            'close_reason' => -1,
        ]);
        $uidHistory->bonus_status = json_encode([
            'execution_status' => 'Running',
            'close_reason' => -1,
        ]);

        $this->assertSame(
            'Canceled',
            OrderStatusController::resolveForkExecutionStatusForClientList($uidHistory)
        );
    }

    /** Mantis #127: одна нога Canceled, вторая Running — заказ в работе, не снимать. */
    public function test_one_leg_canceled_other_running_keeps_running(): void
    {
        $uidHistory = new Uid_history();
        $uidHistory->cancel = 0;
        $uidHistory->double_status = json_encode([
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ]);
        $uidHistory->bonus_status = json_encode([
            'execution_status' => 'Running',
            'close_reason' => -1,
        ]);

        $this->assertSame(
            'Running',
            OrderStatusController::resolveForkExecutionStatusForClientList($uidHistory)
        );
    }

    public function test_one_leg_canceled_other_searches_keeps_searches(): void
    {
        $uidHistory = new Uid_history();
        $uidHistory->cancel = 0;
        $uidHistory->bonus_status = json_encode([
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ]);
        $uidHistory->double_status = json_encode([
            'execution_status' => 'SearchesForCar',
            'close_reason' => -1,
        ]);

        $this->assertSame(
            'SearchesForCar',
            OrderStatusController::resolveForkExecutionStatusForClientList($uidHistory)
        );
    }

    public function test_both_legs_canceled_means_canceled(): void
    {
        $uidHistory = new Uid_history();
        $uidHistory->cancel = 0;
        $uidHistory->double_status = json_encode([
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ]);
        $uidHistory->bonus_status = json_encode([
            'execution_status' => 'Canceled',
            'close_reason' => 1,
        ]);

        $this->assertSame(
            'Canceled',
            OrderStatusController::resolveForkExecutionStatusForClientList($uidHistory)
        );
    }

    public function test_running_preferred_over_searches(): void
    {
        $uidHistory = new Uid_history();
        $uidHistory->cancel = 0;
        $uidHistory->double_status = json_encode([
            'execution_status' => 'SearchesForCar',
            'close_reason' => -1,
        ]);
        $uidHistory->bonus_status = json_encode([
            'execution_status' => 'Running',
            'close_reason' => -1,
        ]);

        $this->assertSame(
            'Running',
            OrderStatusController::resolveForkExecutionStatusForClientList($uidHistory)
        );
    }
}
