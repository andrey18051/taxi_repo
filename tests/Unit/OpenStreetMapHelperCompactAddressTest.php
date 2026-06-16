<?php

namespace Tests\Unit;

use App\Helpers\OpenStreetMapHelper;
use Tests\TestCase;

class OpenStreetMapHelperCompactAddressTest extends TestCase
{
    public function test_street_with_house_without_city(): void
    {
        $result = OpenStreetMapHelper::formatCompactReverseAddress([
            'road' => 'Лютеранська вулиця',
            'house_number' => '3',
            'city' => 'Киев',
            'state' => 'Киевская область',
            'country' => 'Украина',
        ], null, 'ru');

        $this->assertSame('Лютеранська вулиця, д. 3', $result);
    }

    public function test_residential_with_suburb(): void
    {
        $result = OpenStreetMapHelper::formatCompactReverseAddress([
            'residential' => 'ОК ЖСТ "Морське"',
            'suburb' => 'Аркадия',
            'borough' => 'Приморский район',
            'city' => 'Одесса',
            'municipality' => 'Одеська міська громада',
            'state' => 'Одесская область',
            'postcode' => '65062',
            'country' => 'Украина',
        ], null, 'ru');

        $this->assertSame('Аркадия, ОК ЖСТ "Морське"', $result);
    }

    public function test_street_with_suburb(): void
    {
        $result = OpenStreetMapHelper::formatCompactReverseAddress([
            'road' => 'Трасса здоровья',
            'suburb' => 'Ланжерон',
            'city' => 'Одесса',
        ], 'Катакомби', 'ru');

        $this->assertSame('Ланжерон, Трасса здоровья', $result);
    }
}
