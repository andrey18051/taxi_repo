<?php

namespace Tests\Unit;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use App\Models\WfpInvoice;
use App\Services\DispatchOrderCancelService;
use App\Services\WfpHoldRefundEligibility;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WfpSupersededHoldRefundGateTest extends TestCase
{
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
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::create('memory_order_changes', function (Blueprint $table) {
            $table->id();
            $table->string('order_old')->nullable();
            $table->string('order_new')->nullable();
            $table->timestamps();
        });

        Schema::create('uid_histories', function (Blueprint $table) {
            $table->id();
            $table->string('uid_bonusOrderHold')->nullable();
            $table->string('uid_bonusOrder')->nullable();
            $table->string('uid_doubleOrder')->nullable();
            $table->timestamps();
        });

        Schema::create('wfp_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('merchantAccount')->nullable();
            $table->string('orderReference')->nullable();
            $table->string('amount')->nullable();
            $table->string('transactionStatus')->nullable();
            $table->string('reason')->nullable();
            $table->string('reasonCode')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wfp_invoices');
        Schema::dropIfExists('uid_histories');
        Schema::dropIfExists('memory_order_changes');
        Schema::dropIfExists('orderwebs');
        parent::tearDown();
    }

    public function test_superseded_refund_allowed_without_predecessors(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'new-uid';
        $orderweb->server = 'http://188.40.143.61:7222';
        $orderweb->save();

        $eligibility = new WfpHoldRefundEligibility(new DispatchOrderCancelService());

        $this->assertTrue($eligibility->mayRefundSupersededMainHold($orderweb));
    }

    public function test_superseded_refund_blocked_when_predecessor_snapshot_unavailable(): void
    {
        $mapping = new MemoryOrderChange();
        $mapping->order_old = 'old-uid';
        $mapping->order_new = 'new-uid';
        $mapping->save();

        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'new-uid';
        $orderweb->server = 'http://188.40.143.61:7222';
        $orderweb->save();

        $predecessors = (new MemoryOrderChangeController())->collectPredecessorUids('new-uid');
        $this->assertSame(['old-uid'], $predecessors);

        $eligibility = new WfpHoldRefundEligibility(new DispatchOrderCancelService());

        $this->assertFalse($eligibility->mayRefundSupersededMainHold($orderweb));
    }

    public function test_superseded_refund_blocked_when_multiple_active_holds_on_add_cost(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'live-uid';
        $orderweb->server = 'http://188.40.143.61:7222';
        $orderweb->save();

        WfpInvoice::create([
            'dispatching_order_uid' => 'live-uid',
            'orderReference' => 'V_MAIN_10',
            'amount' => '10',
            'transactionStatus' => 'WaitingAuthComplete',
        ]);
        WfpInvoice::create([
            'dispatching_order_uid' => 'live-uid',
            'orderReference' => 'V_ADD_5',
            'amount' => '5',
            'transactionStatus' => 'WaitingAuthComplete',
        ]);

        $eligibility = new WfpHoldRefundEligibility(new DispatchOrderCancelService());

        $this->assertFalse($eligibility->mayRefundSupersededMainHold($orderweb->fresh()));
    }
}
