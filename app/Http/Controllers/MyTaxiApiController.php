<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use App\Models\CityTariff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MyTaxiApiController extends Controller
{
    public function costMyApiTaxi(
        $parameter,
        $city,
        $application,
        $email
    ): array
    {
        Log::info('Начало расчета стоимости такси', ['city' => $city]);

        // Быстрая проверка маршрута
        if (!isset($parameter['route']) || count($parameter['route']) < 2) {
            Log::warning('Невалидный маршрут', ['route' => $parameter['route'] ?? null]);
            return $this->buildErrorResponse('Маршрут не указан или недостаточно точек');
        }

        $route = $parameter['route'];

        // Извлекаем координаты одной операцией
        $startPoint = $route[0];
        $endPoint = $route[1];

        $startLat = $startPoint['lat'] ?? null;
        $startLng = $startPoint['lng'] ?? null;
        $endLat = $endPoint['lat'] ?? null;
        $endLng = $endPoint['lng'] ?? null;

        // Проверка координат
        if (!$this->validateCoordinates($startLat, $startLng, $endLat, $endLng)) {
            return $this->buildErrorResponse('Не все координаты маршрута указаны');
        }

        // Получаем расстояние (может быть 0 если точки совпадают)
        $routeDistanceKm = $this->calculateRouteDistance($startLat, $startLng, $endLat, $endLng);

        // distance может быть 0 - это нормально (точки совпадают)
        if ($routeDistanceKm < 0) {
            return $this->buildErrorResponse('Не удалось рассчитать расстояние маршрута');
        }

        // Рассчитываем стоимость (расстояние может быть 0)
        $price = $this->calculatePrice($city, $routeDistanceKm);
        if ($price === null) {
            return $this->buildErrorResponse('Не удалось рассчитать стоимость поездки');
        }

        // Формируем успешный ответ
        return $this->buildSuccessResponse($price, $startLat, $startLng, $endLat, $endLng,  $application,
            $email);
    }

    /**
     * Валидация координат
     */
    private function validateCoordinates($startLat, $startLng, $endLat, $endLng): bool
    {
        $isValid = $startLat && $startLng && $endLat && $endLng;

        if (!$isValid) {
            Log::warning('Невалидные координаты', [
                'start_lat' => $startLat,
                'start_lng' => $startLng,
                'end_lat' => $endLat,
                'end_lng' => $endLng
            ]);
        }

        return $isValid;
    }

    /**
     * Расчет расстояния маршрута
     */
    private function calculateRouteDistance($startLat, $startLng, $endLat, $endLng): float
    {
        try {
            Log::info('Расчет расстояния через OSRM', [
                'start' => [$startLat, $startLng],
                'end' => [$endLat, $endLng]
            ]);

            // Проверяем, совпадают ли точки
            if ($this->pointsAreEqual($startLat, $startLng, $endLat, $endLng)) {
                Log::info('Начальная и конечная точки совпадают, расстояние = 0');
                return 0;
            }

            $osrmHelper = new OpenStreetMapHelper();
            $distanceMeters = $osrmHelper->getRouteDistance(
                (float) $startLat,
                (float) $startLng,
                (float) $endLat,
                (float) $endLng
            );

            Log::debug('Результат OSRM', ['distance_meters' => $distanceMeters]);

            if (!$distanceMeters || $distanceMeters <= 0) {
                Log::warning('OSRM вернул некорректное расстояние');
                return 0;
            }

            $distanceKm = round($distanceMeters / 1000, 2);
            Log::info('Рассчитанное расстояние', ['kilometers' => $distanceKm]);

            return $distanceKm;

        } catch (\Exception $e) {
            Log::error('Ошибка расчета расстояния', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Проверка, совпадают ли начальная и конечная точки
     */
    private function pointsAreEqual($startLat, $startLng, $endLat, $endLng): bool
    {
        $areEqual = (float) $startLat === (float) $endLat &&
            (float) $startLng === (float) $endLng;

        if ($areEqual) {
            Log::info('Точки маршрута совпадают', [
                'start_lat' => $startLat,
                'start_lng' => $startLng,
                'end_lat' => $endLat,
                'end_lng' => $endLng
            ]);
        }

        return $areEqual;
    }
    /**
     * Расчет стоимости через CityTariffController
     */
    private function calculatePrice(string $city, float $distance): ?float
    {
        try {
            Log::info('Расчет стоимости', ['city' => $city, 'distance_km' => $distance]);

            $tariffController = new CityTariffController();
            $request = new Request(['distance' => $distance]);

            $priceResponse = $tariffController->calculatePrice($request, $city);
            $responseData = $priceResponse->getData();

            if (!$responseData->success) {
                Log::warning('Ошибка расчета стоимости', [
                    'city' => $city,
                    'response' => $responseData
                ]);
                return null;
            }

            $price = $responseData->data->price;
            Log::info('Стоимость рассчитана', ['price' => $price]);

            return $price;

        } catch (\Exception $e) {
            Log::error('Ошибка расчета стоимости', [
                'city' => $city,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Формирование успешного ответа
     */
    private function buildSuccessResponse(
        float $price,
        string $startLat,
        string $startLng,
        string $endLat,
        string $endLng,
        $application,
        $email
    ): array
    {
        $response = [
            'order_cost' => (string) $price,
            'from_lat' => $startLat,
            'from_lng' => $startLng,
            'lat' => $endLat,
            'lng' => $endLng,
            'dispatching_order_uid' => $this->generateOrderUid(),
            'currency' => 'грн',
            'routeto' => 'Точка на карте',
            'to_number' => ' ',
            'routefrom' => $startLat,  // В примере это координата, а не название
            'routefromnumber' => ' '
        ];
        (new PusherController)->sentCostAppEmail($price, $application, $email);

        Log::info('Успешный ответ сформирован', [
            'price' => $price,
            'order_uid' => $response['dispatching_order_uid']
        ]);

        return $response;
    }

    /**
     * Формирование ошибочного ответа
     */
    private function buildErrorResponse(string $message): array
    {
        Log::warning('Формирование ошибочного ответа', ['message' => $message]);

        return [
            'order_cost' => "0",
            'Message' => $message,
        ];
    }

    /**
     * Генерация уникального идентификатора заказа
     */
    private function generateOrderUid(): string
    {
        return md5(time() . bin2hex(random_bytes(8)) . uniqid('', true));
    }


    public function orderMyApiTaxi () {

    }
}
