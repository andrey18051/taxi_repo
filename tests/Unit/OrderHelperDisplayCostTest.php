<?php

namespace Tests\Unit;

use App\Helpers\OrderHelper;
use Tests\TestCase;

class OrderHelperDisplayCostTest extends TestCase
{
    public function test_after_nal_add_cost_does_not_double_count_attempt_20(): void
    {
        $order = (object) [
            'web_cost' => 75,
            'client_cost' => 75,
            'attempt_20' => 15,
            'finish_cost' => null,
        ];

        $this->assertSame(75, OrderHelper::resolveDisplayCostGrivna($order));
    }

    public function test_prefers_finish_cost_when_set(): void
    {
        $order = (object) [
            'web_cost' => 60,
            'client_cost' => 60,
            'attempt_20' => 0,
            'finish_cost' => 80,
        ];

        $this->assertSame(80, OrderHelper::resolveDisplayCostGrivna($order));
    }

    public function test_prefers_client_cost_over_web_cost(): void
    {
        $order = (object) [
            'web_cost' => 60,
            'client_cost' => 70,
            'attempt_20' => 0,
            'finish_cost' => null,
        ];

        $this->assertSame(70, OrderHelper::resolveDisplayCostGrivna($order));
    }

    public function test_falls_back_to_web_cost_when_client_cost_empty(): void
    {
        $order = (object) [
            'web_cost' => 90,
            'client_cost' => 0,
            'attempt_20' => 15,
            'finish_cost' => null,
        ];

        $this->assertSame(90, OrderHelper::resolveDisplayCostGrivna($order));
    }
}
