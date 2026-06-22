<?php

namespace Tests\Unit;

use App\Services\DispatchOrderCancelService;
use PHPUnit\Framework\TestCase;

class DispatchOrderCancelSettledTest extends TestCase
{
    public function test_settled_when_archived(): void
    {
        $service = new DispatchOrderCancelService();
        $this->assertTrue($service->isDispatchCancelSettled([
            'order_is_archive' => true,
            'close_reason' => -1,
        ]));
    }

    public function test_settled_when_close_reason_one(): void
    {
        $service = new DispatchOrderCancelService();
        $this->assertTrue($service->isDispatchCancelSettled([
            'close_reason' => 1,
            'execution_status' => 'Canceled',
        ]));
    }

    public function test_not_settled_when_canceled_without_close_reason(): void
    {
        $service = new DispatchOrderCancelService();
        $this->assertFalse($service->isDispatchCancelSettled([
            'close_reason' => -1,
            'execution_status' => 'Canceled',
        ]));
    }
}
