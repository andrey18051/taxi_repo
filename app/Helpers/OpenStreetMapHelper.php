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
     * Улучшенное геокодирование с правильными приоритетами
     */
    public function getCoordinatesByPlaceName(string $placeName, string $lang = 'uk'): ?array
    {
        $logContext = [
            'placeName' => $placeName,
            'lang' => $lang,
            'timestamp' => now()->toISOString()
        ];

        Log::info('[OpenStreetMapHelper] 🔍 Начало геокодирования', $logContext);

        try {
            $cacheKey = 'coordinates_v3_' . md5($placeName . '_' . $lang);

            return Cache::remember($cacheKey, now()->addHours(24), function () use ($placeName, $lang, $logContext) {
                Log::info('[OpenStreetMapHelper] 🗺️ Обработка запроса (не из кэша)', $logContext);

                // 1. Сначала пытаемся найти точный адрес через Nominatim
                $nominatimCoords = $this->getNominatimCoordinates($placeName, $lang);
                if ($nominatimCoords) {
                    Log::info('[OpenStreetMapHelper] ✅ Координаты найдены через Nominatim', [
                        'address' => $placeName,
                        'coords' => $nominatimCoords
                    ]);
                    return $nominatimCoords;
                }

                Log::warning('[OpenStreetMapHelper] ⚠️ Nominatim не нашел координаты для полного адреса', [
                    'address' => $placeName
                ]);

                // 2. Fallback на MapBox
                $mapboxCoords = $this->mapBoxHelper->getCoordinatesByPlaceName($placeName, $lang);
                if ($mapboxCoords) {
                    Log::info('[OpenStreetMapHelper] ✅ Координаты найдены через MapBox (fallback)', [
                        'address' => $placeName,
                        'coords' => $mapboxCoords
                    ]);
                    return $mapboxCoords;
                }

                Log::warning('[OpenStreetMapHelper] ⚠️ MapBox не нашел координаты', [
                    'address' => $placeName
                ]);

                // 3. Только если оба сервиса не нашли адрес, проверяем на город
                $cityMatch = $this->findCityOnlyMatch($placeName, $lang);
                if ($cityMatch) {
                    Log::info('[OpenStreetMapHelper] 🏙️ Использованы координаты города (fallback)', [
                        'city' => $cityMatch['city'],
                        'coords' => $cityMatch['coords']
                    ]);
                    return $cityMatch['coords'];
                }

                Log::error('[OpenStreetMapHelper] ❌ Все методы геокодирования failed', [
                    'address' => $placeName
                ]);

                return null;
            });

        } catch (\Exception $e) {
            Log::error('[OpenStreetMapHelper] 💥 Критическая ошибка при геокодировании', [
                'placeName' => $placeName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Аварийный fallback на поиск города
            return $this->findCityOnlyMatch($placeName, $lang)['coords'] ?? null;
        }
    }

    /**
     * Поиск через Nominatim с улучшенной обработкой
     */
    private function getNominatimCoordinates(string $placeName, string $lang): ?array
    {
        try {
            Log::debug('[OpenStreetMapHelper] 🗺️ Запрос к Nominatim', ['address' => $placeName]);

            $client = new Client(['timeout' => 8]);

            $response = $client->get('https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $placeName,
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
            $bestResult = $this->selectBestNominatimResult($data, $placeName);

            if ($bestResult && !empty($bestResult['lon']) && !empty($bestResult['lat'])) {
                $coords = [
                    'longitude' => (float)$bestResult['lon'],
                    'latitude' => (float)$bestResult['lat'],
                ];

                // Валидация координат
                if ($this->validateUkrainianCoordinates($coords)) {
                    Log::debug('[OpenStreetMapHelper] 🎯 Выбран результат Nominatim', [
                        'address' => $bestResult['display_name'] ?? $placeName,
                        'coords' => $coords,
                        'importance' => $bestResult['importance'] ?? 'unknown'
                    ]);
                    return $coords;
                }
            }

            return null;

        } catch (RequestException $e) {
            Log::error('[OpenStreetMapHelper] ❌ Ошибка Nominatim', [
                'address' => $placeName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
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
     * Поиск только города (без конкретного адреса)
     */
    private function findCityOnlyMatch(string $placeName, string $lang): ?array
    {
        $cleanPlaceName = mb_strtolower(trim($placeName));

        // Проверяем, является ли запрос только названием города
        foreach ($this->fixedCoordinates[$lang] as $city => $coords) {
            $cleanCity = mb_strtolower(trim($city));

            // Точное совпадение (только название города)
            if ($cleanPlaceName === $cleanCity) {
                return [
                    'city' => $city,
                    'coords' => $coords
                ];
            }

            // Совпадение с удалением лишних пробелов и запятых
            $pattern = '/^[\s,\-]*' . preg_quote($cleanCity, '/') . '[\s,\-]*$/iu';
            if (preg_match($pattern, $cleanPlaceName)) {
                return [
                    'city' => $city,
                    'coords' => $coords
                ];
            }
        }

        return null;
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
     * Получить расстояние через OSRM
     */
    public function getRouteDistance(float $startLat, float $startLon, float $endLat, float $endLon): ?float
    {
        $logContext = [
            'start' => [$startLat, $startLon],
            'end' => [$endLat, $endLon]
        ];

        Log::info('[OpenStreetMapHelper] 🚗 Расчет расстояния маршрута', $logContext);

        try {
            $response = $this->client->get("route/v1/driving/{$startLon},{$startLat};{$endLon},{$endLat}", [
                'query' => ['overview' => 'false'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['routes'][0]['distance'])) {
                $distance = $data['routes'][0]['distance'];
                Log::info('[OpenStreetMapHelper] ✅ Расстояние найдено через OSRM', [
                    'distance' => $distance,
                    'distance_km' => round($distance / 1000, 2)
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

        // Fallback на MapBox
        $mapboxDistance = $this->mapBoxHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon);
        if ($mapboxDistance) {
            Log::info('[OpenStreetMapHelper] ✅ Расстояние найдено через MapBox (fallback)', [
                'distance' => $mapboxDistance,
                'distance_km' => round($mapboxDistance / 1000, 2)
            ]);
        } else {
            Log::error('[OpenStreetMapHelper] ❌ Все методы расчета расстояния failed', $logContext);
        }

        return $mapboxDistance;
    }
}
