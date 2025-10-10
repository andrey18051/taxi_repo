<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenStreetMapHelper
{
    private $client;

    private $regions = [
        'uk' => [
            'Київ', 'Львів', 'Харків', 'Одеса', 'Дніпро', 'Запоріжжя', 'Чернівці', 'Чернігів', 'Житомир',
            'Суми', 'Полтава', 'Вінниця', 'Івано-Франківськ', 'Хмельницький', 'Кропивницький', 'Рівне',
            'Тернопіль', 'Луцьк', 'Черкаси', 'Миколаїв', 'Херсон', 'Ужгород', 'Сєвєродонецьк'
        ],
        'ru' => [
            'Киев', 'Львов', 'Харьков', 'Одесса', 'Днепр', 'Запорожье', 'Черновцы', 'Чернигов', 'Житомир',
            'Сумы', 'Полтава', 'Винница', 'Ивано-Франковск', 'Хмельницкий', 'Кропивницкий', 'Ровно',
            'Тернополь', 'Луцк', 'Черкассы', 'Николаев', 'Херсон', 'Ужгород', 'Северодонецк'
        ],
        'en' => [
            'Kyiv', 'Lviv', 'Kharkiv', 'Odesa', 'Dnipro', 'Zaporizhzhia', 'Chernivtsi', 'Chernihiv', 'Zhytomyr',
            'Sumy', 'Poltava', 'Vinnytsia', 'Ivano-Frankivsk', 'Khmelnytskyi', 'Kropyvnytskyi', 'Rivne',
            'Ternopil', 'Lutsk', 'Cherkasy', 'Mykolaiv', 'Kherson', 'Uzhhorod', 'Severodonetsk'
        ],
    ];

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://router.project-osrm.org/',
            'timeout'  => 5.0,
        ]);
    }

    /**
     * Получить координаты по адресу с кэшированием и нормализацией
     */
    public function getCoordinatesByPlaceName(string $placeName, string $lang = 'uk'): ?array
    {
        try {
            // Ключ кэша для адреса и языка
            $cacheKey = 'coordinates_' . md5($placeName . '_' . $lang);

            return Cache::remember($cacheKey, now()->addHours(24), function () use ($placeName, $lang) {
                // Определяем дефолтный город
                switch ($lang) {
                    case 'en':
                        $defaultCity = 'Kyiv';
                        break;
                    case 'ru':
                        $defaultCity = 'Киев';
                        break;
                    default:
                        $defaultCity = 'Київ';
                        break;
                }

                // Фиксированные координаты всех областных центров Украины
                $fixedCoordinates = [
                    'uk' => [
                        'Київ' => ['latitude' => '50.4500336', 'longitude' => '30.5241361'],
                        'Львів' => ['latitude' => '49.839683', 'longitude' => '24.029717'],
                        'Харків' => ['latitude' => '50.000000', 'longitude' => '36.229444'],
                        'Одеса' => ['latitude' => '46.482526', 'longitude' => '30.723309'],
                        'Дніпро' => ['latitude' => '48.464717', 'longitude' => '35.046183'],
                        'Запоріжжя' => ['latitude' => '47.838800', 'longitude' => '35.139566'],
                        'Чернігів' => ['latitude' => '51.50551', 'longitude' => '31.28487'],
                        'Чернівці' => ['latitude' => '48.29150', 'longitude' => '25.94034'],
                        'Житомир' => ['latitude' => '50.25465', 'longitude' => '28.65870'],
                        'Суми' => ['latitude' => '50.90770', 'longitude' => '34.79814'],
                        'Полтава' => ['latitude' => '49.58963', 'longitude' => '34.55142'],
                        'Вінниця' => ['latitude' => '49.23308', 'longitude' => '28.46822'],
                        'Івано-Франківськ' => ['latitude' => '48.92150', 'longitude' => '24.70972'],
                        'Хмельницький' => ['latitude' => '49.42178', 'longitude' => '26.99654'],
                        'Кропивницький' => ['latitude' => '48.51394', 'longitude' => '32.25914'],
                        'Рівне' => ['latitude' => '50.61993', 'longitude' => '26.25160'],
                        'Тернопіль' => ['latitude' => '49.55352', 'longitude' => '25.59477'],
                        'Луцьк' => ['latitude' => '50.74723', 'longitude' => '25.32544'],
                        'Черкаси' => ['latitude' => '49.44442', 'longitude' => '32.05977'],
                        'Миколаїв' => ['latitude' => '46.97503', 'longitude' => '31.99458'],
                        'Херсон' => ['latitude' => '46.65599', 'longitude' => '32.61782'],
                        'Ужгород' => ['latitude' => '48.62080', 'longitude' => '22.28788'],
                        'Сєвєродонецьк' => ['latitude' => '48.94700', 'longitude' => '38.48465'],
                    ],
                    'ru' => [
                        'Киев' => ['latitude' => '50.4500336', 'longitude' => '30.5241361'],
                        'Львов' => ['latitude' => '49.839683', 'longitude' => '24.029717'],
                        'Харьков' => ['latitude' => '50.000000', 'longitude' => '36.229444'],
                        'Одесса' => ['latitude' => '46.482526', 'longitude' => '30.723309'],
                        'Днепр' => ['latitude' => '48.464717', 'longitude' => '35.046183'],
                        'Запорожье' => ['latitude' => '47.838800', 'longitude' => '35.139566'],
                        'Чернигов' => ['latitude' => '51.50551', 'longitude' => '31.28487'],
                        'Черновцы' => ['latitude' => '48.29150', 'longitude' => '25.94034'],
                        'Житомир' => ['latitude' => '50.25465', 'longitude' => '28.65870'],
                        'Сумы' => ['latitude' => '50.90770', 'longitude' => '34.79814'],
                        'Полтава' => ['latitude' => '49.58963', 'longitude' => '34.55142'],
                        'Винница' => ['latitude' => '49.23308', 'longitude' => '28.46822'],
                        'Ивано-Франковск' => ['latitude' => '48.92150', 'longitude' => '24.70972'],
                        'Хмельницкий' => ['latitude' => '49.42178', 'longitude' => '26.99654'],
                        'Кропивницкий' => ['latitude' => '48.51394', 'longitude' => '32.25914'],
                        'Ровно' => ['latitude' => '50.61993', 'longitude' => '26.25160'],
                        'Тернополь' => ['latitude' => '49.55352', 'longitude' => '25.59477'],
                        'Луцк' => ['latitude' => '50.74723', 'longitude' => '25.32544'],
                        'Черкассы' => ['latitude' => '49.44442', 'longitude' => '32.05977'],
                        'Николаев' => ['latitude' => '46.97503', 'longitude' => '31.99458'],
                        'Херсон' => ['latitude' => '46.65599', 'longitude' => '32.61782'],
                        'Ужгород' => ['latitude' => '48.62080', 'longitude' => '22.28788'],
                        'Северодонецк' => ['latitude' => '48.94700', 'longitude' => '38.48465'],
                    ],
                    'en' => [
                        'Kyiv' => ['latitude' => '50.4500336', 'longitude' => '30.5241361'],
                        'Lviv' => ['latitude' => '49.839683', 'longitude' => '24.029717'],
                        'Kharkiv' => ['latitude' => '50.000000', 'longitude' => '36.229444'],
                        'Odesa' => ['latitude' => '46.482526', 'longitude' => '30.723309'],
                        'Dnipro' => ['latitude' => '48.464717', 'longitude' => '35.046183'],
                        'Zaporizhzhia' => ['latitude' => '47.838800', 'longitude' => '35.139566'],
                        'Chernihiv' => ['latitude' => '51.50551', 'longitude' => '31.28487'],
                        'Chernivtsi' => ['latitude' => '48.29150', 'longitude' => '25.94034'],
                        'Zhytomyr' => ['latitude' => '50.25465', 'longitude' => '28.65870'],
                        'Sumy' => ['latitude' => '50.90770', 'longitude' => '34.79814'],
                        'Poltava' => ['latitude' => '49.58963', 'longitude' => '34.55142'],
                        'Vinnytsia' => ['latitude' => '49.23308', 'longitude' => '28.46822'],
                        'Ivano-Frankivsk' => ['latitude' => '48.92150', 'longitude' => '24.70972'],
                        'Khmelnytskyi' => ['latitude' => '49.42178', 'longitude' => '26.99654'],
                        'Kropyvnytskyi' => ['latitude' => '48.51394', 'longitude' => '32.25914'],
                        'Rivne' => ['latitude' => '50.61993', 'longitude' => '26.25160'],
                        'Ternopil' => ['latitude' => '49.55352', 'longitude' => '25.59477'],
                        'Lutsk' => ['latitude' => '50.74723', 'longitude' => '25.32544'],
                        'Cherkasy' => ['latitude' => '49.44442', 'longitude' => '32.05977'],
                        'Mykolaiv' => ['latitude' => '46.97503', 'longitude' => '31.99458'],
                        'Kherson' => ['latitude' => '46.65599', 'longitude' => '32.61782'],
                        'Uzhhorod' => ['latitude' => '48.62080', 'longitude' => '22.28788'],
                        'Severodonetsk' => ['latitude' => '48.94700', 'longitude' => '38.48465'],
                    ],
                ];

                // Нормализация названий городов
                $cityNormalizeMap = [
                    'uk' => [
                        '/\bКиева\b/ui' => 'Київ', '/\bКиєві\b/ui' => 'Київ',
                        '/\bОдессы\b/ui' => 'Одеса', '/\bЛьвова\b/ui' => 'Львів', '/\bХарькова\b/ui' => 'Харків',
                        '/\bДнепра\b/ui' => 'Дніпро', '/\bЗапорожья\b/ui' => 'Запоріжжя', '/\bВинницы\b/ui' => 'Вінниця',
                        '/\bНиколаева\b/ui' => 'Миколаїв', '/\bХерсона\b/ui' => 'Херсон', '/\bПолтавы\b/ui' => 'Полтава',
                        '/\bСуммы\b/ui' => 'Суми', '/\bЧернигова\b/ui' => 'Чернігів', '/\bРовно\b/ui' => 'Рівне',
                        '/\bХмельницкого\b/ui' => 'Хмельницький', '/\bИвано-Франковска\b/ui' => 'Івано-Франківськ',
                        '/\bЛуцка\b/ui' => 'Луцьк', '/\bЧеркасс\b/ui' => 'Черкаси', '/\bКировограда\b/ui' => 'Кропивницький',
                        '/\bКропивницкого\b/ui' => 'Кропивницький', '/\bУжгорода\b/ui' => 'Ужгород', '/\bЧерновцов\b/ui' => 'Чернівці',
                    ],
                    'ru' => [
                        '/\bКиева\b/ui' => 'Киев', '/\bКиеве\b/ui' => 'Киев',
                        '/\bОдессы\b/ui' => 'Одесса', '/\bЛьвова\b/ui' => 'Львов', '/\bХарькова\b/ui' => 'Харьков',
                        '/\bДнепра\b/ui' => 'Днепр', '/\bЗапорожья\b/ui' => 'Запорожье', '/\bВинницы\b/ui' => 'Винница',
                        '/\bНиколаева\b/ui' => 'Николаев', '/\bХерсона\b/ui' => 'Херсон', '/\bПолтавы\b/ui' => 'Полтава',
                        '/\bСуммы\b/ui' => 'Сумы', '/\bЧернигова\b/ui' => 'Чернигов', '/\bРовно\b/ui' => 'Ровно',
                        '/\bХмельницкого\b/ui' => 'Хмельницкий', '/\bИвано-Франковска\b/ui' => 'Ивано-Франковск',
                        '/\bЛуцка\b/ui' => 'Луцк', '/\bЧеркасс\b/ui' => 'Черкассы', '/\bКировограда\b/ui' => 'Кропивницкий',
                        '/\bКропивницкого\b/ui' => 'Кропивницкий', '/\bУжгорода\b/ui' => 'Ужгород', '/\bЧерновцов\b/ui' => 'Черновцы',
                    ],
                    'en' => [
                        '/\bKiev\b/ui' => 'Kyiv', '/\bOdessa\b/ui' => 'Odesa', '/\bLviv\b/ui' => 'Lviv',
                        '/\bKharkov\b/ui' => 'Kharkiv', '/\bDnipro\b/ui' => 'Dnipro', '/\bZaporozhye\b/ui' => 'Zaporizhzhia',
                        '/\bVinnytsia\b/ui' => 'Vinnytsia', '/\bMykolaiv\b/ui' => 'Mykolaiv', '/\bKherson\b/ui' => 'Kherson',
                        '/\bPoltava\b/ui' => 'Poltava', '/\bSumy\b/ui' => 'Sumy', '/\bChernihiv\b/ui' => 'Chernihiv',
                        '/\bRivne\b/ui' => 'Rivne', '/\bKhmelnytskyi\b/ui' => 'Khmelnytskyi', '/\bIvano-Frankivsk\b/ui' => 'Ivano-Frankivsk',
                        '/\bLutsk\b/ui' => 'Lutsk', '/\bCherkasy\b/ui' => 'Cherkasy', '/\bKropyvnytskyi\b/ui' => 'Kropyvnytskyi',
                        '/\bUzhhorod\b/ui' => 'Uzhhorod', '/\bChernivtsi\b/ui' => 'Chernivtsi',
                    ],
                ];

                // Нормализация названий городов в зависимости от языка
                $placeName = trim(preg_replace('/,+/', ',', $placeName));
                foreach ($cityNormalizeMap[$lang] as $pattern => $replacement) {
                    $placeName = preg_replace($pattern, $replacement, $placeName);
                }

                // Проверяем, является ли адрес только названием города
                $cityNames = array_keys($fixedCoordinates[$lang]);
                $isCityOnly = false;
                $cleanPlaceName = trim(preg_replace('/\s*,\s*/', '', $placeName));
                foreach ($cityNames as $city) {
                    if (mb_strtolower($cleanPlaceName) === mb_strtolower($city)) {
                        $isCityOnly = true;
                        break;
                    }
                }

                // Если адрес — только город, возвращаем фиксированные координаты
                if ($isCityOnly) {
                    foreach ($fixedCoordinates[$lang] as $city => $coords) {
                        if (mb_stripos($placeName, $city) !== false) {
                            Log::info('[OpenStreetMapHelper] Returning fixed coordinates for city', [
                                'city' => $city,
                                'coords' => $coords,
                            ]);
                            return $coords;
                        }
                    }
                }

                // Удаляем дублирующиеся названия города
                $cityPattern = implode('|', array_map('preg_quote', array_keys($fixedCoordinates[$lang])));
                $placeName = preg_replace("/\b($cityPattern)\b[,\s]*/iu", '', $placeName);
                $placeName = trim($defaultCity . ', ' . trim($placeName, " ,"), " ,");

                Log::info('[OpenStreetMapHelper] Normalized placeName for query', ['placeName' => $placeName]);

                // Запрос к Nominatim
                $client = new \GuzzleHttp\Client(['timeout' => 5]);
                $response = $client->get('https://nominatim.openstreetmap.org/search', [
                    'query' => [
                        'q' => $placeName,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'limit' => 1,
                        'accept-language' => $lang,
                        'countrycodes' => 'ua',
                    ],
                    'headers' => [
                        'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (!empty($data[0]['lon']) && !empty($data[0]['lat'])) {
                    $coords = [
                        'longitude' => $data[0]['lon'],
                        'latitude' => $data[0]['lat'],
                    ];
                    Log::info('[OpenStreetMapHelper] Coordinates found via Nominatim', [
                        'placeName' => $placeName,
                        'coords' => $coords,
                    ]);
                    return $coords;
                }

                Log::warning('[OpenStreetMapHelper] No coordinates found via Nominatim', ['placeName' => $placeName]);

                // Fallback через MapBox
                $mapBoxCacheKey = 'mapbox_coordinates_' . md5($placeName . '_' . $lang);
                return Cache::remember($mapBoxCacheKey, now()->addHours(24), function () use ($placeName, $lang) {
                    $mapBoxHelper = new MapBoxHelper();
                    $coords = $mapBoxHelper->getCoordinatesByPlaceName($placeName, $lang);
                    Log::info('[OpenStreetMapHelper] Fallback to MapBox', [
                        'placeName' => $placeName,
                        'coords' => $coords,
                    ]);
                    return $coords ?: null;
                });
            });
        } catch (\Exception $e) {
            Log::error('[OpenStreetMapHelper] Error fetching coordinates', [
                'placeName' => $placeName,
                'error' => $e->getMessage(),
            ]);

            // Fallback через MapBox
            $mapBoxCacheKey = 'mapbox_coordinates_' . md5($placeName . '_' . $lang);
            return Cache::remember($mapBoxCacheKey, now()->addHours(24), function () use ($placeName, $lang) {
                $mapBoxHelper = new MapBoxHelper();
                $coords = $mapBoxHelper->getCoordinatesByPlaceName($placeName, $lang);
                Log::info('[OpenStreetMapHelper] Fallback to MapBox after error', [
                    'placeName' => $placeName,
                    'coords' => $coords,
                ]);
                return $coords ?: null;
            });
        }
    }


    /**
     * Получить расстояние через OSRM, fallback MapBox
     */
    public function getRouteDistance(float $startLat, float $startLon, float $endLat, float $endLon): ?float
    {
        try {
            $response = $this->client->get("route/v1/driving/{$startLon},{$startLat};{$endLon},{$endLat}", [
                'query' => ['overview' => 'false'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['routes'][0]['distance'])) {
                return $data['routes'][0]['distance'];
            }

            $mapBoxHelper = new MapBoxHelper();
            return $mapBoxHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon);

        } catch (RequestException $e) {
            Log::error('[OpenStreetMapHelper] ❌ Ошибка OSRM', ['error' => $e->getMessage()]);
            $mapBoxHelper = new MapBoxHelper();
            return $mapBoxHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon);
        }
    }
}
