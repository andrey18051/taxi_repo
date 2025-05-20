<?php


namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class OpenStreetMapHelper
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://router.project-osrm.org/',
            'timeout'  => 5.0,
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
                ],
                'timeout' => 5.0,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Проверяем, что маршрут существует
            if (isset($data['routes'][0]['distance'])) {
                return $data['routes'][0]['distance'];
            }

            // Если OSRM не смог рассчитать маршрут за 5 секунд, пробуем через MapBox
            $mapBoxHelper = new MapBoxHelper();
            return $mapBoxHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon);
        } catch (RequestException $e) {
            // Логируем ошибку, если произошел сбой
            Log::error("11 Error fetching route from OSRM: " . $e->getMessage());

            // Пробуем через MapBox
            $mapBoxHelper = new MapBoxHelper();
            return $mapBoxHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon);
        }
    }
    public function getCoordinatesByPlaceName(string $placeName): ?array
    {
        try {
            // Отправляем запрос к Nominatim для получения координат по названию места
            $response = $this->client->get("https://nominatim.openstreetmap.org/search", [
                'query' => [
                    'q' => $placeName,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1, // Получаем только одно совпадение
                ],
                'timeout' => 5.0,
                'headers' => [
                    'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)', // Укажите название вашего приложения и контактный email
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Проверяем, что координаты были найдены
            if (!empty($data[0]['lon']) && !empty($data[0]['lat'])) {
                return [
                    'longitude' => $data[0]['lon'],
                    'latitude' => $data[0]['lat'],
                ];
            }
            // Если координаты не найдены
            $mapBoxHelper = new MapBoxHelper();
            return $mapBoxHelper->getCoordinatesByPlaceName($placeName);
        } catch (RequestException $e) {
            // Логируем ошибку, если произошел сбой
            Log::error("Error fetching coordinates from Nominatim: " . $e->getMessage());
            $mapBoxHelper = new MapBoxHelper();
            return $mapBoxHelper->getCoordinatesByPlaceName($placeName);
        }
    }

}

