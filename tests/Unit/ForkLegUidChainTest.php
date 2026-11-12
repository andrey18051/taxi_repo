<?php

namespace Tests\Unit;

use App\Services\ForkLegUidChain;
use App\Models\MemoryOrderChange;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForkLegUidChainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

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
        parent::tearDown();
    }

    public function test_chain_includes_later_orders_even_when_seed_is_the_first_pair(): void
    {
        $chain = new ForkLegUidChain();
        $chain->rememberReplacement('bonus-1', 'bonus-2');
        $chain->rememberReplacement('double-1', 'double-2');

        $uids = $chain->allUids('bonus-1', 'double-1', 'bonus-1');

        $this->assertEqualsCanonicalizing(
            ['bonus-1', 'bonus-2', 'double-1', 'double-2'],
            $uids
        );
    }

    public function test_remember_replacement_does_not_duplicate_the_same_link(): void
    {
        $chain = new ForkLegUidChain();
        $chain->rememberReplacement('bonus-1', 'bonus-2');
        $chain->rememberReplacement('bonus-1', 'bonus-2');

        $this->assertSame(1, MemoryOrderChange::count());
    }
}
