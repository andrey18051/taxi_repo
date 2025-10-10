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
     * Получить координаты по адресу через MapBox Geocoding API с fallback на Nominatim.
     */
    public function getCoordinatesByPlaceName(string $placeName, string $lang = 'uk'): ?array
    {
        try {
            // Запрос к MapBox Geocoding API
            $response = $this->client->get("geocoding/v5/mapbox.places/" . urlencode($placeName) . ".json", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'language' => $lang,
                    'limit' => 1,
                    'types' => 'address,place',
                ],
                'headers' => [
                    'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['features'][0]['center'])) {
                return [
                    'longitude' => $data['features'][0]['center'][0],
                    'latitude' => $data['features'][0]['center'][1],
                ];
            }

            // Fallback на Nominatim
            Log::info('[MapBoxHelper] Fallback to Nominatim', ['placeName' => $placeName]);
            return $this->getNominatimCoordinates($placeName, $lang);

        } catch (RequestException $e) {
            Log::error('[MapBoxHelper] Error fetching coordinates from MapBox', [
                'placeName' => $placeName,
                'error' => $e->getMessage(),
            ]);

            // Fallback на Nominatim
            return $this->getNominatimCoordinates($placeName, $lang);
        }
    }

    /**
     * Вспомогательный метод для получения координат через Nominatim
     */
    protected function getNominatimCoordinates(string $placeName, string $lang): ?array
    {
        try {
            $response = $this->client->get("https://nominatim.openstreetmap.org/search", [
                'query' => [
                    'q' => $placeName,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'accept-language' => $lang,
                ],
                'timeout' => 5.0,
                'headers' => [
                    'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data[0]['lon']) && !empty($data[0]['lat'])) {
                return [
                    'longitude' => $data[0]['lon'],
                    'latitude' => $data[0]['lat'],
                ];
            }

            return null;
        } catch (RequestException $e) {
            Log::error('[MapBoxHelper] Error fetching coordinates from Nominatim', [
                'placeName' => $placeName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
