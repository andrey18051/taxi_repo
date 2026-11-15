<?php

namespace Tests\Unit;

use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Services\DispatchOrderCancelService;
use App\Support\AddCostClientPayload;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForkAddCostRecreationLookupTest extends TestCase
{
    private const HOLD = 'a7566353d9324de5ad3ef196eb1abb7d';
    private const BONUS = 'd2918048533e46d69c90d9dc25967868';
    private const DOUBLE = '30037e874f9b4b758fa099584a8f354c';

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
            $table->string('server')->nullable();
            $table->string('pay_system')->nullable();
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

        Orderweb::unsetEventDispatcher();
        Uid_history::unsetEventDispatcher();
        MemoryOrderChange::unsetEventDispatcher();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('memory_order_changes');
        Schema::dropIfExists('uid_histories');
        Schema::dropIfExists('orderwebs');
        DispatchOrderCancelService::clearAddCostRecreationInProgress(self::BONUS);
        DispatchOrderCancelService::clearAddCostRecreationInProgress(self::HOLD);
        parent::tearDown();
    }

    public function test_add_cost_cancel_finds_orderweb_by_fork_hold_not_live_leg(): void
    {
        $order = new Orderweb();
        $order->dispatching_order_uid = self::HOLD;
        $order->server = 'my_server_api';
        $order->pay_system = 'wfp_payment';
        $order->save();

        $history = new Uid_history();
        $history->uid_bonusOrderHold = self::HOLD;
        $history->uid_bonusOrder = self::BONUS;
        $history->uid_doubleOrder = self::DOUBLE;
        $history->save();

        $result = (new DispatchOrderCancelService())->waitForAddCostRecreationCancel([
            'primary_uid' => self::BONUS,
            'city' => 'OdessaTest',
            'application' => 'PAS4',
            'payment_type' => 'wfp_payment',
            'resolve_uid_mapping' => false,
            'legs' => [
                ['uid' => self::BONUS, 'auth_role' => 'bonus'],
                ['uid' => self::DOUBLE, 'auth_role' => 'double'],
            ],
        ]);

        $this->assertTrue($result['ready']);
    }

    public function test_pending_cancel_payload_has_transaction_status(): void
    {
        $payload = AddCostClientPayload::pendingCancel();

        $this->assertSame('dispatch_cancel_pending', $payload['response']);
        $this->assertSame('InProcessing', $payload['transactionStatus']);
        $this->assertNotSame('', $payload['transactionStatus']);
    }

    public function test_failed_payload_has_transaction_status(): void
    {
        $payload = AddCostClientPayload::failed('Новый заказ не создался');

        $this->assertSame('Новый заказ не создался', $payload['response']);
        $this->assertSame('Declined', $payload['transactionStatus']);
    }
}
