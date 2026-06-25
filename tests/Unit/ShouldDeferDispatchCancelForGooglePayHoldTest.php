<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\Orderweb;
use PHPUnit\Framework\TestCase;

class ShouldDeferDispatchCancelForGooglePayHoldTest extends TestCase
{
    public function test_defers_when_dispatch_close_reason_is_minus_one(): void
    {
        $order = $this->makeOrderweb(['pay_system' => 'wfp_payment']);

        $this->assertTrue(OrderStatusController::shouldDeferDispatchCancelForGooglePayHold(
            $order,
            ['close_reason' => -1, 'execution_status' => 'Canceled']
        ));
    }

    public function test_defers_for_google_pay_with_waiting_auth_complete(): void
    {
        $order = $this->makeOrderweb([
            'pay_system' => 'google_pay_payment',
            'wfp_status_pay' => 'WaitingAuthComplete',
        ]);

        $this->assertTrue(OrderStatusController::shouldDeferDispatchCancelForGooglePayHold(
            $order,
            ['close_reason' => 1, 'execution_status' => 'Canceled']
        ));
    }

    public function test_does_not_defer_wfp_without_hold(): void
    {
        $order = $this->makeOrderweb([
            'pay_system' => 'wfp_payment',
            'wfp_status_pay' => null,
        ]);

        $this->assertFalse(OrderStatusController::shouldDeferDispatchCancelForGooglePayHold(
            $order,
            ['close_reason' => 1, 'execution_status' => 'Canceled']
        ));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeOrderweb(array $attributes): Orderweb
    {
        $orderweb = (new \ReflectionClass(Orderweb::class))->newInstanceWithoutConstructor();
        foreach ($attributes as $key => $value) {
            $orderweb->{$key} = $value;
        }

        return $orderweb;
    }
}
