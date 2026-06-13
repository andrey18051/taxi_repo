<?php

namespace Tests\Unit;

use App\Services\OrderLegActionMatrix;
use Tests\TestCase;

class OrderLegActionMatrixTest extends TestCase
{
    /** @var OrderLegActionMatrix */
    private $matrix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matrix = new OrderLegActionMatrix();
    }

    public function test_bonus_search_with_double_car_found_cancels_bonus(): void
    {
        $result = $this->matrix->resolve('SearchesForCar', 'CarFound');

        $this->assertNotNull($result);
        $this->assertSame('отмена', $result['bonus_action']);
        $this->assertSame('ничего', $result['double_action']);
    }

    public function test_bonus_search_with_double_cost_calculation_restores_double(): void
    {
        $result = $this->matrix->resolve('SearchesForCar', 'CostCalculation');

        $this->assertNotNull($result);
        $this->assertSame('опрос', $result['bonus_action']);
        $this->assertSame('востановление', $result['double_action']);
    }

    public function test_both_searching_polls_bonus_only(): void
    {
        $result = $this->matrix->resolve('SearchesForCar', 'SearchesForCar');

        $this->assertNotNull($result);
        $this->assertSame('опрос', $result['bonus_action']);
        $this->assertSame('ничего', $result['double_action']);
    }

    public function test_car_found_with_double_search_cancels_double(): void
    {
        $result = $this->matrix->resolveBonusPhase('CarFound', 'SearchesForCar', null);

        $this->assertNotNull($result);
        $this->assertSame('опрос', $result['bonus_action']);
        $this->assertSame('отмена', $result['double_action']);
    }

    public function test_canceled_bonus_with_search_last_polls_double_only(): void
    {
        $result = $this->matrix->resolveBonusPhase('Canceled', 'SearchesForCar', 'SearchesForCar');

        $this->assertNotNull($result);
        $this->assertSame('ничего', $result['bonus_action']);
        $this->assertSame('опрос', $result['double_action']);
    }

    public function test_canceled_bonus_restore_when_last_was_car_found(): void
    {
        $result = $this->matrix->resolveBonusPhase('Canceled', 'SearchesForCar', 'CarFound');

        $this->assertNotNull($result);
        $this->assertSame('востановление', $result['bonus_action']);
        $this->assertSame('опрос', $result['double_action']);
    }

    public function test_nal_phase_double_search_polls_double(): void
    {
        $result = $this->matrix->resolveNalPhase('SearchesForCar', 'SearchesForCar', null);

        $this->assertNotNull($result);
        $this->assertSame('опрос', $result['double_action']);
        $this->assertSame('ничего', $result['bonus_action']);
    }

    public function test_nal_phase_canceled_bonus_with_searching_double_polls_double(): void
    {
        $result = $this->matrix->resolveNalPhase('SearchesForCar', 'Canceled', 'SearchesForCar');

        $this->assertNotNull($result);
        $this->assertSame('опрос', $result['double_action']);
        $this->assertSame('ничего', $result['bonus_action']);
    }

    public function test_nal_phase_canceled_bonus_restore_when_last_double_was_car_found(): void
    {
        $result = $this->matrix->resolveNalPhase('SearchesForCar', 'Canceled', 'CarFound');

        $this->assertNotNull($result);
        $this->assertSame('опрос', $result['double_action']);
        $this->assertSame('востановление', $result['bonus_action']);
    }
}
