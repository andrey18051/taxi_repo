<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use App\Jobs\CheckAndCancelOrderJob;
use App\Jobs\SimplePollStatusJob;
use App\Models\Orderweb;
use App\Models\WfpInvoice;
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
        $payment_type = $parameter['payment_type'] ?? 0;

        // Проверка координат
        if (!$this->validateCoordinates($startLat, $startLng, $endLat, $endLng)) {
            return $this->buildErrorResponse('Не все координаты маршрута указаны');
        }

        // Создаем ключ для кеширования на основе координат, города и типа оплаты
        $cacheKey = "taxi_cost:" . md5("{$city}:{$startLat}:{$startLng}:{$endLat}:{$endLng}:{$payment_type}");
        $cacheDuration = 24 * 60; // сутки

        // Пробуем получить результат из кеша
        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult !== null) {
            // Проверяем, что кешированный результат - это массив
            if (is_array($cachedResult)) {
                Log::info('Используем кешированную стоимость такси', [
                    'city' => $city,
                    'cache_key' => $cacheKey
                ]);

                // Отправляем email с кешированной стоимостью
                if (isset($cachedResult['order_cost'])) {
                    (new PusherController)->sentCostAppEmail($cachedResult['order_cost'], $application, $email);
                }

                // Возвращаем кешированный результат
                return $cachedResult;
            } else {
                // Если в кеше не массив, очищаем и продолжаем расчет
                Log::warning('Некорректный формат кешированных данных', [
                    'type' => gettype($cachedResult),
                    'cache_key' => $cacheKey
                ]);
                Cache::forget($cacheKey);
            }
        }

        // Кешируем расчет расстояния
        $distanceCacheKey = "route_distance:" . md5("{$startLat}:{$startLng}:{$endLat}:{$endLng}");
        $routeDistanceKm = Cache::remember($distanceCacheKey, 3600, function() use ($startLat, $startLng, $endLat, $endLng) {
            return $this->calculateRouteDistance($startLat, $startLng, $endLat, $endLng);
        });

        // distance может быть 0 - это нормально (точки совпадают)
        if ($routeDistanceKm < 0) {
            return $this->buildErrorResponse('Не удалось рассчитать расстояние маршрута');
        }

        // Рассчитываем базовую стоимость
        $basePrice = $this->calculatePrice($city, $routeDistanceKm, $payment_type);
        if ($basePrice === null) {
            return $this->buildErrorResponse('Не удалось рассчитать стоимость поездки');
        }

        // Применяем наценку 10% для безналичной оплаты
        $finalPrice = $basePrice;
        if ($payment_type != 0) {
            $finalPrice = $basePrice * 1.1;
            Log::info('Применена наценка для безналичной оплаты', [
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'payment_type' => $payment_type
            ]);
        }

        // Формируем успешный ответ
        $result = $this->buildSuccessResponse($finalPrice, $startLat, $startLng, $endLat, $endLng, $application, $email);

        // Добавляем флаг кеширования
        $result['cached'] = false;
        $result['payment_type'] = $payment_type;
        $result['distance_km'] = $routeDistanceKm;
        $result['base_price'] = $basePrice;

        // Отправляем email с рассчитанной стоимостью
        (new PusherController)->sentCostAppEmail($result['order_cost'], $application, $email);

        // Кешируем финальный результат
        Cache::put($cacheKey, $result, $cacheDuration);

        Log::info('Стоимость такси рассчитана и закеширована', [
            'city' => $city,
            'distance_km' => $routeDistanceKm,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'payment_type' => $payment_type,
            'cache_duration' => $cacheDuration,
            'cache_key' => $cacheKey
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
    private function calculatePrice(string $city, float $distance, $payment_type): ?float
    {
        try {
            // Создаем ключ для кеширования
            $cacheKey = "tariff_price:{$city}:{$payment_type}" . round($distance, 2);
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
        $email,
        $wfpInvoice,
        $city
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
            "comment_info" => $parameter['comment_info'] ?? null,
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
            $order_id = (new UniversalAndroidFunctionController)->saveOrder($params, $identificationId);
            Log::info('✅ Заказ успешно сохранен в базу');

            if($wfpInvoice != "*") {
                $orderReference = $wfpInvoice;
                $amount = $clientCost;
                $productName = "Інша допоміжна діяльність у сфері транспорту";
                $clientEmail = $params['email'];
                $clientPhone = $params["user_phone"];
                $pay_system = $params['pay_system'];

                (new UniversalAndroidFunctionController)->orderIdMemoryToken($orderReference, $order_id, $pay_system);
                (new WfpController)->chargeActiveToken(
                    $application,
                    $city,
                    $orderReference,
                    $amount,
                    $productName,
                    $clientEmail,
                    $clientPhone
                );

                Log::debug("🔍 Поиск информации о транзакции в таблице WfpInvoice");

                // Первый запуск - без пятого параметра (по умолчанию 0)

                SimplePollStatusJob::dispatch(
                    $orderReference,
                    $dispatching_order_uid,
                    $application,
                    $email
                )->onQueue('high');

                CheckAndCancelOrderJob::dispatch(
                    $dispatching_order_uid,
                    $application,
                    $email
                )->onQueue('high')->delay(now()->addSeconds(50));
            }



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
            (new CentrifugoController)->sentUidAppEmailPayType(
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


    public function startAddCostMyApi(
        $order,
        $application,
        $email,
        $addCost,
        $response
    )  {
        Log::info('🟢 НАЧАЛО startAddCostMyApi', [
            'order_uid' => $order->dispatching_order_uid ?? 'unknown',
            'application' => $application,
            'email' => $email,
            'addCost' => $addCost,
            'order_id' => $order->id ?? 'unknown'
        ]);

        // Генерация нового UID для заказа
        $orderNew = $this->generateOrderUid();
        Log::debug('🔑 Сгенерирован новый UID', [
            'new_order_uid' => $orderNew,
            'old_order_uid' => $order->dispatching_order_uid ?? 'unknown'
        ]);

        // Отправка email уведомления
        Log::info('📧 Отправка email уведомления...');
        try {
            (new PusherController)->sentUidAppEmailPayType(
                $orderNew,
                $application,
                $email,
                "nal_payment"
            );
            (new CentrifugoController)->sentUidAppEmailPayType(
                $orderNew,
                $application,
                $email,
                "nal_payment"
            );
            Log::info('✅ Уведомление отправлено успешно');
        } catch (\Exception $e) {
            Log::error('❌ Ошибка отправки уведомления', [
                'error' => $e->getMessage(),
                'new_uid' => $orderNew
            ]);
        }

        Log::debug("📝 Создан новый заказ с UID: " . $orderNew);

        $order_old_uid = $order->dispatching_order_uid;
        $order_new_uid = $orderNew;

        Log::debug('🔄 Подготовка к замене UID', [
            'old_uid' => $order_old_uid,
            'new_uid' => $order_new_uid
        ]);

        // Сохранение в истории изменений
        try {
            (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);
            Log::info('✅ История изменений UID сохранена');
            // Обновление WfpInvoice
            $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $order_old_uid)->get();
            if ($wfpInvoices->isNotEmpty()) {
                foreach ($wfpInvoices as $wfpInvoice) {
                    $wfpInvoice->dispatching_order_uid = $order_new_uid;
                    $wfpInvoice->save();
                    Log::info("Обновлен WfpInvoice с dispatching_order_uid='$order_new_uid'.");
                }
            } else {
                Log::info("WfpInvoice для dispatching_order_uid='$order_old_uid' не найдены.");
            }
        } catch (\Exception $e) {
            Log::error('❌ Ошибка сохранения истории изменений UID', [
                'error' => $e->getMessage(),
                'old_uid' => $order_old_uid,
                'new_uid' => $order_new_uid
            ]);
        }

        // Обновление заказа с новыми данными
        Log::debug('🔄 Обновление заказа с новым UID и расчетами стоимости');

        $currentWebCost = $order->client_cost;
        $currentAttempt20 = $order->attempt_20;
        $newWebCost = $currentWebCost + (int) $currentAttempt20 + (int)$addCost;

        Log::debug('💰 Расчет стоимости', [
            'current_web_cost' => $currentWebCost,
            'current_attempt_20' => $currentAttempt20,
            'new_add_cost' => $addCost,
            'total_new_web_cost' => $newWebCost
        ]);

        $order->dispatching_order_uid = $order_new_uid;
        $order->auto = null;
        $order->web_cost = $newWebCost;
        $order->closeReason = "100";
        $order->closeReasonI = "0";
        $order->attempt_20 += $addCost;

        Log::debug('📋 Данные для сохранения', [
            'new_uid' => $order_new_uid,
            'auto' => 'null',
            'web_cost' => $newWebCost,
            'closeReason' => '-1',
            'closeReasonI' => '0',
            'new_attempt_20' => $order->attempt_20
        ]);

        $order->save();
        Log::info("✅ Заказ обновлен с новым UID: " . $order_new_uid);

        // Запись в Firestore
        if ($order->route_undefined == "0") {
            Log::debug('🔥 Проверка условий для Firestore', [
                'pay_system' => $order->pay_system,
                'route_undefined' => $order->route_undefined,
                'meets_conditions' => ($order->pay_system == "nal_payment" && $order->route_undefined == "0")
            ]);

            try {
                $controller = new FCMController();

                Log::debug('🔥 Начало операций с Firestore', [
                    'old_uid' => $order_old_uid,
                    'new_uid' => $order_new_uid
                ]);

                // 1. Удаление старого документа из основного Firestore
                Log::debug('🗑️ Удаление старого документа из Firestore...');
                $controller->deleteDocumentFromFirestore($order_old_uid);
                Log::info('✅ Старый документ удален из Firestore', ['uid' => $order_old_uid]);

                // 2. Удаление из коллекции отмененных заказов
                Log::debug('🗑️ Удаление из коллекции отмененных заказов...');
                $controller->deleteDocumentFromFirestoreOrdersTakingCancel($order_old_uid);
                Log::info('✅ Удален из коллекции отмененных заказов', ['uid' => $order_old_uid]);

                // 3. Удаление из секторного Firestore
                Log::debug('🗑️ Удаление из секторного Firestore...');
                $controller->deleteDocumentFromSectorFirestore($order_old_uid);
                Log::info('✅ Удален из секторного Firestore', ['uid' => $order_old_uid]);

                // 4. Запись в историю как отмененного
                Log::debug('📝 Запись в историю как отмененного...');
                $controller->writeDocumentToHistoryFirestore($order_old_uid, "cancelled");
                Log::info('✅ Запись в историю выполнена', [
                    'uid' => $order_old_uid,
                    'status' => 'cancelled'
                ]);

                // 5. Создание нового документа с новым UID
                Log::debug('📄 Создание нового документа с новым UID...');
                $controller->writeDocumentToFirestore($order_new_uid);
                Log::info('✅ Новый документ создан в Firestore', ['uid' => $order_new_uid]);

                Log::info('🎯 Все операции Firestore выполнены успешно', [
                    'old_uid' => $order_old_uid,
                    'new_uid' => $order_new_uid
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Ошибка операций с Firestore', [
                    'error' => $e->getMessage(),
                    'old_uid' => $order_old_uid,
                    'new_uid' => $order_new_uid,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::debug('⏭️ Запись в Firestore пропущена - условия не выполнены', [
                'pay_system' => $order->pay_system,
                'route_undefined' => $order->route_undefined
            ]);
        }

        // Отправка сообщения о восстановлении машины
        Log::info('🚗 Отправка сообщения о восстановлении машины...');
        try {
            (new MessageSentController())->sentCarRestoreOrderAfterAddCost($order);
            Log::info("✅ Сообщение о восстановлении заказа отправлено.");
        } catch (\Exception $e) {
            Log::error('❌ Ошибка отправки сообщения о восстановлении заказа', [
                'error' => $e->getMessage()
            ]);
        }

        Log::info('🎯 ЗАВЕРШЕНИЕ startAddCostMyApi - УСПЕХ', [
            'old_uid' => $order_old_uid,
            'new_uid' => $order_new_uid,
            'total_cost' => $order->web_cost,
            'added_cost' => $addCost,
            'pay_system' => $order->pay_system
        ]);
        $response =  [
            'uid' => $order_new_uid,
            'web_cost' => $order->web_cost,
            'routefrom' => $order->routefrom,
            'startLat' => $order->startLat,
            'startLan' => $order->startLan,
            'routeto' => $order->routeto,
            'to_lat' => $order->to_lat,
            'to_lng' => $order->to_lng,
            'pay_system' => $order->pay_system,
            "transactionStatus" => "WaitingAuthComplete"
        ];
        if (is_object($response) && method_exists($response, 'body')) {
            Log::debug("purchase startAddCostMyApi: ", ['response' => $response->body()]);
        } else {
            Log::debug("purchase startAddCostMyApi: ", ['response' => $response]);
        }
        return response()->json($response, 200);
    }
}
