<?php
// database/seeders/TariffSeeder.php

namespace Database\Seeders;

use App\Models\CityTariff;
use Illuminate\Database\Seeder;

class CityTariffSeeder extends Seeder
{
    public function run()
    {
        $cities = [
            [
                'city' => 'Kyiv City',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Cherkasy Oblast',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Odessa',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'OdessaTest',
                'base_price' => 50.00,
                'base_distance' => 3,
                'price_per_km' => 10.00,
                'is_test' => true
            ],
            [
                'city' => 'Zaporizhzhia',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Dnipropetrovsk Oblast',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Lviv',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Ivano_frankivsk',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Vinnytsia',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Poltava',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Sumy',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Kharkiv',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Chernihiv',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Rivne',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Ternopil',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Khmelnytskyi',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Zakarpattya',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Zhytomyr',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Kropyvnytskyi',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Mykolaiv',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Chernivtsi',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'Lutsk',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ],
            [
                'city' => 'all',
                'base_price' => 100.00,
                'base_distance' => 3,
                'price_per_km' => 13.00,
                'is_test' => false
            ]
        ];

        foreach ($cities as $cityData) {
            CityTariff::create($cityData);
        }

        $this->command->info('Тарифы для всех городов успешно созданы!');
    }
}
