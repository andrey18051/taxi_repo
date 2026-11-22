<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\Orderweb;
use App\Services\OrderStatusMessageResolver;
use Tests\TestCase;

class PersistedCancelCloseReasonTest extends TestCase
{
    public function test_prefers_driver_refusal_over_client_cancel(): void
    {
        $reason = OrderStatusController::resolvePersistedCancelCloseReason(
            ['close_reason' => 1],
            ['close_reason' => 2]
        );

        $this->assertSame('2', $reason);
    }

    public function test_keeps_client_cancel_when_both_legs_client(): void
    {
        $reason = OrderStatusController::resolvePersistedCancelCloseReason(
            ['close_reason' => 1],
            ['close_reason' => 1]
        );

        $this->assertSame('1', $reason);
    }

    public function test_falls_back_when_legs_have_no_cancel_code(): void
    {
        $reason = OrderStatusController::resolvePersistedCancelCloseReason(
            ['close_reason' => -1],
            ['close_reason' => -1],
            '1'
        );

        $this->assertSame('1', $reason);
    }

    public function test_apply_canceled_does_not_overwrite_driver_refusal_with_client(): void
    {
        $orderweb = new Orderweb();
        $orderweb->closeReason = '2';
        $orderweb->cancel_timestamp = null;

        OrderStatusController::applyCanceledOrderweb($orderweb, '1');

        $this->assertSame('2', (string) $orderweb->closeReason);
        $this->assertNotNull($orderweb->cancel_timestamp);
    }

    public function test_apply_canceled_persists_driver_refusal(): void
    {
        $orderweb = new Orderweb();
        $orderweb->closeReason = '-1';
        $orderweb->cancel_timestamp = null;

        OrderStatusController::applyCanceledOrderweb($orderweb, '2');

        $this->assertSame('2', (string) $orderweb->closeReason);
    }

    public function test_resolver_both_canceled_keeps_driver_close_reason(): void
    {
        $resolver = new OrderStatusMessageResolver();
        $result = $resolver->resolve(
            'Canceled',
            'Canceled',
            ['execution_status' => 'Canceled', 'close_reason' => 2],
            ['execution_status' => 'Canceled', 'close_reason' => 1]
        );

        $this->assertSame('Заказ снят', $result['action']);
        $this->assertSame(2, $result['close_reason']);
        $this->assertSame('2', $result['orderweb_close_reason']);
    }
}
