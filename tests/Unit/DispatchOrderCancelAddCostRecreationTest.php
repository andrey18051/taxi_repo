<?php

namespace Tests\Unit;

use App\Services\DispatchOrderCancelService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DispatchOrderCancelAddCostRecreationTest extends TestCase
{
    protected function tearDown(): void
    {
        DispatchOrderCancelService::clearAddCostRecreationInProgress('uid-old');
        parent::tearDown();
    }

    public function test_marks_and_clears_add_cost_recreation_flag(): void
    {
        $this->assertFalse(DispatchOrderCancelService::isAddCostRecreationInProgress('uid-old'));

        DispatchOrderCancelService::markAddCostRecreationInProgress('uid-old');

        $this->assertTrue(DispatchOrderCancelService::isAddCostRecreationInProgress('uid-old'));

        DispatchOrderCancelService::clearAddCostRecreationInProgress('uid-old');

        $this->assertFalse(DispatchOrderCancelService::isAddCostRecreationInProgress('uid-old'));
    }

    public function test_recreation_flag_uses_cache_prefix(): void
    {
        DispatchOrderCancelService::markAddCostRecreationInProgress('uid-old');

        $this->assertTrue(
            Cache::has(DispatchOrderCancelService::ADD_COST_RECREATION_CACHE_PREFIX . 'uid-old')
        );
    }
}
