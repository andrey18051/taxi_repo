<?php

namespace Tests\Unit;

use App\Models\Orderweb;
use App\Services\DispatchOrderCancelService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * При взятии заказа водителем closeReason=101 — отмена на диспетчере не должна пропускаться.
 */
class DispatchOrderCancelDriverTakeTest extends TestCase
{
    public function test_driver_assigned_status_does_not_count_as_dispatch_finished(): void
    {
        foreach (['101', '102', '103', '104'] as $closeReason) {
            $orderweb = $this->makeOrderweb($closeReason);

            $this->assertFalse(
                $this->invokeIsOrderwebAlreadyFinished($orderweb),
                "closeReason {$closeReason} must not skip dispatch cancel"
            );
        }
    }

    public function test_dispatch_cancelled_close_reason_counts_as_finished(): void
    {
        $this->assertTrue($this->invokeIsOrderwebAlreadyFinished($this->makeOrderweb('1')));
    }

    public function test_active_order_is_not_finished(): void
    {
        $this->assertFalse($this->invokeIsOrderwebAlreadyFinished($this->makeOrderweb('-1')));
    }

    private function makeOrderweb(string $closeReason): Orderweb
    {
        $orderweb = (new \ReflectionClass(Orderweb::class))->newInstanceWithoutConstructor();
        $orderweb->closeReason = $closeReason;

        return $orderweb;
    }

    private function invokeIsOrderwebAlreadyFinished(Orderweb $orderweb): bool
    {
        $method = new ReflectionMethod(DispatchOrderCancelService::class, 'isOrderwebAlreadyFinished');
        $method->setAccessible(true);

        return $method->invoke(new DispatchOrderCancelService(), $orderweb);
    }
}
