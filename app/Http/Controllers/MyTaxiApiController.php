<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use App\Models\CityTariff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Создаем ключ для кеширования на основе координат и города
        $cacheKey = "taxi_cost:" . md5("{$city}:{$startLat}:{$startLng}:{$endLat}:{$endLng}");
        $cacheDuration = 24*60; // сутки

        // Пробуем получить результат из кеша используя фасад Cache
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult !== null) {
            Log::info('Используем кешированную стоимость такси', [
                'city' => $city,
                'cache_key' => $cacheKey
            ]);

            // Обновляем email в кешированном результате
            $cachedResult['cached'] = true;
            (new PusherController)->sentCostAppEmail($cachedResult['order_cost'], $application, $email);

            return $cachedResult;
        }

        // Кешируем расчет расстояния используя фасад Cache
        $distanceCacheKey = "route_distance:" . md5("{$startLat}:{$startLng}:{$endLat}:{$endLng}");
        $routeDistanceKm = Cache::remember($distanceCacheKey, 3600, function() use ($startLat, $startLng, $endLat, $endLng) {
            return $this->calculateRouteDistance($startLat, $startLng, $endLat, $endLng);
        });

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
        $result = $this->buildSuccessResponse($price, $startLat, $startLng, $endLat, $endLng, $application, $email);
        $result['cached'] = false;

        // Кешируем финальный результат используя фасад Cache
        Cache::put($cacheKey, $result, $cacheDuration);

        Log::info('Стоимость такси рассчитана и закеширована', [
            'city' => $city,
            'distance_km' => $routeDistanceKm,
            'price' => $price,
            'cache_duration' => $cacheDuration
        ]);

        return $result;
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
            // Создаем ключ для кеширования
            $cacheKey = "tariff_price:{$city}:" . round($distance, 2);
            $cacheDuration = 3600; // 1 час

            Log::info('Начало расчета стоимости тарифа', [
                'city' => $city,
                'distance_km' => $distance,
                'cache_key' => $cacheKey
            ]);

            // Пробуем получить из кеша
            $cachedPrice = Cache::get($cacheKey);
            if ($cachedPrice !== null) {
                Log::info('Использована кешированная стоимость тарифа', [
                    'city' => $city,
                    'distance_km' => $distance,
                    'price' => $cachedPrice,
                    'cache_key' => $cacheKey
                ]);
                return $cachedPrice;
            }

            Log::info('Расчет стоимости тарифа (кеш не найден)', [
                'city' => $city,
                'distance_km' => $distance
            ]);

            $tariffController = new CityTariffController();
            $request = new Request(['distance' => $distance]);

            $startTime = microtime(true);
            $priceResponse = $tariffController->calculatePrice($request, $city);
            $calculationTime = round((microtime(true) - $startTime) * 1000, 2); // время в ms

            $responseData = $priceResponse->getData();

            if (!$responseData->success) {
                Log::warning('Ошибка расчета стоимости тарифа', [
                    'city' => $city,
                    'distance_km' => $distance,
                    'response' => $responseData,
                    'calculation_time_ms' => $calculationTime
                ]);
                return null;
            }

            $price = $responseData->data->price;

            // Кешируем результат
            Cache::put($cacheKey, $price, $cacheDuration);

            Log::info('Стоимость тарифа рассчитана и закеширована', [
                'city' => $city,
                'distance_km' => $distance,
                'price' => $price,
                'calculation_time_ms' => $calculationTime,
                'cache_duration_seconds' => $cacheDuration,
                'cache_key' => $cacheKey
            ]);

            return $price;

        } catch (\Exception $e) {
            Log::error('Критическая ошибка расчета стоимости тарифа', [
                'city' => $city,
                'distance_km' => $distance,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'exception_trace' => $e->getTraceAsString()
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


    public function orderMyApiTaxi(
        $parameter,
        $clientCost,
        $application,
        $email
    ): array
    {
        Log::info('🟢 НАЧАЛО создания заказа такси', [
            'application' => $application,
            'email' => $email,
            'client_cost' => $clientCost,
            'required_time_received' => $parameter['required_time'] ?? 'NOT_SET'
        ]);

        $dispatching_order_uid = $this->generateOrderUid();
        Log::debug('Сгенерирован order_uid', ['dispatching_order_uid' => $dispatching_order_uid]);

        $route = $parameter['route'];
        Log::debug('Получен маршрут', ['route_points_count' => count($route)]);

        // Извлекаем координаты одной операцией
        $startPoint = $route[0];
        $endPoint = $route[1];

        $from = $startPoint['name'] ?? null;
        $to = $endPoint['name'] ?? null;

        $startLat = $startPoint['lat'] ?? null;
        $startLng = $startPoint['lng'] ?? null;
        $endLat = $endPoint['lat'] ?? null;
        $endLng = $endPoint['lng'] ?? null;

        Log::info('Координаты маршрута', [
            'start_lat' => $startLat,
            'start_lng' => $startLng,
            'end_lat' => $endLat,
            'end_lng' => $endLng,
            'from_address' => $from,
            'to_address' => $to
        ]);

        $identificationId = (new AndroidTestOSMController)->identificationId($application);
        Log::debug('Получен identificationId', ['identificationId' => $identificationId]);

        // Обработка required_time - преобразуем пустые значения в null
        $requiredTime = $parameter['required_time'] ?? null;

        // Если required_time пустой, невалидный или равен 'no_time', устанавливаем null
        if (empty($requiredTime) || $requiredTime === 'no_time' || $requiredTime === '') {
            $requiredTime = null;
            Log::debug('required_time установлен как NULL', ['original_value' => $parameter['required_time'] ?? 'NOT_SET']);
        } else {
            // Пытаемся преобразовать в корректный datetime формат
            try {
                $requiredTime = \Carbon\Carbon::parse($requiredTime)->format('Y-m-d H:i:s');
                Log::debug('required_time преобразован', [
                    'original' => $parameter['required_time'],
                    'converted' => $requiredTime
                ]);
            } catch (\Exception $e) {
                Log::warning('❌ Не удалось преобразовать required_time, устанавливаем NULL', [
                    'original_value' => $requiredTime,
                    'error' => $e->getMessage()
                ]);
                $requiredTime = null;
            }
        }

        // Обработка extra_charge_codes - преобразуем массив в строку
        $extraChargeCodes = $parameter['extra_charge_codes'] ?? null;
        if (is_array($extraChargeCodes)) {
            $extraChargeCodes = implode(',', $extraChargeCodes);
            Log::debug('Преобразовано extra_charge_codes', ['from' => 'array', 'to' => $extraChargeCodes]);
        }

        // Обработка других полей, которые могут быть массивами
        $addCost = $parameter['add_cost'] ?? 0;
        if (is_array($addCost)) {
            $addCost = implode(',', $addCost);
        }

        // Преобразуем булевы значения в числа для базы данных
        $wagon = $parameter['wagon'] ?? 0;
        if (is_bool($wagon)) {
            $wagon = $wagon ? 1 : 0;
        } elseif (is_array($wagon)) {
            $wagon = implode(',', $wagon);
        }

        $minibus = $parameter['minibus'] ?? 0;
        if (is_bool($minibus)) {
            $minibus = $minibus ? 1 : 0;
        } elseif (is_array($minibus)) {
            $minibus = implode(',', $minibus);
        }

        $premium = $parameter['premium'] ?? 0;
        if (is_bool($premium)) {
            $premium = $premium ? 1 : 0;
        } elseif (is_array($premium)) {
            $premium = implode(',', $premium);
        }

        $routeUndefined = $parameter['route_undefined'] ?? 0;
        if (is_bool($routeUndefined)) {
            $routeUndefined = $routeUndefined ? 1 : 0;
        } elseif (is_array($routeUndefined)) {
            $routeUndefined = implode(',', $routeUndefined);
        }

        // Подготовка параметров для сохранения заказа
        $params = [
            "user_full_name" => $parameter['user_full_name'] ?? null,
            "user_phone" => $parameter['user_phone'] ?? null,
            "email" => $email,
            "required_time" => $requiredTime, // Исправлено: теперь null вместо пустой строки
            "reservation" => $parameter['reservation'] ?? 0,
            "add_cost" => $addCost,
            "wagon" => $wagon,
            "minibus" => $minibus,
            "premium" => $premium,
            "flexible_tariff_name" => $parameter['flexible_tariff_name'] ?? null,
            "route_undefined" => $routeUndefined,
            "from" => $from,
            "from_number" => " ",
            "startLat" => $startLat,
            "startLan" => $startLng,
            "to" => $to,
            "to_number" => " ",
            "to_lat" => $endLat,
            "to_lng" => $endLng,
            "comment_info" => $parameter['user_full_name'] ?? null,
            "extra_charge_codes" => $extraChargeCodes,
            "taxiColumnId" => $parameter['taxiColumnId'] ?? 0,
            "payment_type" => $parameter['payment_type'] ?? 0,
            "pay_system" => $parameter['pay_system'] ?? 'nal_payment',
            "bonus_status" => ($parameter['pay_system'] ?? '') == "bonus_payment" ? 'hold' : '',
            "order_cost" => $clientCost,
            "clientCost" => $clientCost,
            "dispatching_order_uid" => $dispatching_order_uid,
            "closeReason" => '100',
            "server" => "my_server_api"
        ];

        Log::info('📋 Параметры заказа подготовлены', [
            'user_phone' => $params['user_phone'],
            'payment_type' => $params['payment_type'],
            'pay_system' => $params['pay_system'],
            'taxiColumnId' => $params['taxiColumnId'],
            'reservation' => $params['reservation'],
            'required_time' => $params['required_time'],
            'required_time_type' => gettype($params['required_time'])
        ]);

        try {
            // Сохраняем заказ
            Log::info('💾 Сохранение заказа в базу...');
            (new UniversalAndroidFunctionController)->saveOrder($params, $identificationId);
            Log::info('✅ Заказ успешно сохранен в базу');
        } catch (\Exception $e) {
            Log::error('❌ Ошибка сохранения заказа в базу', [
                'error' => $e->getMessage(),
                'order_uid' => $dispatching_order_uid,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'required_time_value' => $params['required_time'],
                'required_time_type' => gettype($params['required_time'])
            ]);
            throw $e;
        }

        try {
            // Отправляем email
            Log::info('📧 Отправка email уведомления...');
            (new PusherController)->sentUidAppEmailPayType(
                $dispatching_order_uid,
                $application,
                $email,
                $parameter["pay_system"] ?? null
            );
            Log::info('✅ Email уведомление отправлено');
        } catch (\Exception $e) {
            Log::warning('⚠️ Ошибка отправки email', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            // Не прерываем выполнение при ошибке email
        }

        // Формируем ответ для Android
        $response = [
            'from_lat' => (string) $startLat,
            'from_lng' => (string) $startLng,
            'lat' => (string) $endLat,
            'lng' => (string) $endLng,
            'dispatching_order_uid' => $dispatching_order_uid,
            'order_cost' => (string) $clientCost,
            'currency' => 'грн',
            'routefrom' => $from ?? 'Точка на карте',
            'routefromnumber' => ' ',
            'routeto' => $to ?? 'Точка на карте',
            'to_number' => ' ',
            'doubleOrder' => '0',
            'dispatching_order_uid_Double' => null,
            'Message' => null,
            'required_time' => $parameter['required_time'] ?? null,
            'flexible_tariff_name' => $parameter['flexible_tariff_name'] ?? null,
            'comment_info' => $parameter['user_full_name'] ?? null,
            'extra_charge_codes' => $extraChargeCodes
        ];

        Log::info('🎉 ЗАКАЗ УСПЕШНО СОЗДАН', [
            'dispatching_order_uid' => $dispatching_order_uid,
            'order_cost' => $clientCost,
            'application' => $application,
            'required_time_in_response' => $response['required_time']
        ]);

        return $response;
    }

    /**
     * Вспомогательная функция для гарантированного преобразования в строку
     */
    private function ensureString($value): string
    {
        if (is_array($value)) {
            Log::warning('🔄 Обнаружен массив, преобразование в строку', ['array' => $value]);
            return implode(',', $value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_null($value)) {
            return '';
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_object($value)) {
            Log::warning('🔄 Обнаружен объект, преобразование через json_encode', ['object' => get_class($value)]);
            return json_encode($value);
        }

        return (string) $value;
    }
}
