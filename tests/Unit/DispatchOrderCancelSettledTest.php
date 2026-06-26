<?php

namespace Tests\Unit;

use App\Services\DispatchOrderCancelService;
use PHPUnit\Framework\TestCase;

class DispatchOrderCancelSettledTest extends TestCase
{
    /** @var DispatchOrderCancelService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DispatchOrderCancelService();
    }

    public function test_settled_when_close_reason_is_one(): void
    {
        $this->assertTrue($this->service->isDispatchCancelSettled(['close_reason' => 1]));
    }

    public function test_settled_when_order_is_archived(): void
    {
        $this->assertTrue($this->service->isDispatchCancelSettled([
            'close_reason' => -1,
            'order_is_archive' => true,
        ]));
    }

    public function test_not_settled_when_close_reason_pending(): void
    {
        $this->assertFalse($this->service->isDispatchCancelSettled(['close_reason' => -1]));
        $this->assertFalse($this->service->isDispatchCancelSettled(['close_reason' => 2]));
        $this->assertFalse($this->service->isDispatchCancelSettled(null));
    }

    public function test_not_settled_when_canceled_execution_without_close_reason(): void
    {
        $this->assertFalse($this->service->isDispatchCancelSettled([
            'close_reason' => -1,
            'execution_status' => 'Canceled',
        ], 'campaign-uid'));
    }

    public function test_fork_requires_both_legs_settled(): void
    {
        $bonusSettled = ['close_reason' => 1];
        $nalActive = ['close_reason' => -1];

        $this->assertTrue($this->service->isDispatchCancelSettled($bonusSettled));
        $this->assertFalse($this->service->isDispatchCancelSettled($nalActive));
    }
}
