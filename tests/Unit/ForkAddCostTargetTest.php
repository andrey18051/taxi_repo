<?php

namespace Tests\Unit;

use App\Http\Controllers\WfpController;
use App\Models\DriverMemoryOrder;
use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Models\WfpInvoice;
use App\Support\ForkAddCostTarget;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForkAddCostTargetTest extends TestCase
{
    private const HOLD = '89c10532f37e4cc788e445884185bcf7';
    private const BONUS = 'a9cc4a89f75f45d883e830163bcb9cb8';
    private const DOUBLE = 'aed25581a783436382c3fea6fa5dfd3a';

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required');
        }

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('wfp_order_id')->nullable();
            $table->string('web_cost')->nullable();
            $table->string('client_cost')->nullable();
            $table->string('cancel_timestamp')->nullable();
            $table->timestamps();
        });
        Schema::create('uid_histories', function (Blueprint $table) {
            $table->id();
            $table->string('uid_bonusOrderHold')->nullable();
            $table->string('uid_bonusOrder')->nullable();
            $table->string('uid_doubleOrder')->nullable();
            $table->timestamps();
        });
        Schema::create('memory_order_changes', function (Blueprint $table) {
            $table->id();
            $table->string('order_old')->nullable();
            $table->string('order_new')->nullable();
            $table->timestamps();
        });
        Schema::create('driver_memory_orders', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->text('response')->nullable();
            $table->timestamps();
        });

        Orderweb::unsetEventDispatcher();
        Uid_history::unsetEventDispatcher();
        MemoryOrderChange::unsetEventDispatcher();
        WfpInvoice::unsetEventDispatcher();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('driver_memory_orders');
        Schema::dropIfExists('memory_order_changes');
        Schema::dropIfExists('uid_histories');
        Schema::dropIfExists('orderwebs');
        parent::tearDown();
    }

    public function test_recreated_fork_leg_resolves_hold_order_and_live_pair(): void
    {
        $order = new Orderweb();
        $order->dispatching_order_uid = self::HOLD;
        $order->wfp_order_id = 'V_MAIN';
        $order->web_cost = '7';
        $order->client_cost = '7';
        $order->save();

        $history = new Uid_history();
        $history->uid_bonusOrderHold = self::HOLD;
        $history->uid_bonusOrder = self::BONUS;
        $history->uid_doubleOrder = self::DOUBLE;
        $history->save();

        $link = new MemoryOrderChange();
        $link->order_old = self::HOLD;
        $link->order_new = self::BONUS;
        $link->save();

        $memory = new DriverMemoryOrder();
        $memory->dispatching_order_uid = self::HOLD;
        $memory->response = '{"add_cost":0}';
        $memory->save();

        $resolved = ForkAddCostTarget::resolve(self::BONUS);

        $this->assertNotNull($resolved);
        $this->assertSame(self::HOLD, $resolved['order']->dispatching_order_uid);
        $this->assertSame(self::BONUS, $resolved['bonusUid']);
        $this->assertSame(self::DOUBLE, $resolved['doubleUid']);
        $this->assertSame(self::HOLD, ForkAddCostTarget::findMemory(self::BONUS)->dispatching_order_uid);

        $invoice = new WfpInvoice();
        $invoice->dispatching_order_uid = self::BONUS;
        $invoice->orderReference = 'V_ADD';
        $invoice->amount = '5';

        $this->assertTrue((new WfpController())->isAddCostInvoice($invoice));
    }
}
