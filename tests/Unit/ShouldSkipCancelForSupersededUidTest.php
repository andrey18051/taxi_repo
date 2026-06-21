<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderStatusController;
use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ShouldSkipCancelForSupersededUidTest extends TestCase
{
    private const OLD_UID = '05374d01612e48029b257e5c799b4e7c';
    private const NEW_UID = '81071180b1be4c6282cc84e99710d641';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('closeReason')->nullable();
            $table->timestamp('cancel_timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('memory_order_changes', function (Blueprint $table) {
            $table->id();
            $table->string('order_old')->nullable();
            $table->string('order_new')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('memory_order_changes');
        Schema::dropIfExists('orderwebs');
        parent::tearDown();
    }

    public function test_non_persisted_order_does_not_skip_cancel(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = self::NEW_UID;

        $this->assertFalse($this->invokeShouldSkip(self::OLD_UID, $orderweb));
    }

    private function makeOrderweb(string $uid, string $closeReason = '-1'): Orderweb
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = $uid;
        $orderweb->closeReason = $closeReason;
        $orderweb->save();

        return $orderweb;
    }

    private function makeMemoryMapping(string $oldUid, string $newUid): void
    {
        $mapping = new MemoryOrderChange();
        $mapping->order_old = $oldUid;
        $mapping->order_new = $newUid;
        $mapping->save();
    }

    public function test_skip_cancel_when_orderweb_already_points_to_new_uid(): void
    {
        $orderweb = $this->makeOrderweb(self::NEW_UID);

        $this->assertTrue($this->invokeShouldSkip(self::OLD_UID, $orderweb));
    }

    public function test_skip_cancel_when_polled_uid_superseded_by_mapping(): void
    {
        $this->makeMemoryMapping(self::OLD_UID, self::NEW_UID);
        $orderweb = $this->makeOrderweb(self::NEW_UID);

        $this->assertTrue($this->invokeShouldSkip(self::OLD_UID, $orderweb));
    }

    public function test_do_not_skip_cancel_for_active_order_with_same_uid(): void
    {
        $orderweb = $this->makeOrderweb(self::NEW_UID);

        $this->assertFalse($this->invokeShouldSkip(self::NEW_UID, $orderweb));
    }

    public function test_finalize_does_not_apply_cancel_when_uid_superseded(): void
    {
        $orderweb = $this->makeOrderweb(self::NEW_UID);

        $this->invokeFinalizeCanceled($orderweb, self::OLD_UID);

        $orderweb->refresh();
        $this->assertNull($orderweb->cancel_timestamp);
        $this->assertSame('-1', (string) $orderweb->closeReason);
    }

    private function invokeShouldSkip(string $polledUid, Orderweb $orderweb): bool
    {
        $method = new ReflectionMethod(OrderStatusController::class, 'shouldSkipCancelForSupersededUid');
        $method->setAccessible(true);

        return (bool) $method->invoke(null, $orderweb, $polledUid);
    }

    private function invokeFinalizeCanceled(Orderweb $orderweb, string $uid): void
    {
        $method = new ReflectionMethod(OrderStatusController::class, 'finalizeCanceledFromStatusPush');
        $method->setAccessible(true);
        $method->invoke(null, $orderweb, $uid);
    }
}
