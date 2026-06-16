<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenStreetMapHelper
{
    private $client;
    private $mapBoxHelper;

    // Только областные центры Украины
    private $fixedCoordinates = [
        'uk' => [
            'Київ' => ['latitude' => 50.4500336, 'longitude' => 30.5241361],
            'Львів' => ['latitude' => 49.839683, 'longitude' => 24.029717],
            'Харків' => ['latitude' => 49.993500, 'longitude' => 36.230376],
            'Одеса' => ['latitude' => 46.482526, 'longitude' => 30.723309],
            'Дніпро' => ['latitude' => 48.464717, 'longitude' => 35.046183],
            'Запоріжжя' => ['latitude' => 47.838800, 'longitude' => 35.139566],
            'Чернівці' => ['latitude' => 48.291500, 'longitude' => 25.940340],
            'Чернігів' => ['latitude' => 51.505510, 'longitude' => 31.284870],
            'Житомир' => ['latitude' => 50.254650, 'longitude' => 28.658700],
            'Суми' => ['latitude' => 50.907700, 'longitude' => 34.798140],
            'Полтава' => ['latitude' => 49.589630, 'longitude' => 34.551420],
            'Вінниця' => ['latitude' => 49.233080, 'longitude' => 28.468220],
            'Івано-Франківськ' => ['latitude' => 48.921500, 'longitude' => 24.709720],
            'Хмельницький' => ['latitude' => 49.421780, 'longitude' => 26.996540],
            'Кропивницький' => ['latitude' => 48.513940, 'longitude' => 32.259140],
            'Рівне' => ['latitude' => 50.619930, 'longitude' => 26.251600],
            'Тернопіль' => ['latitude' => 49.553520, 'longitude' => 25.594770],
            'Луцьк' => ['latitude' => 50.747230, 'longitude' => 25.325440],
            'Черкаси' => ['latitude' => 49.444420, 'longitude' => 32.059770],
            'Миколаїв' => ['latitude' => 46.975030, 'longitude' => 31.994580],
            'Херсон' => ['latitude' => 46.655990, 'longitude' => 32.617820],
            'Ужгород' => ['latitude' => 48.620800, 'longitude' => 22.287880],
        ],
        'ru' => [
            'Киев' => ['latitude' => 50.4500336, 'longitude' => 30.5241361],
            'Львов' => ['latitude' => 49.839683, 'longitude' => 24.029717],
            'Харьков' => ['latitude' => 49.993500, 'longitude' => 36.230376],
            'Одесса' => ['latitude' => 46.482526, 'longitude' => 30.723309],
            'Днепр' => ['latitude' => 48.464717, 'longitude' => 35.046183],
            'Запорожье' => ['latitude' => 47.838800, 'longitude' => 35.139566],
            'Черновцы' => ['latitude' => 48.291500, 'longitude' => 25.940340],
            'Чернигов' => ['latitude' => 51.505510, 'longitude' => 31.284870],
            'Житомир' => ['latitude' => 50.254650, 'longitude' => 28.658700],
            'Сумы' => ['latitude' => 50.907700, 'longitude' => 34.798140],
            'Полтава' => ['latitude' => 49.589630, 'longitude' => 34.551420],
            'Винница' => ['latitude' => 49.233080, 'longitude' => 28.468220],
            'Ивано-Франковск' => ['latitude' => 48.921500, 'longitude' => 24.709720],
            'Хмельницкий' => ['latitude' => 49.421780, 'longitude' => 26.996540],
            'Кропивницкий' => ['latitude' => 48.513940, 'longitude' => 32.259140],
            'Ровно' => ['latitude' => 50.619930, 'longitude' => 26.251600],
            'Тернополь' => ['latitude' => 49.553520, 'longitude' => 25.594770],
            'Луцк' => ['latitude' => 50.747230, 'longitude' => 25.325440],
            'Черкассы' => ['latitude' => 49.444420, 'longitude' => 32.059770],
            'Николаев' => ['latitude' => 46.975030, 'longitude' => 31.994580],
            'Херсон' => ['latitude' => 46.655990, 'longitude' => 32.617820],
            'Ужгород' => ['latitude' => 48.620800, 'longitude' => 22.287880],
        ],
        'en' => [
            'Kyiv' => ['latitude' => 50.4500336, 'longitude' => 30.5241361],
            'Lviv' => ['latitude' => 49.839683, 'longitude' => 24.029717],
            'Kharkiv' => ['latitude' => 49.993500, 'longitude' => 36.230376],
            'Odesa' => ['latitude' => 46.482526, 'longitude' => 30.723309],
            'Dnipro' => ['latitude' => 48.464717, 'longitude' => 35.046183],
            'Zaporizhzhia' => ['latitude' => 47.838800, 'longitude' => 35.139566],
            'Chernivtsi' => ['latitude' => 48.291500, 'longitude' => 25.940340],
            'Chernihiv' => ['latitude' => 51.505510, 'longitude' => 31.284870],
            'Zhytomyr' => ['latitude' => 50.254650, 'longitude' => 28.658700],
            'Sumy' => ['latitude' => 50.907700, 'longitude' => 34.798140],
            'Poltava' => ['latitude' => 49.589630, 'longitude' => 34.551420],
            'Vinnytsia' => ['latitude' => 49.233080, 'longitude' => 28.468220],
            'Ivano-Frankivsk' => ['latitude' => 48.921500, 'longitude' => 24.709720],
            'Khmelnytskyi' => ['latitude' => 49.421780, 'longitude' => 26.996540],
            'Kropyvnytskyi' => ['latitude' => 48.513940, 'longitude' => 32.259140],
            'Rivne' => ['latitude' => 50.619930, 'longitude' => 26.251600],
            'Ternopil' => ['latitude' => 49.553520, 'longitude' => 25.594770],
            'Lutsk' => ['latitude' => 50.747230, 'longitude' => 25.325440],
            'Cherkasy' => ['latitude' => 49.444420, 'longitude' => 32.059770],
            'Mykolaiv' => ['latitude' => 46.975030, 'longitude' => 31.994580],
            'Kherson' => ['latitude' => 46.655990, 'longitude' => 32.617820],
            'Uzhhorod' => ['latitude' => 48.620800, 'longitude' => 22.287880],
        ],
    ];

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://router.project-osrm.org/',
            'timeout'  => 5.0,
        ]);
        $this->mapBoxHelper = new MapBoxHelper();
    }

    /**
     * Улучшенное геокодирование с использованием переданного города
     */
    public function getCoordinatesByPlaceName(
        string $placeName,
        string $lang,
        string $selectedCity
    ): ?array
    {
        $logContext = [
            'placeName' => $placeName,
            'lang' => $lang,
            'selectedCity' => $selectedCity,
            'timestamp' => now()->toISOString()
        ];

        Log::info('[OpenStreetMapHelper] 🔍 Начало геокодирования с городом', $logContext);

        try {
            $cacheKey = 'coordinates_v4_' . md5($placeName . '_' . $lang . '_' . $selectedCity);

            return Cache::remember($cacheKey, now()->addHours(24), function () use ($placeName, $lang, $selectedCity, $logContext) {
                Log::info('[OpenStreetMapHelper] 🗺️ Обработка запроса с городом (не из кэша)', $logContext);

                // 1. Сначала пытаемся найти точный адрес через Nominatim с указанным городом
                $nominatimCoords = $this->getNominatimCoordinates($placeName, $lang, $selectedCity);
                if ($nominatimCoords) {
                    Log::info('[OpenStreetMapHelper] ✅ Координаты найдены через Nominatim с городом', [
                        'address' => $placeName,
                        'city' => $selectedCity,
                        'coords' => $nominatimCoords
                    ]);
                    return $nominatimCoords;
                }

                Log::warning('[OpenStreetMapHelper] ⚠️ Nominatim не нашел координаты для адреса с городом', [
                    'address' => $placeName,
                    'city' => $selectedCity
                ]);

                // 2. Fallback на MapBox с указанным городом
                $mapboxCoords = $this->mapBoxHelper->getCoordinatesByPlaceName($placeName, $lang, $selectedCity);
                if ($mapboxCoords) {
                    Log::info('[OpenStreetMapHelper] ✅ Координаты найдены через MapBox с городом (fallback)', [
                        'address' => $placeName,
                        'city' => $selectedCity,
                        'coords' => $mapboxCoords
                    ]);
                    return $mapboxCoords;
                }

                Log::warning('[OpenStreetMapHelper] ⚠️ MapBox не нашел координаты с городом', [
                    'address' => $placeName,
                    'city' => $selectedCity
                ]);

                // 3. Используем координаты указанного города как fallback
                $cityCoords = $this->getCityCoordinates($selectedCity, $lang);
                if ($cityCoords) {
                    Log::info('[OpenStreetMapHelper] 🏙️ Использованы координаты указанного города (fallback)', [
                        'city' => $selectedCity,
                        'coords' => $cityCoords
                    ]);
                    return $cityCoords;
                }

                Log::error('[OpenStreetMapHelper] ❌ Все методы геокодирования failed', [
                    'address' => $placeName,
                    'city' => $selectedCity
                ]);

                return null;
            });

        } catch (\Exception $e) {
            Log::error('[OpenStreetMapHelper] 💥 Критическая ошибка при геокодировании', [
                'placeName' => $placeName,
                'selectedCity' => $selectedCity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Аварийный fallback на координаты указанного города
            return $this->getCityCoordinates($selectedCity, $lang);
        }
    }

    /**
     * Поиск через Nominatim с учетом города
     */
    private function getNominatimCoordinates(string $placeName, string $lang, ?string $city = null): ?array
    {
        try {
            $query = $placeName;

            // Если указан город, добавляем его к запросу для повышения точности
            if ($city && !empty(trim($city))) {
                $query = $placeName . ', ' . $city;
            }

            Log::debug('[OpenStreetMapHelper] 🗺️ Запрос к Nominatim с городом', [
                'address' => $placeName,
                'city' => $city,
                'full_query' => $query
            ]);

            $client = new Client(['timeout' => 8]);

            $response = $client->get('https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 5,
                    'accept-language' => $lang,
                    'countrycodes' => 'ua',
                    'bounded' => 1,
                    'viewbox' => '22.0,44.0,41.0,53.0', // Ограничение Украиной
                ],
                'headers' => [
                    'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data)) {
                return null;
            }

            // Выбираем лучший результат
            $bestResult = $this->selectBestNominatimResult($data, $query);

            if ($bestResult && !empty($bestResult['lon']) && !empty($bestResult['lat'])) {
                $coords = [
                    'longitude' => (float)$bestResult['lon'],
                    'latitude' => (float)$bestResult['lat'],
                ];

                // Валидация координат
                if ($this->validateUkrainianCoordinates($coords)) {
                    Log::debug('[OpenStreetMapHelper] 🎯 Выбран результат Nominatim с городом', [
                        'address' => $bestResult['display_name'] ?? $query,
                        'coords' => $coords,
                        'importance' => $bestResult['importance'] ?? 'unknown'
                    ]);
                    return $coords;
                }
            }

            return null;

        } catch (RequestException $e) {
            Log::error('[OpenStreetMapHelper] ❌ Ошибка Nominatim с городом', [
                'address' => $placeName,
                'city' => $city,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить координаты города из фиксированного списка
     */
    private function getCityCoordinates(string $city, string $lang): ?array
    {
        $cleanCity = trim($city);

        // Ищем город в фиксированных координатах
        foreach ($this->fixedCoordinates as $langCode => $cities) {
            foreach ($cities as $cityName => $coords) {
                if (mb_strtolower($cleanCity) === mb_strtolower($cityName)) {
                    Log::debug('[OpenStreetMapHelper] 🏙️ Найдены координаты города из фиксированного списка', [
                        'city' => $cityName,
                        'lang' => $langCode,
                        'coords' => $coords
                    ]);
                    return $coords;
                }
            }
        }

        Log::warning('[OpenStreetMapHelper] 🏙️ Город не найден в фиксированном списке', [
            'city' => $city,
            'lang' => $lang
        ]);

        return null;
    }

    /**
     * Выбор лучшего результата Nominatim
     */
    private function selectBestNominatimResult(array $results, string $query): ?array
    {
        if (empty($results)) {
            return null;
        }

        // Сортируем по importance (чем выше, тем лучше)
        usort($results, function ($a, $b) {
            return ($b['importance'] ?? 0) <=> ($a['importance'] ?? 0);
        });

        $bestResult = $results[0];
        $bestCoords = [
            'longitude' => (float)$bestResult['lon'],
            'latitude' => (float)$bestResult['lat'],
        ];

        // Проверяем что лучший результат в пределах Украины
        if ($this->validateUkrainianCoordinates($bestCoords)) {
            return $bestResult;
        }

        // Если лучший результат не в Украине, ищем первый валидный
        foreach ($results as $result) {
            $coords = [
                'longitude' => (float)$result['lon'],
                'latitude' => (float)$result['lat'],
            ];

            if ($this->validateUkrainianCoordinates($coords)) {
                Log::info('[OpenStreetMapHelper] 🔄 Выбран альтернативный результат в Украине', [
                    'original_best' => $bestResult['display_name'] ?? 'unknown',
                    'selected' => $result['display_name'] ?? 'unknown'
                ]);
                return $result;
            }
        }

        // Если ничего не найдено в Украине, возвращаем лучший результат
        return $bestResult;
    }

    /**
     * Валидация координат Украины
     */
    private function validateUkrainianCoordinates(array $coords): bool
    {
        // Границы Украины
        $minLat = 44.0;   // юг
        $maxLat = 53.0;   // север
        $minLon = 22.0;   // запад
        $maxLon = 41.0;   // восток

        $isValid = ($coords['latitude'] >= $minLat && $coords['latitude'] <= $maxLat &&
            $coords['longitude'] >= $minLon && $coords['longitude'] <= $maxLon);

        if (!$isValid) {
            Log::warning('[OpenStreetMapHelper] 🚫 Координаты вне пределов Украины', [
                'coords' => $coords,
                'bounds' => "Lat: $minLat-$maxLat, Lon: $minLon-$maxLon"
            ]);
        }

        return $isValid;
    }

    /**
     * Получить расстояние через OSRM (с кешированием + нормализацией координат)
     */
    public function getRouteDistance(float $startLat, float $startLon, float $endLat, float $endLon): ?float
    {
        // ---- 📌 НОРМАЛИЗАЦИЯ КООРДИНАТ ----
        // до 5 знаков после запятой (~1 метр)
        $startLat = round($startLat, 5);
        $startLon = round($startLon, 5);
        $endLat   = round($endLat, 5);
        $endLon   = round($endLon, 5);

        // ---- 📌 КЛЮЧ ДЛЯ КЕША ----
        $cacheKey = sprintf(
            'route_distance_%s_%s_%s_%s',
            $startLat,
            $startLon,
            $endLat,
            $endLon
        );

        // ---- 📌 Проверяем кеш ----
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            Log::info('[OpenStreetMapHelper] 🗄️ Расстояние взято из кеша', [
                'distance' => $cached,
                'distance_km' => round($cached / 1000, 2),
                'cache_key' => $cacheKey,
            ]);
            return $cached;
        }

        // ---- 📌 ЛОГИ ----
        $logContext = [
            'start' => [$startLat, $startLon],
            'end'   => [$endLat, $endLon],
            'cache_key' => $cacheKey
        ];

        Log::info('[OpenStreetMapHelper] 🚗 Расчет расстояния маршрута', $logContext);

        // ---- 📌 OSRM попытка ----
        try {
            $response = $this->client->get("route/v1/driving/{$startLon},{$startLat};{$endLon},{$endLat}", [
                'query' => ['overview' => 'false'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['routes'][0]['distance'])) {
                $distance = $data['routes'][0]['distance'];

                // Сохраняем в кеш на 90 дней
                Cache::put($cacheKey, $distance, now()->addDays(90));

                Log::info('[OpenStreetMapHelper] ✅ Расстояние найдено через OSRM', [
                    'distance' => $distance,
                    'distance_km' => round($distance / 1000, 2),
                    'cache_key' => $cacheKey,
                ]);

                return $distance;
            }

            Log::warning('[OpenStreetMapHelper] ⚠️ OSRM не вернул расстояние, пробуем MapBox');

        } catch (RequestException $e) {
            Log::error('[OpenStreetMapHelper] ❌ Ошибка OSRM', [
                'error' => $e->getMessage(),
                'context' => $logContext
            ]);
        }

        // ---- 📌 Fallback на MapBox ----
        $mapboxDistance = $this->mapBoxHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon);

        if ($mapboxDistance) {
            Cache::put($cacheKey, $mapboxDistance, now()->addDays(90));

            Log::info('[OpenStreetMapHelper] ✅ Расстояние найдено через MapBox (fallback)', [
                'distance' => $mapboxDistance,
                'distance_km' => round($mapboxDistance / 1000, 2),
                'cache_key' => $cacheKey,
            ]);
        } else {
            Log::error('[OpenStreetMapHelper] ❌ Все методы расчета расстояния failed', $logContext);
        }

        return $mapboxDistance;
    }

    /**
     * Сборка адреса в формате Visicom (как в AndroidPas*_Controller).
     */
    public static function buildAddressFromVisicomProperties(array $props, string $local): string
    {
        $buildingText = $local === 'ru' ? 'д.' : ($local === 'en' ? 'build.' : 'буд.');

        $streetType = trim((string) ($props['street_type'] ?? ''));
        $street = trim((string) ($props['street'] ?? ''));
        if ($streetType !== '' && $street !== '') {
            $streetLine = $streetType . ' ' . $street;
        } elseif ($street !== '') {
            $streetLine = $street;
        } else {
            $streetLine = $streetType;
        }

        return $streetLine
            . ', ' . $buildingText . ($props['name'] ?? '')
            . ', ' . ($props['settlement_type'] ?? '')
            . ' ' . ($props['settlement'] ?? '');
    }

    /**
     * Тот же формат строки для fallback из Nominatim.
     */
    public static function buildAddressFromNominatim(array $address, ?string $osmName, string $local): ?string
    {
        $buildingText = $local === 'ru' ? 'д.' : ($local === 'en' ? 'build.' : 'буд.');

        $road = $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? $address['path'] ?? null;
        $house = $address['house_number'] ?? null;

        if ($road === null || $road === '') {
            $road = $address['building'] ?? $address['residential'] ?? $address['apartments']
                ?? $address['house'] ?? $address['tourism'] ?? $address['amenity']
                ?? $address['shop'] ?? $address['man_made'] ?? null;
            if (($road === null || $road === '') && $osmName !== null && $osmName !== '') {
                $road = $osmName;
            }
        }

        if ($road === null || $road === '') {
            return null;
        }

        $settlement = null;
        $settlementType = '';
        if (!empty($address['city'])) {
            $settlement = $address['city'];
            $settlementType = $local === 'ru' ? 'город ' : ($local === 'en' ? 'city ' : 'місто ');
        } elseif (!empty($address['town'])) {
            $settlement = $address['town'];
            $settlementType = $local === 'ru' ? 'г. ' : ($local === 'en' ? 'town ' : 'м. ');
        } elseif (!empty($address['village'])) {
            $settlement = $address['village'];
            $settlementType = $local === 'ru' ? 'селище ' : ($local === 'en' ? 'village ' : 'селище ');
        }

        $result = $road;
        if ($house !== null && $house !== '') {
            $result .= ', ' . $buildingText . $house;
        }
        if ($settlement !== null && $settlement !== '') {
            $result .= ', ' . $settlementType . $settlement;
        }

        return $result;
    }

}
