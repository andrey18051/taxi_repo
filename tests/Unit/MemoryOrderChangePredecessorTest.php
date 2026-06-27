<?php

namespace Tests\Unit;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\MemoryOrderChange;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemoryOrderChangePredecessorTest extends TestCase
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

    public function test_collect_predecessor_uids_returns_chain_from_new_to_old(): void
    {
        $first = new MemoryOrderChange();
        $first->order_old = 'uid-a';
        $first->order_new = 'uid-b';
        $first->save();

        $second = new MemoryOrderChange();
        $second->order_old = 'uid-b';
        $second->order_new = 'uid-c';
        $second->save();

        $predecessors = (new MemoryOrderChangeController())->collectPredecessorUids('uid-c');

        $this->assertSame(['uid-b', 'uid-a'], $predecessors);
    }
}
