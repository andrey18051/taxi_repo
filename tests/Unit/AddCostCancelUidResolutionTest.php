<?php

namespace Tests\Unit;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use App\Services\DispatchOrderCancelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class AddCostCancelUidResolutionTest extends TestCase
{
    private const OLD_UID = '16902baacc404e0680f029edddc92bf5';
    private const NEW_UID = 'c7fa1ab7782f46cfb0127c50d5833345';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('closeReason')->nullable();
            $table->string('pay_system')->nullable();
            $table->string('server')->nullable();
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

    public function test_add_cost_cancel_uses_raw_old_uid_after_mapping(): void
    {
        $mapping = new MemoryOrderChange();
        $mapping->order_old = self::OLD_UID;
        $mapping->order_new = self::NEW_UID;
        $mapping->save();

        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = self::NEW_UID;
        $orderweb->closeReason = '-1';
        $orderweb->pay_system = 'wfp_payment';
        $orderweb->server = 'http://188.40.143.61:7222';
        $orderweb->save();

        $service = new DispatchOrderCancelService();
        $resolveMethod = new ReflectionMethod(DispatchOrderCancelService::class, 'resolveCancelLegUid');
        $resolveMethod->setAccessible(true);

        $mappedUid = (new MemoryOrderChangeController())->show(self::OLD_UID);
        $this->assertSame(self::NEW_UID, $mappedUid);

        $cancelUid = $resolveMethod->invoke($service, self::OLD_UID, false);
        $this->assertSame(self::OLD_UID, $cancelUid);

        $lookupUid = (new MemoryOrderChangeController())->show(self::OLD_UID);
        $found = Orderweb::where('dispatching_order_uid', $lookupUid)->first();
        $this->assertNotNull($found);
        $this->assertSame(self::NEW_UID, $found->dispatching_order_uid);
    }
}
