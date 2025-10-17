<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MapBoxHelper
{
    private $client;
    private $accessToken;

    public function __construct()
    {
        $this->accessToken = config('app.keyMapbox');
        $this->client = new Client([
            'base_uri' => 'https://api.mapbox.com/',
            'timeout' => 10.0,
        ]);
    }

    /**
     * Получить расстояние между двумя точками через MapBox Directions API.
     */
    public function getRouteDistance(float $startLat, float $startLon, float $endLat, float $endLon): ?float
    {
        try {
            $response = $this->client->get("directions/v5/mapbox/driving/{$startLon},{$startLat};{$endLon},{$endLat}", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'overview' => 'false',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['routes'][0]['distance'])) {
                return $data['routes'][0]['distance'];
            }

            return null;
        } catch (RequestException $e) {
            Log::error("Error fetching route from MapBox: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить координаты по адресу через MapBox Geocoding API с учетом города
     */
    public function getCoordinatesByPlaceName(string $placeName, string $lang , ?string $city = null): ?array
    {
        try {
            $query = $placeName;

            // Если указан город, добавляем его к запросу для повышения точности
            if ($city && !empty(trim($city))) {
                $query = $placeName . ', ' . $city;
            }

            Log::debug('[MapBoxHelper] 🔍 Запрос к MapBox с городом', [
                'address' => $placeName,
                'city' => $city,
                'full_query' => $query
            ]);

            // Запрос к MapBox Geocoding API
            $response = $this->client->get("geocoding/v5/mapbox.places/" . urlencode($query) . ".json", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'language' => $lang,
                    'limit' => 1,
                    'types' => 'address,place',
                    'country' => 'ua', // Ограничение Украиной
                ],
                'headers' => [
                    'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['features'][0]['center'])) {
                $coords = [
                    'longitude' => $data['features'][0]['center'][0],
                    'latitude' => $data['features'][0]['center'][1],
                ];

                Log::debug('[MapBoxHelper] ✅ Координаты найдены через MapBox с городом', [
                    'address' => $query,
                    'coords' => $coords
                ]);
                return $coords;
            }

            // Fallback на Nominatim с городом
            Log::info('[MapBoxHelper] Fallback to Nominatim with city', [
                'placeName' => $placeName,
                'city' => $city
            ]);
            return $this->getNominatimCoordinates($placeName, $lang, $city);

        } catch (RequestException $e) {
            Log::error('[MapBoxHelper] Error fetching coordinates from MapBox', [
                'placeName' => $placeName,
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            // Fallback на Nominatim с городом
            return $this->getNominatimCoordinates($placeName, $lang, $city);
        }
    }

    /**
     * Вспомогательный метод для получения координат через Nominatim с учетом города
     */
    protected function getNominatimCoordinates(string $placeName, string $lang, ?string $city = null): ?array
    {
        try {
            $query = $placeName;

            // Если указан город, добавляем его к запросу
            if ($city && !empty(trim($city))) {
                $query = $placeName . ', ' . $city;
            }

            Log::debug('[MapBoxHelper] 🔍 Fallback к Nominatim с городом', [
                'address' => $placeName,
                'city' => $city,
                'full_query' => $query
            ]);

            $response = $this->client->get("https://nominatim.openstreetmap.org/search", [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'accept-language' => $lang,
                    'countrycodes' => 'ua',
                ],
                'timeout' => 5.0,
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

                Log::debug('[MapBoxHelper] ✅ Координаты найдены через Nominatim с городом', [
                    'address' => $query,
                    'coords' => $coords
                ]);
                return $coords;
            }

            return null;
        } catch (RequestException $e) {
            Log::error('[MapBoxHelper] Error fetching coordinates from Nominatim', [
                'placeName' => $placeName,
                'city' => $city,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
