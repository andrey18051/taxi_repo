<?php

namespace Tests\Unit;

use App\Helpers\OpenStreetMapHelper;
use Tests\TestCase;

class OpenStreetMapHelperCompactAddressTest extends TestCase
{
    public function test_visicom_properties_format(): void
    {
        $result = OpenStreetMapHelper::buildAddressFromVisicomProperties([
            'street_type' => 'вул. ',
            'street' => 'Глаубермана (Державіна)',
            'name' => '11',
            'settlement_type' => 'місто ',
            'settlement' => 'Одеса',
        ], 'uk');

        $this->assertSame('вул. Глаубермана (Державіна), буд.11, місто  Одеса', $result);
    }

    public function test_visicom_street_type_and_name_separated_ru(): void
    {
        $result = OpenStreetMapHelper::buildAddressFromVisicomProperties([
            'street_type' => 'ул.',
            'street' => 'Князей Острожских (Московская)',
            'name' => '26',
            'settlement_type' => 'город ',
            'settlement' => 'Киев',
        ], 'ru');

        $this->assertSame('ул. Князей Острожских (Московская), д.26, город  Киев', $result);
    }

    public function test_visicom_spusk_street_type_separated(): void
    {
        $result = OpenStreetMapHelper::buildAddressFromVisicomProperties([
            'street_type' => 'спуск',
            'street' => 'Кловский',
            'name' => '6',
            'settlement_type' => 'город ',
            'settlement' => 'Киев',
        ], 'ru');

        $this->assertSame('спуск Кловский, д.6, город  Киев', $result);
    }

    public function test_nominatim_street_house_city_like_visicom(): void
    {
        $result = OpenStreetMapHelper::buildAddressFromNominatim([
            'road' => 'Лютеранська вулиця',
            'house_number' => '3',
            'city' => 'Киев',
        ], null, 'ru');

        $this->assertSame('Лютеранська вулиця, д.3, город Киев', $result);
    }

    public function test_nominatim_residential_with_city(): void
    {
        $result = OpenStreetMapHelper::buildAddressFromNominatim([
            'residential' => 'ОК ЖСТ "Морське"',
            'city' => 'Одесса',
        ], null, 'ru');

        $this->assertSame('ОК ЖСТ "Морське", город Одесса', $result);
    }
}
