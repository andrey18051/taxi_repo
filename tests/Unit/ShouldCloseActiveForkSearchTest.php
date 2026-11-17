<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use App\Services\DispatchOrderCancelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShouldCloseActiveForkSearchTest extends TestCase
{
    private const HOLD = 'hold-uid-addcost-101';
    private const OLD_BONUS = 'old-bonus-uid-addcost-101';
    private const NEW_BONUS = 'new-bonus-uid-addcost-101';

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required');
        }

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        \DB::purge('sqlite');
        \DB::reconnect('sqlite');

        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('closeReason')->nullable();
            $table->string('auto')->nullable();
            $table->timestamp('cancel_timestamp')->nullable();
            $table->timestamps();
        });
        Schema::create('memory_order_changes', function (Blueprint $table) {
            $table->id();
            $table->string('order_old')->nullable();
            $table->string('order_new')->nullable();
            $table->timestamps();
        });

        Orderweb::unsetEventDispatcher();
        MemoryOrderChange::unsetEventDispatcher();
    }

    protected function tearDown(): void
    {
        DispatchOrderCancelService::clearAddCostRecreationUids([
            self::HOLD,
            self::OLD_BONUS,
            self::NEW_BONUS,
        ]);
        Schema::dropIfExists('memory_order_changes');
        Schema::dropIfExists('orderwebs');
        parent::tearDown();
    }

    public function test_operator_cancel_while_other_leg_searches_closes_order(): void
    {
        $this->assertTrue($this->shouldClose(
            $this->canceledLeg(1),
            $this->leg('SearchesForCar'),
            $this->orderweb(null),
            false
        ));
    }

    public function test_add_cost_flag_skips_active_fork_search_close(): void
    {
        DispatchOrderCancelService::markAddCostRecreationInProgress(self::OLD_BONUS);

        $orderweb = $this->persistedOrderweb(self::HOLD);

        $this->assertTrue(OrderStatusController::shouldSkipActiveForkSearchCloseForAddCostRecreation(
            $orderweb,
            [self::HOLD, self::OLD_BONUS, '']
        ));
    }

    public function test_add_cost_recreate_does_not_stamp_cancel_on_new_uid(): void
    {
        $orderweb = $this->persistedOrderweb(self::NEW_BONUS);
        $mapping = new MemoryOrderChange();
        $mapping->order_old = self::OLD_BONUS;
        $mapping->order_new = self::NEW_BONUS;
        $mapping->save();

        $this->assertTrue(OrderStatusController::shouldSkipActiveForkSearchCloseForAddCostRecreation(
            $orderweb,
            [self::HOLD, self::OLD_BONUS, '']
        ));
        $this->assertFalse($this->shouldClose(
            $this->canceledLeg(1),
            $this->leg('SearchesForCar'),
            $orderweb,
            true
        ));
    }

    public function test_cash_driver_refusal_while_card_searches_keeps_the_card(): void
    {
        $this->assertFalse($this->shouldClose(
            $this->canceledLeg(2),
            $this->leg('SearchesForCar'),
            $this->orderweb(null),
            false
        ));
    }

    public function test_driver_refusal_while_searching_keeps_the_other_leg(): void
    {
        foreach ([2, 3, 5] as $closeReason) {
            $this->assertFalse($this->shouldClose(
                $this->canceledLeg($closeReason),
                $this->leg('WaitingCarSearch'),
                $this->orderweb(null),
                false
            ), 'close_reason ' . $closeReason);
        }
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

    private function persistedOrderweb(string $uid): Orderweb
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = $uid;
        $orderweb->closeReason = '-1';
        $orderweb->auto = null;
        $orderweb->save();

        return $orderweb;
    }
}
