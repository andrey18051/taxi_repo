<?php

namespace Tests\Unit;

use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Services\OrderForkLegExecutor;
use App\Services\OrderLegActionMatrix;
use ReflectionMethod;
use Tests\TestCase;

class OrderForkLegExecutorLastStatusTest extends TestCase
{
    public function test_advance_last_leg_status_keeps_previous_on_transition(): void
    {
        $this->assertSame(
            'SearchesForCar',
            $this->invokeAdvanceLastLegStatus('SearchesForCar', 'Canceled')
        );
    }

    public function test_advance_last_leg_status_keeps_current_when_unchanged(): void
    {
        $this->assertSame(
            'Canceled',
            $this->invokeAdvanceLastLegStatus('Canceled', 'Canceled')
        );
    }

    public function test_matrix_uses_preserved_last_after_search_to_canceled_transition(): void
    {
        $matrix = new OrderLegActionMatrix();

        $result = $matrix->resolveBonusPhase('Canceled', 'SearchesForCar', 'SearchesForCar');

        $this->assertNotNull($result);
        $this->assertSame('ничего', $result['bonus_action']);
        $this->assertSame('опрос', $result['double_action']);
    }

    private function invokeAdvanceLastLegStatus(?string $atStart, ?string $afterPhase): ?string
    {
        $executor = new OrderForkLegExecutor(new UniversalAndroidFunctionController(), []);
        $method = new ReflectionMethod(OrderForkLegExecutor::class, 'advanceLastLegStatus');
        $method->setAccessible(true);

        return $method->invoke($executor, $atStart, $afterPhase);
    }
}
