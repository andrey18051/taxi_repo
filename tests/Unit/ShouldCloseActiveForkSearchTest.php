<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\Orderweb;
use Tests\TestCase;

class ShouldCloseActiveForkSearchTest extends TestCase
{
    public function test_operator_cancel_while_other_leg_searches_closes_order(): void
    {
        $this->assertTrue($this->shouldClose(
            $this->canceledLeg(1),
            $this->leg('SearchesForCar'),
            $this->orderweb(null),
            false
        ));
    }

    public function test_close_reason_two_while_searching_closes_order(): void
    {
        $this->assertTrue($this->shouldClose(
            $this->canceledLeg(2),
            $this->leg('WaitingCarSearch'),
            $this->orderweb(null),
            false
        ));
    }

    public function test_armed_recreation_keeps_restore(): void
    {
        $this->assertFalse($this->shouldClose(
            $this->canceledLeg(1),
            $this->leg('SearchesForCar'),
            $this->orderweb(null),
            true
        ));
    }

    public function test_assigned_car_keeps_restore(): void
    {
        $this->assertFalse($this->shouldClose(
            $this->canceledLeg(1),
            $this->leg('SearchesForCar'),
            $this->orderweb('AA1234BB'),
            false
        ));
    }

    public function test_other_leg_already_running_keeps_restore(): void
    {
        $this->assertFalse($this->shouldClose(
            $this->canceledLeg(1),
            $this->leg('Running'),
            $this->orderweb(null),
            false
        ));
    }

    public function test_technical_close_reason_keeps_restore(): void
    {
        $this->assertFalse($this->shouldClose(
            $this->canceledLeg(-1),
            $this->leg('SearchesForCar'),
            $this->orderweb(null),
            false
        ));
    }

    /**
     * @param array<string, mixed> $canceled
     * @param array<string, mixed> $other
     */
    private function shouldClose(array $canceled, array $other, ?Orderweb $orderweb, bool $armed): bool
    {
        return OrderStatusController::shouldCloseActiveForkSearchInsteadOfRestore(
            $canceled,
            $other,
            $orderweb,
            $armed
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function canceledLeg(int $closeReason): array
    {
        return [
            'execution_status' => 'Canceled',
            'close_reason' => $closeReason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leg(string $status): array
    {
        return [
            'execution_status' => $status,
            'close_reason' => -1,
        ];
    }

    private function orderweb(?string $auto): Orderweb
    {
        $orderweb = new Orderweb();
        $orderweb->auto = $auto;
        $orderweb->closeReason = '-1';

        return $orderweb;
    }
}
