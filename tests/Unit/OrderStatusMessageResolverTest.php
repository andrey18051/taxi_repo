<?php

namespace Tests\Unit;

use App\Services\OrderStatusMessageResolver;
use Tests\TestCase;

class OrderStatusMessageResolverTest extends TestCase
{
    /** @var OrderStatusMessageResolver */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new OrderStatusMessageResolver();
    }

    public function test_all_fixture_pairs_match_expected_app_action(): void
    {
        $cases = json_decode(
            (string) file_get_contents(base_path('tests/fixtures/order_status_app_cases.json')),
            true
        );

        $this->assertIsArray($cases);
        foreach ($cases as $case) {
            $result = $this->resolver->resolve($case['nal'], $case['card']);
            $this->assertSame(
                $case['action'],
                $result['action'],
                "Unexpected action for {$case['nal']}|{$case['card']}"
            );
            $this->assertSame(
                $case['leg'],
                $result['response_leg'],
                "Unexpected response leg for {$case['nal']}|{$case['card']}"
            );
        }
    }

    public function test_taxialfa_running_plus_canceled_card_returns_car_found(): void
    {
        $result = $this->resolver->resolve('Running', 'Canceled');

        $this->assertSame('Авто найдено', $result['action']);
        $this->assertSame('nal', $result['response_leg']);
    }

    public function test_fallback_does_not_downgrade_when_one_leg_dispatched(): void
    {
        $result = $this->resolver->resolve('UnknownState', 'CarFound');

        $this->assertSame('Авто найдено', $result['action']);
        $this->assertSame('card', $result['response_leg']);
    }

    public function test_running_active_leg_maps_to_in_route_for_app(): void
    {
        $nalOrder = [
            'execution_status' => 'Running',
            'close_reason' => -1,
            'order_car_info' => 'AA1234',
        ];

        $result = $this->resolver->resolve('Running', 'Canceled', $nalOrder, null);

        $this->assertSame('В пути', $result['action']);
        $this->assertSame(103, $result['close_reason']);
    }

    public function test_executed_with_canceled_other_leg_is_completed(): void
    {
        $result = $this->resolver->resolve('Canceled', 'Executed');

        $this->assertSame('Заказ выполнен', $result['action']);
    }

    public function test_both_canceled_with_operator_close_returns_canceled(): void
    {
        $nalOrder = ['execution_status' => 'Canceled', 'close_reason' => 9];
        $cardOrder = ['execution_status' => 'Canceled', 'close_reason' => -1];

        $result = $this->resolver->resolve('Canceled', 'Canceled', $nalOrder, $cardOrder);

        $this->assertSame('Заказ снят', $result['action']);
    }
}
