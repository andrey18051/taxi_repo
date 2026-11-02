<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class FindOrderwebForStatusPushTest extends TestCase
{
    private const CARD_UID = '8377f570706d4f8d8a44277b1f77a86f';
    private const NAL_UID = '1c26ef22ca0c41a0a4a4243483a94295';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('closeReason')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('orderwebs');
        parent::tearDown();
    }

    public function test_nal_uid_poll_finds_orderweb_stored_on_card_uid(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = self::CARD_UID;
        $orderweb->closeReason = '-1';
        $orderweb->save();

        $history = new Uid_history();
        $history->uid_bonusOrder = self::CARD_UID;
        $history->uid_doubleOrder = self::NAL_UID;

        $found = $this->invokeFind(
            self::NAL_UID,
            $history,
            self::CARD_UID,
            ['dispatching_order_uid' => self::NAL_UID],
            ['dispatching_order_uid' => self::CARD_UID]
        );

        $this->assertNotNull($found);
        $this->assertSame(self::CARD_UID, $found->dispatching_order_uid);
    }

    public function test_missing_orderweb_returns_null(): void
    {
        $history = new Uid_history();
        $history->uid_bonusOrder = self::CARD_UID;
        $history->uid_doubleOrder = self::NAL_UID;

        $found = $this->invokeFind(self::NAL_UID, $history, self::CARD_UID, null, null);

        $this->assertNull($found);
    }

    private function invokeFind(
        string $polledUid,
        ?Uid_history $history,
        string $dispatchingOrderUid,
        ?array $nalOrder,
        ?array $cardOrder
    ): ?Orderweb {
        $method = new ReflectionMethod(OrderStatusController::class, 'findOrderwebForStatusPush');
        $method->setAccessible(true);

        return $method->invoke(
            new OrderStatusController(),
            $polledUid,
            $history,
            $dispatchingOrderUid,
            $nalOrder,
            $cardOrder
        );
    }
}
