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

    public function test_collect_add_cost_recreation_uids_includes_legs(): void
    {
        $uids = DispatchOrderCancelService::collectAddCostRecreationUids(
            'primary-raw',
            'primary-resolved',
            [
                ['uid' => 'leg-bonus'],
                ['uid' => 'leg-double'],
                ['uid' => ''],
            ]
        );

        $this->assertSame(
            ['primary-raw', 'primary-resolved', 'leg-bonus', 'leg-double'],
            $uids
        );
    }

    public function test_clear_add_cost_recreation_uids_batch(): void
    {
        DispatchOrderCancelService::markAddCostRecreationInProgress('uid-a');
        DispatchOrderCancelService::markAddCostRecreationInProgress('uid-b');

        DispatchOrderCancelService::clearAddCostRecreationUids(['uid-a', 'uid-b']);

        $this->assertFalse(DispatchOrderCancelService::isAddCostRecreationInProgress('uid-a'));
        $this->assertFalse(DispatchOrderCancelService::isAddCostRecreationInProgress('uid-b'));
    }
}
