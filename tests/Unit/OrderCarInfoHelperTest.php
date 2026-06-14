<?php

namespace Tests\Unit;

use App\Services\OrderCarInfoHelper;
use Tests\TestCase;

class OrderCarInfoHelperTest extends TestCase
{
    public function test_formats_json_auto_for_app(): void
    {
        $json = json_encode([
            'number' => 'AC4453CM',
            'color' => 'Белый',
            'brand' => 'Renault',
            'model' => 'Logan',
            'phoneNumber' => '+380933464747',
        ]);

        $result = OrderCarInfoHelper::formatForApp($json);

        $this->assertNotNull($result);
        $this->assertSame('AC4453CM, цвет Белый  Renault Logan.', $result['order_car_info']);
        $this->assertSame('+380933464747', $result['driver_phone']);
    }

    public function test_formats_vod_comma_string_for_app(): void
    {
        $vod = 'AC4453CM, Белый, Renault Logan, +380933464747';

        $result = OrderCarInfoHelper::formatForApp($vod);

        $this->assertNotNull($result);
        $this->assertSame('AC4453CM, цвет Белый  Renault Logan.', $result['order_car_info']);
        $this->assertSame('+380933464747', $result['driver_phone']);
    }

    public function test_returns_null_for_malformed_placeholder(): void
    {
        $this->assertNull(OrderCarInfoHelper::formatForApp(', цвет    . '));
        $this->assertNull(OrderCarInfoHelper::formatForApp(''));
        $this->assertNull(OrderCarInfoHelper::formatForApp(null));
    }

    public function test_action_from_close_reason(): void
    {
        $this->assertSame('В пути', OrderCarInfoHelper::actionFromCloseReason('103'));
        $this->assertSame('Поиск авто', OrderCarInfoHelper::actionFromCloseReason('-1'));
    }
}
