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
            'timeout'  => 10.0,
        ]);
    }

    /**
     * Получить расстояние между двумя точками через MapBox Directions API.
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
            $response = $this->client->get("directions/v5/mapbox/driving/{$startLon},{$startLat};{$endLon},{$endLat}", [
                'query' => [
                    'access_token' => $this->accessToken,
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
            Log::error("Error fetching route from MapBox: " . $e->getMessage());
            return null;
        }
    }
}


//Основные отличия от реализации с OSRM:
//1. Вместо `https://router.project-osrm.org/` используется `https://api.mapbox.com/`.
//2. Вместо `route/v1/driving/` используется `directions/v5/mapbox/driving/`.
//3. Требуется передача `access_token` в качестве параметра запроса.
//
//Остальная логика аналогична: функция `getRouteDistance` принимает координаты начальной и конечной точки, делает запрос к MapBox Directions API, получает расстояние маршрута и возвращает его. В случае ошибки возвращается `null`.
