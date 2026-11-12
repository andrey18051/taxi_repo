<?php

namespace Tests\Unit;

use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Services\ForkLegUidChain;
use App\Support\ForkOrderCancelLegResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForkOrderCancelLegResolverTest extends TestCase
{
    public function test_nal_order_uses_dispatching_order_uid_only(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'a1950002a8e44e5bbb19476ac5a47ee0';

        $history = new Uid_history();
        $history->uid_bonusOrder = 'afcc3e2ff9824428b0954139755b092c';
        $history->uid_doubleOrder = 'd19a0285529b4084a243315c6bf488d2';

        $legs = ForkOrderCancelLegResolver::resolveDispatchCancelLegs(
            $orderweb,
            $history,
            ['authorization' => 'token-nal']
        );

        $this->assertCount(1, $legs);
        $this->assertSame('a1950002a8e44e5bbb19476ac5a47ee0', $legs[0]['uid']);
        $this->assertSame('default', $legs[0]['auth_role']);
    }

    public function test_card_fork_includes_bonus_and_double_when_auth_present(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'hold-uid';

        $history = new Uid_history();
        $history->uid_bonusOrder = 'bonus-uid';
        $history->uid_doubleOrder = 'double-uid';

        $legs = ForkOrderCancelLegResolver::resolveDispatchCancelLegs(
            $orderweb,
            $history,
            [
                'authorization' => 'token-default',
                'authorizationBonus' => 'token-bonus',
                'authorizationDouble' => 'token-double',
            ]
        );

        $this->assertCount(3, $legs);
        $this->assertSame('hold-uid', $legs[0]['uid']);
        $this->assertSame('bonus-uid', $legs[1]['uid']);
        $this->assertSame('double-uid', $legs[2]['uid']);
    }

    public function test_has_live_pair_requires_both_legs(): void
    {
        $pair = new Uid_history();
        $pair->uid_bonusOrder = '2c189ffe26c04d7bbc9d0f59ba5a6172';
        $pair->uid_doubleOrder = '4ca84d2e9b614096a300608b8ce821a6';
        $this->assertTrue(ForkOrderCancelLegResolver::hasLivePair($pair));

        $cardOnly = new Uid_history();
        $cardOnly->uid_bonusOrder = '2c189ffe26c04d7bbc9d0f59ba5a6172';
        $this->assertFalse(ForkOrderCancelLegResolver::hasLivePair($cardOnly));
        $this->assertFalse(ForkOrderCancelLegResolver::hasLivePair(null));
    }

    public function test_live_sibling_prefers_history_uid_over_stale_loop_variable(): void
    {
        $history = new Uid_history();
        $history->uid_bonusOrder = 'bonus-2';
        $history->uid_doubleOrder = 'double-2';

        $this->assertSame(
            'double-2',
            ForkOrderCancelLegResolver::liveSiblingUid($history, 'double', 'double-1')
        );
        $this->assertSame(
            'bonus-2',
            ForkOrderCancelLegResolver::liveSiblingUid($history, 'bonus', 'bonus-1')
        );
        $this->assertSame(
            'fallback-double',
            ForkOrderCancelLegResolver::liveSiblingUid(null, 'double', 'fallback-double')
        );
    }

    public function test_recreated_fork_pair_is_included_in_cancel_legs(): void
    {
        $this->bootMemorySqlite();

        $chain = new ForkLegUidChain();
        $chain->rememberReplacement('bonus-1', 'bonus-2');
        $chain->rememberReplacement('double-1', 'double-2');

        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'hold-uid';

        $history = new Uid_history();
        $history->uid_bonusOrderHold = 'hold-uid';
        $history->uid_bonusOrder = 'bonus-2';
        $history->uid_doubleOrder = 'double-2';

        $legs = ForkOrderCancelLegResolver::resolveDispatchCancelLegs(
            $orderweb,
            $history,
            [
                'authorization' => 'token-default',
                'authorizationBonus' => 'token-bonus',
                'authorizationDouble' => 'token-double',
            ]
        );

        $uids = array_column($legs, 'uid');
        $this->assertContains('hold-uid', $uids);
        $this->assertContains('bonus-2', $uids);
        $this->assertContains('double-2', $uids);
        $this->assertContains('bonus-1', $uids);
        $this->assertContains('double-1', $uids);
    }

    public function test_find_orderweb_by_new_bonus_uid_uses_hold_row(): void
    {
        $this->bootMemorySqlite();

        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'hold-uid';
        $orderweb->closeReason = '-1';
        $orderweb->save();

        $history = new Uid_history();
        $history->uid_bonusOrderHold = 'hold-uid';
        $history->uid_bonusOrder = 'bonus-2';
        $history->uid_doubleOrder = 'double-2';
        $history->save();

        $found = ForkOrderCancelLegResolver::findOrderweb('bonus-2', 'double-2');

        $this->assertNotNull($found);
        $this->assertSame('hold-uid', $found->dispatching_order_uid);
    }

    public function test_find_history_by_predecessor_double_after_pair_restore(): void
    {
        $this->bootMemorySqlite();

        (new ForkLegUidChain())->rememberReplacement('double-1', 'double-2');

        $history = new Uid_history();
        $history->uid_bonusOrderHold = 'hold-uid';
        $history->uid_bonusOrder = 'bonus-2';
        $history->uid_doubleOrder = 'double-2';
        $history->save();

        $found = ForkOrderCancelLegResolver::findHistory('hold-uid', 'double-1');

        $this->assertNotNull($found);
        $this->assertSame('double-2', $found->uid_doubleOrder);
    }

    private function bootMemorySqlite(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for fork UID chain tests');
        }

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('closeReason')->nullable();
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

        Uid_history::unsetEventDispatcher();
        Orderweb::unsetEventDispatcher();
        MemoryOrderChange::unsetEventDispatcher();
    }
}
