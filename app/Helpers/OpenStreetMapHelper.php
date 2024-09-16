<?php


namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenStreetMapHelper
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://router.project-osrm.org/',
            'timeout'  => 10.0,
        ]);
    }

    /**
     * Получить расстояние между двумя точками через OSRM API.
     *
     * @param float $startLat Широта начальной точки
     * @param float $startLon Долгота начальной точки
     * @param float $endLat Широта конечной точки
     * @param float $endLon Долгота конечной точки
     * @return float|null Возвращает расстояние в метрах или null в случае ошибки
     */
    public function getRouteDistance(float $startLat, float $startLon, float $endLat, float $endLon): ?float
    {
        try {
            $response = $this->client->get("route/v1/driving/{$startLon},{$startLat};{$endLon},{$endLat}", [
                'query' => [
                    'overview' => 'false',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Проверяем, что маршрут существует
            if (isset($data['routes'][0]['distance'])) {
                return $data['routes'][0]['distance'];
            }

            return null;
        } catch (RequestException $e) {
            // Логируем ошибку, если произошел сбой
            \Log::error("Error fetching route from OSRM: " . $e->getMessage());
            return null;
        }
    }
}

