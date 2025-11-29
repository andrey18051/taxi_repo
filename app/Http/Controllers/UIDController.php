<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UIDController extends Controller
{

    public function closeReasonUIDStatus($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;

        try {
            $response = Http::withHeaders([
                "Authorization" => $autorization,
                "X-WO-API-APP-ID" => $identificationId,
            ])->timeout(5) // Устанавливаем таймаут в 10 секунд
            ->get($url);

            // Логируем тело ответа
            Log::debug("postRequestHTTP: " . $response->body());

            // Проверяем успешность ответа
            if ($response->successful() && $response->status() == 200) {
                $response_arr = json_decode($response, true);

                $order = Orderweb::where("dispatching_order_uid", $uid)->first();
                if ($order != null) {
                    $old_order_closeReason = $order->closeReason;

                    if ($old_order_closeReason == $response_arr["close_reason"]) {
                        $order->closeReasonI += 1;
                    } else {
                        $order->closeReason = $response_arr["close_reason"];

                        $order->closeReasonI = 1;
                    }
//                    $nameFrom = $response_arr['route_address_from']['name'] . " " . $response_arr['route_address_from']['number'];
//                    $nameTo = $response_arr['route_address_to']['name'] . " " . $response_arr['route_address_to']['number'];
//
//                    $order->routefrom = $nameFrom;
//                    $order->routeto = $nameTo;

                    if ($response_arr["order_car_info"] != null) {
                        $order->auto = $response_arr["order_car_info"];
                    }
                    $order->save();
                }
            } else {
                // Логируем ошибки в случае неудачного запроса
                Log::error("Request failed with status: " . $response->status());
                Log::error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            // Обработка исключений
            Log::error("Exception caught: " . $e->getMessage());
        }
    }

    public function closeReasonUIDStatusFirst($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            return $response_arr["close_reason"];
        }
//        return "-1";
    }

    public function closeReasonUIDStatusService($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);

            $orderweb_uid = Orderweb::where("dispatching_order_uid", $uid)->first();
            Log::debug("closeReasonUIDStatusService uid $uid");
            $orderweb_uid->auto = $response_arr["order_car_info"];

            $orderweb_uid->closeReason = $response_arr["close_reason"];
            $orderweb_uid->save();

            return $response_arr["close_reason"];
        }
    }

    public function closeReasonUIDStatusFirstWfp($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            return json_decode($response, true);
        }
        return "-1";
    }

    public function UIDStatusShow($user_full_name)
    {
        $order = Orderweb:: where("user_full_name", $user_full_name)
            -> where("closeReason", "!=", null)
            -> where("server", "!=", null)
            -> where("comment", "!=", null)->get();

        $response = null;
        if (!$order->isEmpty()) {
            self::UIDStatusReview($order);
            $orderUpdate = Orderweb::where("user_full_name", $user_full_name)
                -> where("closeReason", "!=", null)
                -> where("server", "!=", null)
                -> where("comment", "!=", null)->get()->toArray();
            $i=0;
            foreach ($orderUpdate as $value) {
                $response[$i] = [
                    'routefrom' => $value["routefrom"],
                    'routefromnumber' => $value["routefromnumber"],
                    'routeto' => $value["routeto"],
                    'routetonumber' => $value["routetonumber"],
                    'web_cost' => $value["web_cost"],
                    'closeReason' => $value["closeReason"],
                    'created_at' => $value["created_at"],
                ];
                $i++;
            }
        }
        return $response;
    }

    public function UIDStatusShowEmail($email)
    {

//        $order = Orderweb::where("email", $email)
//
//           ->where("closeReason", "!=", null)
//            ->where("closeReason", "-1")
//            ->where("server", "!=", null)
//            ->where("comment", "!=", null)
//            ->orderBy("created_at", "desc")
//            ->get();
        $order = Orderweb::where("email", $email)
            ->where("closeReason", "-1")
//            ->whereNotNull("server")
//            ->whereNotNull("comment")
//            ->orderBy("created_at", "desc")
            ->get();

//dd($order);
        $response = null;
        Log::debug("UIDStatusShowEmail order 1", $order->toArray());
        if (!$order->isEmpty()) {
            self::UIDStatusReview($order);
        }
//        $orderHistory = Orderweb::where("email", $email)
//
//            -> where("closeReason", "!=", null)
//            -> where("server", "!=", null)
//            -> where("startLat", "!=", null)
//            -> where("startLan", "!=", null)
//            -> where("to_lat", "!=", null)
//            -> where("to_lng", "!=", null)
//            -> where("comment", "!=", null)
//            -> orderBy("created_at", "desc")
//            -> get();

        $orderHistory = Orderweb::where("email", $email)
            ->whereNotNull("closeReason")
//            ->whereNotNull("server")
//            ->whereNotNull("startLat")
//            ->whereNotNull("startLan")
//            ->whereNotNull("to_lat")
//            ->whereNotNull("to_lng")
//            ->whereNotNull("comment")
            ->orderBy("created_at", "desc")
            ->get()
            ->take(30);

        if ($orderHistory) {
            $i=0;
            $orderUpdate = $orderHistory->toArray();
            Log::debug("UIDStatusShowEmail orderUpdate", $orderUpdate);
            date_default_timezone_set('Europe/Kiev');

            foreach ($orderUpdate as $value) {
                if ($i < 5) {
                    $response[] = [
                        'routefrom' => $value["routefrom"],
                        'routefromnumber' => $value["routefromnumber"],
                        'startLat' => $value["startLat"],
                        'startLan' => $value["startLan"],
                        'routeto' => $value["routeto"],
                        'routetonumber' => $value["routetonumber"],
                        'to_lat' => $value["to_lat"],
                        'to_lng' => $value["to_lng"],
                        'web_cost' => $value["web_cost"],
                        'closeReason' => $value["closeReason"],
                        'auto' => $value["auto"],
                        'created_at' => date('d.m.Y H:i:s', strtotime($value["created_at"])),
                    ];
                } else {
//                    if ($value["closeReason"] == "0" ) {
                    if ($value["closeReason"] == 0 || $value["closeReason"] == 8 ||$value["closeReason"] == 9) {
                        $response[] = [
                            'routefrom' => $value["routefrom"],
                            'routefromnumber' => $value["routefromnumber"],
                            'startLat' => $value["startLat"],
                            'startLan' => $value["startLan"],
                            'routeto' => $value["routeto"],
                            'routetonumber' => $value["routetonumber"],
                            'to_lat' => $value["to_lat"],
                            'to_lng' => $value["to_lng"],
                            'web_cost' => $value["web_cost"],
                            'closeReason' => $value["closeReason"],
                            'auto' => $value["auto"],
                            'created_at' => date('d.m.Y H:i:s', strtotime($value["created_at"])),
                        ];
                    }
                }
                $i++;
            }
        } else {
            $response = null;
            $response[] = [
                'routefrom' => "*",
                'routefromnumber' => "*",
                'routeto' => "*",
                'routetonumber' => "*",
                'web_cost' => "*",
                'closeReason' => "*",
                'auto' => "*",
                'created_at' => "*",
            ];
        }
//        Log::debug("UIDStatusShowEmail response", $response);
        return $response;
    }

    public function getServerArray($city, $app): array
    {
        Log::info('🟢 НАЧАЛО getServerArray', [
            'input_city' => $city,
            'input_app' => $app
        ]);

        $originalCity = $city;

        // Логируем преобразование города
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                Log::debug('🏙️ Город преобразован в OdessaTest', [
                    'original_city' => $originalCity,
                    'new_city' => $city
                ]);
                break;
            case "foreign countries":
                $city = "Kyiv City";
                Log::debug('🏙️ Иностранные страны преобразованы в Kyiv City', [
                    'original_city' => $originalCity,
                    'new_city' => $city
                ]);
                break;
            default:
                Log::debug('🏙️ Город не требует преобразования', [
                    'city' => $city
                ]);
        }

        // Логируем выбор таблицы по приложению
        Log::debug('📱 Выбор таблицы серверов по приложению', [
            'app' => $app,
            'city' => $city
        ]);

        switch ($app) {
            case "PAS1":
                $serverInfo = City_PAS1::where("name", $city)->get();
                Log::debug('🔍 Поиск серверов в City_PAS1', [
                    'table' => 'City_PAS1',
                    'city' => $city,
                    'query' => "name = $city"
                ]);
                break;
            case "PAS2":
                $serverInfo = City_PAS2::where("name", $city)->get();
                Log::debug('🔍 Поиск серверов в City_PAS2', [
                    'table' => 'City_PAS2',
                    'city' => $city,
                    'query' => "name = $city"
                ]);
                break;
            //case "PAS4":
            default:
                $serverInfo = City_PAS4::where("name", $city)->get();
                Log::debug('🔍 Поиск серверов в City_PAS4 (по умолчанию)', [
                    'table' => 'City_PAS4',
                    'city' => $city,
                    'query' => "name = $city"
                ]);
        }

        // Логируем результаты поиска серверов
        Log::debug('📊 Результаты поиска серверов', [
            'found_servers_count' => $serverInfo->count(),
            'servers' => $serverInfo->pluck('address')->toArray(),
            'servers_full' => $serverInfo->toArray()
        ]);

        $serverArray = [];

        if ($serverInfo->isNotEmpty()) {
            Log::info('✅ Серверы найдены, формируем массив адресов');
            foreach ($serverInfo as $index => $value) {
                $serverAddress = 'http://' . $value->address;
                $serverArray[] = $serverAddress;
                Log::debug("🔗 Добавлен сервер в массив", [
                    'index' => $index,
                    'original_address' => $value->address,
                    'full_address' => $serverAddress,
                    'server_id' => $value->id ?? 'unknown'
                ]);
            }
            // Добавляем my_server_api в конец массива
            $serverArray[] = 'my_server_api';
            Log::debug("➕ Добавлен my_server_api в массив серверов", [
                'total_servers_count' => count($serverArray),
                'added_server' => 'my_server_api'
            ]);
        } else {
            Log::warning('⚠️ Серверы не найдены для указанных параметров', [
                'city' => $city,
                'app' => $app,
                'original_city' => $originalCity
            ]);
        }

        Log::info('🎯 ЗАВЕРШЕНИЕ getServerArray', [
            'input_city' => $originalCity,
            'processed_city' => $city,
            'app' => $app,
            'server_array_count' => count($serverArray),
            'server_array' => $serverArray
        ]);

        return $serverArray;
    }

    private static function getAppName($app): string
    {

        switch ($app) {
            case "PAS1":
                $result  = "taxi_easy_ua_pas1";
                break;
            case "PAS2":
                $result  = "taxi_easy_ua_pas2";
                break;
            //case "PAS4":
            default:
                $result  = "taxi_easy_ua_pas4";
        }


        return $result;
    }

    public function UIDStatusShowEmailCityApp($email, $city, $app)
    {
        Log::info('🟢 НАЧАЛО UIDStatusShowEmailCityApp', [
            'email' => $email,
            'city' => $city,
            'app' => $app
        ]);

        $serverArray = self::getServerArray($city, $app);
        $app_name = self::getAppName($app);

        Log::debug('📡 Получены сервер и приложение', [
            'serverArray' => $serverArray,
            'app_name' => $app_name
        ]);

        if ($serverArray != null) {
            Log::debug('✅ Сервер доступен, продолжаем обработку');

            // Логируем преобразование города
            $originalCity = $city;
            switch ($city) {
                case "Kyiv City":
                    $city = "city_kiev";
                    break;
                case "Cherkasy Oblast":
                    $city = "city_cherkassy";
                    break;
                case "Odessa":
                case "OdessaTest":
                    $city = "city_odessa";
                    break;
                case "Zaporizhzhia":
                    $city = "city_zaporizhzhia";
                    break;
                case "Dnipropetrovsk Oblast":
                    $city = "city_dnipro";
                    break;
                case "Lviv":
                    $city = "city_lviv";
                    break;
                case "Ivano_frankivsk":
                    $city = "city_ivano_frankivsk";
                    break;
                case "Vinnytsia":
                    $city = "city_vinnytsia";
                    break;
                case "Poltava":
                    $city = "city_poltava";
                    break;
                case "Sumy":
                    $city = "city_sumy";
                    break;
                case "Kharkiv":
                    $city = "city_kharkiv";
                    break;
                case "Chernihiv":
                    $city = "city_chernihiv";
                    break;
                case "Rivne":
                    $city = "city_rivne";
                    break;
                case "Ternopil":
                    $city = "city_ternopil";
                    break;
                case "Khmelnytskyi":
                    $city = "city_khmelnytskyi";
                    break;
                case "Zakarpattya":
                    $city = "city_zakarpattya";
                    break;
                case "Zhytomyr":
                    $city = "city_zhytomyr";
                    break;
                case "Kropyvnytskyi":
                    $city = "city_kropyvnytskyi";
                    break;
                case "Mykolaiv":
                    $city = "city_mykolaiv";
                    break;
                case "Chernivtsi":
                    $city = "city_chernivtsi";
                    break;
                case "Lutsk":
                    $city = "city_lutsk";
                    break;
                default:
                    $city = "all";
            }
            Log::debug('🏙️ Преобразование города', [
                'original_city' => $originalCity,
                'db_city' => $city
            ]);

            // Поиск активных заказов
            Log::info('🔍 Поиск активных заказов...', [
                'email' => $email,
                'closeReasons' => ['-1', '100', '101', '102'],
                'app_name' => $app_name,
                'city' => $city
            ]);

            $order = Orderweb::where("email", $email)
                ->whereIn('closeReason', ['-1', '100', '101', '102'])
                ->where("comment", $app_name)
                ->where("city", $city)
                ->orderBy("created_at", "desc")
                ->get();

            Log::debug('📊 Результат поиска активных заказов', [
                'found_records' => $order->count(),
                'order_ids' => $order->pluck('id')->toArray(),
                'closeReasons' => $order->pluck('closeReason')->toArray()
            ]);

            $response = null;
            if (!$order->isEmpty()) {
                Log::info('🔄 Запуск UIDStatusReview для активных заказов', [
                    'order_count' => $order->count()
                ]);
                self::UIDStatusReview($order);
            } else {
                Log::info('ℹ️ Активных заказов не найдено');
            }

            // Поиск истории заказов
            Log::info('🔍 Поиск истории заказов...', [
                'email' => $email,
                'excluded_closeReasons' => ['-1', '100', '101', '102'],
                'serverArray' => $serverArray,
                'app_name' => $app_name,
                'city' => $city,
                'limit' => 10
            ]);

            $orderHistory = Orderweb::where("email", $email)
                ->whereNotIn('closeReason', ['-1', '100', '101', '102'])
                ->whereIn("server", $serverArray)
                ->where("comment", $app_name)
                ->where("city", $city)
                ->orderBy("created_at", "desc")
                ->get()
                ->take(10);

            Log::debug('📊 Результат поиска истории заказов', [
                'found_records' => $orderHistory->count(),
                'order_ids' => $orderHistory->pluck('id')->toArray(),
                'closeReasons' => $orderHistory->pluck('closeReason')->toArray()
            ]);

            if ($orderHistory->isNotEmpty()) {
                Log::info('📝 Формирование ответа с историей заказов', [
                    'records_count' => $orderHistory->count()
                ]);

                $i = 0;
                $orderUpdate = $orderHistory->toArray();

                Log::debug('📋 Данные истории заказов для обработки', [
                    'total_records' => count($orderUpdate),
                    'first_record' => $orderUpdate[0] ?? 'empty'
                ]);

                date_default_timezone_set('Europe/Kiev');

                foreach ($orderUpdate as $index => $value) {
                    Log::debug("🔧 Обработка заказа #{$index}", [
                        'order_id' => $value['id'] ?? 'unknown',
                        'closeReason' => $value['closeReason'] ?? 'unknown',
                        'auto_data' => $value['auto'] ?? 'empty'
                    ]);

                    $storedData = $value["auto"] ?? '';
                    $dataDriver = json_decode($storedData, true);

                    if ($dataDriver && isset($dataDriver["uid"]) && $dataDriver["uid"] != null) {
                        $color = $dataDriver["color"] ?? '';
                        $brand = $dataDriver["brand"] ?? '';
                        $model = $dataDriver["model"] ?? '';
                        $number = $dataDriver["number"] ?? '';
                        $auto = "Авто $number, цвет $color $brand $model";
                        Log::debug("🚗 Данные водителя из JSON", [
                            'number' => $number,
                            'color' => $color,
                            'brand' => $brand,
                            'model' => $model
                        ]);
                    } else {
                        $auto = $value["auto"] ?? '';
                        Log::debug("📄 Данные водителя из прямого поля", ['auto' => $auto]);
                    }

                    // Расчет стоимости
                    $cost = $value["web_cost"] ?? 0;
                    if (!empty($value["client_cost"])) {
                        $cost = $value["client_cost"] + ($value["attempt_20"] ?? 0);
                        Log::debug('💰 Расчет стоимости с client_cost', [
                            'client_cost' => $value["client_cost"],
                            'attempt_20' => $value["attempt_20"] ?? 0,
                            'total_cost' => $cost
                        ]);
                    }
                    if (!empty($value["finish_cost"])) {
                        $cost = $value["finish_cost"];
                        Log::debug('💰 Использована finish_cost', ['finish_cost' => $cost]);
                    }

                    // Форматирование дат
                    $requiredTime = !empty($value["required_time"]) ? date('d.m.Y H:i', strtotime($value["required_time"])) : '';
                    $createdAt = !empty($value["created_at"]) ? date('d.m.Y H:i:s', strtotime($value["created_at"])) : '';

                    if ($i < 10) {
                        $response[] = [
                            'routefrom' => $value["routefrom"] ?? '',
                            'routefromnumber' => $value["routefromnumber"] ?? '',
                            'startLat' => $value["startLat"] ?? '',
                            'startLan' => $value["startLan"] ?? '',
                            'routeto' => $value["routeto"] ?? '',
                            'routetonumber' => $value["routetonumber"] ?? '',
                            'to_lat' => $value["to_lat"] ?? '',
                            'to_lng' => $value["to_lng"] ?? '',
                            'web_cost' => $cost,
                            'closeReason' => $value["closeReason"] ?? '',
                            'auto' => $auto,
                            'required_time' => $requiredTime,
                            'created_at' => $createdAt,
                        ];
                        Log::debug("✅ Добавлен заказ в ответ (i < 10)", ['index' => $i]);
                    } else {
                        if (in_array($value["closeReason"] ?? '', [0, 8, 9])) {
                            $response[] = [
                                'routefrom' => $value["routefrom"] ?? '',
                                'routefromnumber' => $value["routefromnumber"] ?? '',
                                'startLat' => $value["startLat"] ?? '',
                                'startLan' => $value["startLan"] ?? '',
                                'routeto' => $value["routeto"] ?? '',
                                'routetonumber' => $value["routetonumber"] ?? '',
                                'to_lat' => $value["to_lat"] ?? '',
                                'to_lng' => $value["to_lng"] ?? '',
                                'web_cost' => $value["web_cost"] ?? 0,
                                'closeReason' => $value["closeReason"] ?? '',
                                'auto' => $auto,
                                'required_time' => $requiredTime,
                                'created_at' => $createdAt,
                            ];
                            Log::debug("✅ Добавлен заказ в ответ (closeReason 0,8,9)", [
                                'index' => $i,
                                'closeReason' => $value["closeReason"] ?? ''
                            ]);
                        } else {
                            Log::debug("❌ Заказ пропущен (closeReason не 0,8,9)", [
                                'index' => $i,
                                'closeReason' => $value["closeReason"] ?? ''
                            ]);
                        }
                    }
                    $i++;
                }

                Log::info('📤 Ответ с историей заказов сформирован', [
                    'total_records_in_response' => count($response ?? [])
                ]);

            } else {
                Log::warning('⚠️ История заказов не найдена, создаем заглушку');
                $response = null;
                $response[] = [
                    'routefrom' => "*",
                    'routefromnumber' => "*",
                    'routeto' => "*",
                    'routetonumber' => "*",
                    'web_cost' => "*",
                    'closeReason' => "*",
                    'auto' => "*",
                    'created_at' => "*",
                ];
            }

            Log::info('🎯 ЗАВЕРШЕНИЕ UIDStatusShowEmailCityApp', [
                'email' => $email,
                'total_response_records' => count($response ?? [])
            ]);

            return $response;
        } else {
            Log::error('❌ Сервер не доступен, прерываем выполнение', [
                'city' => $city,
                'app' => $app
            ]);
            return null;
        }
    }

    public function UIDStatusShowEmailCancel($email)
    {

        $order = Orderweb:: where("email", $email)
            ->whereIn('closeReason', ['-1', '101', '102'])
            ->where("server", "!=", null)
            ->where("comment", "!=", null)
            ->orderBy("created_at", "desc")
            ->get();
//dd($order);
        $response = null;
        Log::debug("UIDStatusShowEmailCancel order", $order->toArray());
        if (!$order->isEmpty()) {
            self::UIDStatusReview($order);
//            $orderHistory = Orderweb::where("email", $email)
//                -> whereIn('closeReason', ['-1', '101', '102'])
//                -> where("server", "!=", null)
//                -> where("startLat", "!=", null)
//                -> where("startLan", "!=", null)
//                -> where("to_lat", "!=", null)
//                -> where("to_lng", "!=", null)
//                -> where("comment", "!=", null)
//                -> orderBy("created_at", "desc")
//                -> get();
            $orderHistory = Orderweb::where("email", $email)
                ->whereIn('closeReason', ['-1', '101', '102'])
//                ->whereNotNull("server")
//                ->whereNotNull("startLat")
//                ->whereNotNull("startLan")
//                ->whereNotNull("to_lat")
//                ->whereNotNull("to_lng")
//                ->whereNotNull("comment")
                ->orderBy("created_at", "desc")
                ->get();

            if ($orderHistory) {
                $i=0;
                $orderUpdate = $orderHistory->toArray();
                Log::debug("UIDStatusShowEmailCancel orderUpdate", $orderUpdate);
                date_default_timezone_set('Europe/Kiev');

                foreach ($orderUpdate as $value) {
                    $uid_history = Uid_history::where("uid_bonusOrderHold", $value['id'])->first();
                    $dispatchingOrderUidDouble = "";
                    if ($uid_history) {
                        $dispatchingOrderUidDouble = $uid_history->uid_doubleOrder;
                        Log::debug("uid_history webordersCancelDouble :", $uid_history->toArray());
                    } else {
                        $dispatchingOrderUidDouble = " ";
                    }
                    $storedData = $value["auto"];

                    $dataDriver = json_decode($storedData, true);

                    if ($dataDriver["uid"] != null) {
                        $storedData = $value["auto"];

//                        $name = $dataDriver["name"];
                        $color = $dataDriver["color"];
                        $brand = $dataDriver["brand"];
                        $model = $dataDriver["model"];
                        $number = $dataDriver["number"];
                        $auto = "Авто $number, цвет $color  $brand $model";
                    } else {
                        $auto =  $value["auto"];
                    }

                    $response[] = [
                        'uid' => $value["dispatching_order_uid"],
                        'routefrom' => $value["routefrom"],
                        'routefromnumber' => $value["routefromnumber"],
                        'startLat' => $value["startLat"],
                        'startLan' => $value["startLan"],
                        'routeto' => $value["routeto"],
                        'routetonumber' => $value["routetonumber"],
                        'to_lat' => $value["to_lat"],
                        'to_lng' => $value["to_lng"],
                        'web_cost' => $value["web_cost"],
                        'closeReason' => $value["closeReason"],
                        'auto' => $auto,
                        'required_time' => date('d.m.Y H:i', strtotime($value["required_time"])),
                        'dispatchingOrderUidDouble' => $dispatchingOrderUidDouble,
                        'pay_method' => $value["pay_system"],
                        'created_at' => date('d.m.Y H:i:s', strtotime($value["created_at"])),
                    ];

                    $i++;
                }
            } else {
                $response = null;
                $response[] = [
                    'routefrom' => "*",
                    'routefromnumber' => "*",
                    'routeto' => "*",
                    'routetonumber' => "*",
                    'web_cost' => "*",
                    'closeReason' => "*",
                    'auto' => "*",
                    'created_at' => "*",
                ];
            }
        } else {
            $response = null;
            $response[] = [
                'routefrom' => "*",
                'routefromnumber' => "*",
                'routeto' => "*",
                'routetonumber' => "*",
                'web_cost' => "*",
                'closeReason' => "*",
                'auto' => "*",
                'created_at' => "*",
            ];
        }
//        Log::debug("UIDStatusShowEmail response", $response);
        return $response;
    }
    public function UIDStatusShowEmailCancelApp($email, $cityApp, $app)
    {
        switch ($app) {
            case "PAS1":
                $application = "taxi_easy_ua_pas1";
                break;
            case "PAS2":
                $application = "taxi_easy_ua_pas2";
                break;
            //case "PAS4":
            default:
                $application = "taxi_easy_ua_pas4";
        }


        switch ($cityApp) {
            case "Kyiv City":
                $city = "city_kiev";
                break;
            case "Cherkasy Oblast":
                $city = "city_cherkassy";
                break;
            case "Odessa":
            case "OdessaTest":
                $city = "city_odessa";
                break;
            case "Zaporizhzhia":
                $city = "city_zaporizhzhia";
                break;
            case "Dnipropetrovsk Oblast":
                $city = "city_dnipro";
                break;
            case "Lviv":
                $city = "city_lviv";
                break;
            case "Ivano_frankivsk":
                $city = "city_ivano_frankivsk";
                break;
            case "Vinnytsia":
                $city = "city_vinnytsia";
                break;
            case "Poltava":
                $city = "city_poltava";
                break;
            case "Sumy":
                $city = "city_sumy";
                break;
            case "Kharkiv":
                $city = "city_kharkiv";
                break;
            case "Chernihiv":
                $city = "city_chernihiv";
                break;
            case "Rivne":
                $city = "city_rivne";
                break;
            case "Ternopil":
                $city = "city_ternopil";
                break;
            case "Khmelnytskyi":
                $city = "city_khmelnytskyi";
                break;
            case "Zakarpattya":
                $city = "city_zakarpattya";
                break;
            case "Zhytomyr":
                $city = "city_zhytomyr";
                break;
            case "Kropyvnytskyi":
                $city = "city_kropyvnytskyi";
                break;
            case "Mykolaiv":
                $city = "city_mykolaiv";
                break;
            case "Chernivtsi":
                $city = "city_chernivtsi";
                break;
            case "Lutsk":
                $city = "city_lutsk";
                break;
            default:
                $city = "all";
        }

        $order = Orderweb:: where("email", $email)
            ->whereIn('closeReason', ['-1', '100', '101', '102'])
            ->where("comment", $application)
            ->where("city", $city)
            ->orderBy("created_at", "desc")
            ->get();

        $response = null;
        Log::debug("UIDStatusShowEmailCancelApp order comment " . $application);
        Log::debug("UIDStatusShowEmailCancelApp order city " . $city);

        Log::debug("UIDStatusShowEmailCancelApp order comment " . $application);

        Log::debug("UIDStatusShowEmailCancelApp order", $order->toArray());

        if (!$order->isEmpty()) {

                self::UIDStatusReview($order);


//            $orderHistory = Orderweb::where("email", $email)
//                ->whereIn('closeReason', ['-1', '101', '102', '103'])
//
//                ->where("city", $city)
//                ->where("startLat", "!=", null)
//                ->where("startLan", "!=", null)
//                ->where("to_lat", "!=", null)
//                ->where("to_lng", "!=", null)
//                ->where("comment", $application)
//                ->orderBy("created_at", "desc")
//                ->get();
            $orderHistory = Orderweb::where("email", $email)
                ->whereIn('closeReason', ['-1', '100', '101', '102', '103'])
                ->where("city", $city)
//                ->whereNotNull("startLat")
//                ->whereNotNull("startLan")
//                ->whereNotNull("to_lat")
//                ->whereNotNull("to_lng")
                ->where("comment", $application)
                ->orderBy("created_at", "desc")
                ->get();

//            $controller = new MemoryOrderChangeController();
//            $orderHistory = $controller->getFilteredOrders($orderHistory);


            if ($orderHistory) {
                $i = 0;
                $orderUpdate = $orderHistory->toArray();
                Log::debug("UIDStatusShowEmailCancelApp orderUpdate", $orderUpdate);
                date_default_timezone_set('Europe/Kiev');

                foreach ($orderUpdate as $value) {
                    $uid_history = Uid_history::where("uid_bonusOrderHold", $value['dispatching_order_uid'])->first();
//                    Log::debug("uid_history webordersCancelDouble :", $uid_history->toArray());
                    $storedData = $value["auto"];

                    $dataDriver = json_decode($storedData, true);

                    if (isset($dataDriver["uid"]) && $dataDriver["uid"] !== null) {
                        $storedData = $value["auto"];

                        $dataDriver = json_decode($storedData, true);
//                            $name = $dataDriver["name"];
                        $color = $dataDriver["color"];
                        $brand = $dataDriver["brand"];
                        $model = $dataDriver["model"];
                        $number = $dataDriver["number"];
                        $auto = "Авто $number, цвет $color  $brand $model";
                    } else {
                        $auto =  $value["auto"];
                    }
                    if ($uid_history) {
                        $dispatchingOrderUidDouble = $uid_history->uid_doubleOrder;
                        Log::debug("uid_history webordersCancelDouble :", $uid_history->toArray());
                    } else {
                        $dispatchingOrderUidDouble = " ";
                    }
                    $cost = $value["web_cost"];
                    if ($value["client_cost"] !=null) {
                        $cost = $value["client_cost"]+ $value["attempt_20"];
                    }
                    if ($value["finish_cost"] !=null) {
                        $cost = $value["finish_cost"];
                    }
                    $response[] = [
                        'uid' => $value["dispatching_order_uid"],
                        'routefrom' => $value["routefrom"],
                        'routefromnumber' => $value["routefromnumber"],
                        'startLat' => $value["startLat"],
                        'startLan' => $value["startLan"],
                        'routeto' => $value["routeto"],
                        'routetonumber' => $value["routetonumber"],
                        'to_lat' => $value["to_lat"],
                        'to_lng' => $value["to_lng"],
                        'web_cost' => $cost,
                        'closeReason' => $value["closeReason"],
                        'auto' => $auto,
                        'flexible_tariff_name' => $value["flexible_tariff_name"],
                        'comment_info' => $value["comment_info"],
                        'extra_charge_codes' => $value["extra_charge_codes"],
                        'required_time' => date('d.m.Y H:i', strtotime($value["required_time"])),
                        'dispatching_order_uid_Double' => $dispatchingOrderUidDouble,
                        'pay_method' => $value["pay_system"],
                        'created_at' => date('d.m.Y H:i:s', strtotime($value["created_at"])),
                    ];

                    $i++;
                }
            } else {
                $response = null;
                $response[] = [
                    'routefrom' => "*",
                    'routefromnumber' => "*",
                    'routeto' => "*",
                    'routetonumber' => "*",
                    'web_cost' => "*",
                    'closeReason' => "*",
                    'auto' => "*",
                    'created_at' => "*",
                ];
            }
        } else {
            $response = null;
            $response[] = [
                'routefrom' => "*",
                'routefromnumber' => "*",
                'routeto' => "*",
                'routetonumber' => "*",
                'web_cost' => "*",
                'closeReason' => "*",
                'auto' => "*",
                'created_at' => "*",
            ];
        }
//        Log::debug("UIDStatusShowEmail response", $response);
            return $response;
    }

    /**
     * @throws \Exception
     */
//    public function UIDStatusShowEmailApp(
//        $email,
//        $city,
//        $application
//    ) {
//        $connectAPI = (new AndroidTestOSMController)->connectAPIAppOrder($city, $application);
//
//
//        if ($connectAPI == 400) {
//            $response = null;
//            $response[] = [
//                'routefrom' => "*",
//                'routefromnumber' => "*",
//                'routeto' => "*",
//                'routetonumber' => "*",
//                'web_cost' => "*",
//                'closeReason' => "*",
//                'auto' => "*",
//                'created_at' => "*",
//            ];
//            return $response;
//        }
//
//        $order = Orderweb:: where("email", $email)
////            ->where("closeReason", "!=", null)
//            ->where("closeReason", "!=", "-1")
////            ->where("server", "!=", null)
////            ->where("comment", "!=", null)
//            ->orderBy("created_at", "desc")
//            ->get();
////dd($order);
//        $response = null;
//        if (!$order->isEmpty()) {
//            self::UIDStatusReview($order);
//        }
////        $orderHistory = Orderweb::where("email", $email)
////
////            -> where("closeReason", "!=", null)
////            -> where("server", "!=", null)
////            -> where("startLat", "!=", null)
////            -> where("startLan", "!=", null)
////            -> where("to_lat", "!=", null)
////            -> where("to_lng", "!=", null)
////            -> where("comment", "!=", null)
////            -> orderBy("created_at", "desc")
////            -> get();
//        $orderHistory = Orderweb::where("email", $email)
////            ->whereNotNull("closeReason")
////            ->whereNotNull("server")
////            ->whereNotNull("startLat")
////            ->whereNotNull("startLan")
////            ->whereNotNull("to_lat")
////            ->whereNotNull("to_lng")
////            ->whereNotNull("comment")
//            ->orderBy("created_at", "desc")
//            ->get();
//
//        if (!$orderHistory->isEmpty()) {
//            $i=0;
//            $orderUpdate = $orderHistory->toArray();
//            date_default_timezone_set('Europe/Kiev');
//
//            foreach ($orderUpdate as $value) {
//                if ($i < 5) {
//                    $response[] = [
//                        'routefrom' => $value["routefrom"],
//                        'routefromnumber' => $value["routefromnumber"],
//                        'startLat' => $value["startLat"],
//                        'startLan' => $value["startLan"],
//                        'routeto' => $value["routeto"],
//                        'routetonumber' => $value["routetonumber"],
//                        'to_lat' => $value["to_lat"],
//                        'to_lng' => $value["to_lng"],
//                        'web_cost' => $value["web_cost"],
//                        'closeReason' => $value["closeReason"],
//                        'auto' => $value["auto"],
//                        'created_at' => date('d.m.Y H:i:s', strtotime($value["created_at"])),
//                    ];
//                } else {
////                    if ($value["closeReason"] == "0" ) {
//                    if ($value["closeReason"] == 0 || $value["closeReason"] == 8 ||$value["closeReason"] == 9) {
//                        $response[] = [
//                            'routefrom' => $value["routefrom"],
//                            'routefromnumber' => $value["routefromnumber"],
//                            'startLat' => $value["startLat"],
//                            'startLan' => $value["startLan"],
//                            'routeto' => $value["routeto"],
//                            'routetonumber' => $value["routetonumber"],
//                            'to_lat' => $value["to_lat"],
//                            'to_lng' => $value["to_lng"],
//                            'web_cost' => $value["web_cost"],
//                            'closeReason' => $value["closeReason"],
//                            'auto' => $value["auto"],
//                            'created_at' => date('d.m.Y H:i:s', strtotime($value["created_at"])),
//                        ];
//                    }
//                }
//                $i++;
//            }
//        } else {
//            $response = null;
//            $response[] = [
//                'routefrom' => "*",
//                'routefromnumber' => "*",
//                'routeto' => "*",
//                'routetonumber' => "*",
//                'web_cost' => "*",
//                'closeReason' => "*",
//                'auto' => "*",
//                'created_at' => "*",
//            ];
//        }
//        return $response;
//    }

    public function UIDStatusShowAdmin(): array
    {
        $order = Orderweb::where("closeReason", "!=", null)
            -> where("server", "!=", null)
            -> where("comment", "!=", null)
            ->orderByDesc('created_at')
            ->get();
        $response = null;
//        dd($order->toArray());
        if (!$order->isEmpty()) {
            $i=0;

            foreach ($order->toArray() as $value) {
                switch ($value["closeReason"]) {
                    case "-1":
                        $closeReasonText = "(-1) В обработке";
                        break;
                    case "0":
                        $closeReasonText = "(0) Выполнен";
                        break;
                    case "1":
                        $closeReasonText = "(1) Снят клиентом";
                        break;
                    case "2":
                        $closeReasonText = "(2) Не выполнено";
                        break;
                    case "3":
                        $closeReasonText = "(3) Не выполнено";
                        break;
                    case "4":
                        $closeReasonText = "(4) Не выполнено";
                        break;
                    case "5":
                        $closeReasonText = "(5) Не выполнено";
                        break;
                    case "6":
                        $closeReasonText = "(6) Снят клиентом";
                        break;
                    case "7":
                        $closeReasonText = "(7) Снят клиентом";
                        break;
                    case "8":
                        $closeReasonText = "(8) Выполнен";
                        break;
                    case "9":
                        $closeReasonText = "(9) Снят клиентом";
                        break;
                    default:
                        $closeReasonText = "не известное значение";
                        break;

                }


                date_default_timezone_set('Europe/Kiev');


                $date = new DateTime($value["created_at"]);
                $date->add(new DateInterval('PT3H'));

                $formatted_date = $date->format('d.m.Y H:i:s');


                $response[$i] = [
                    'id' => $value["id"],
                    'first' =>$formatted_date,
                    'name' => $value["user_full_name"],
                    'from' => "От " . $value["routefrom"] . " " . $value["routefromnumber"] . " до " . $value["routeto"] . " " . $value["routetonumber"],
                    'cost' => $value["web_cost"],
                    'uid' => $value["dispatching_order_uid"],
                    'reason' => $closeReasonText,
                ];
                $i++;
            }
        }
//        dd($response);
        return $response;
    }

    public function UIDStatusReviewAdmin($uid)
    {
         $order = Orderweb::where("dispatching_order_uid", $uid)->first();

         $connectAPI =  $order->server;
         $autorization = self::autorization($connectAPI);
         $identificationId = $order->comment;

         $order->closeReason = self::closeReasonUIDStatusFirst($uid, $connectAPI, $autorization, $identificationId);
         $order->save();
    }

    public function UIDStatusReviewDaily()
    {
        Log::info('UIDStatusReviewDaily started.');

        try {
            // Получение записей для обработки
//            $orderwebs = Orderweb::whereIn('closeReason', ['-1', '101', '102'])
            $orderwebs = Orderweb::whereIn('closeReason', ['-1'])
                ->whereNotNull('server')
                ->whereNotNull('comment')
                ->get();

            Log::info("Found {$orderwebs->count()} orders to process.");

            // Сбор всех уникальных UID
            $dispatchingOrderUids = $orderwebs->pluck('dispatching_order_uid');
            Log::info("Collected dispatching_order_uids: " . implode(', ', $dispatchingOrderUids->toArray()));

            // Загрузка связанных записей одним запросом
            $orderData = Orderweb::whereIn('dispatching_order_uid', $dispatchingOrderUids)
                ->get()
                ->keyBy('dispatching_order_uid');

            $processedCount = 0;
            $errorsCount = 0;

            // Обработка каждой записи
            foreach ($orderwebs as $value) {
                $uid_history = Uid_history::where("uid_bonusOrderHold", $value->dispatching_order_uid)->first();

                if ($uid_history) {
                    self::UIDStatusReviewCard($value->dispatching_order_uid);
                } else {
                    $order = $orderData->get($value->dispatching_order_uid);

                    if ($order) {
                        $connectAPI = $order->server;
                        $autorization = self::autorization($connectAPI);
                        $identificationId = $order->comment;

                        try {
                            Log::info("Processing order UID: {$value->dispatching_order_uid}");

                            $order->closeReason = self::closeReasonUIDStatusFirst(
                                $value->dispatching_order_uid,
                                $connectAPI,
                                $autorization,
                                $identificationId
                            );
                            $order->save();

                            $processedCount++;
                            Log::info("Order UID {$value->dispatching_order_uid} successfully updated.");
                        } catch (\Exception $e) {
                            $errorsCount++;
                            Log::error("Error updating order UID {$value->dispatching_order_uid}: {$e->getMessage()}");
                        }
                    } else {
                        Log::warning("No matching order found for UID: {$value->dispatching_order_uid}");
                    }
                }
            }

            Log::info("UIDStatusReviewDaily completed. Processed: {$processedCount}, Errors: {$errorsCount}.");

        } catch (\Exception $e) {
            Log::critical("UIDStatusReviewDaily failed with error: {$e->getMessage()}");
        }
    }



    public function UIDStatusReview($order)
    {
        Log::debug("UIDStatusReview", $order->toArray());
        foreach ($order->toArray() as $value) {
            $currentTime = time();
            $uid = $value["dispatching_order_uid"];

            $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

            if ($uid_history) {
                self::UIDStatusReviewCard($uid);
            } else {
              if (!in_array($value['closeReason'],  ['100', '101', '102', '103', '104'] )) {

                  $timeElapsed = $currentTime - strtotime($value["updated_at"]);
                  $timeElapsed5 = $currentTime - strtotime($value["updated_at"]) - 5 * 60;

                  $closeReason = $value["closeReason"];
                  $closeReasonI = $value["closeReasonI"];
                  $connectAPI = $value["server"];

                  switch ($value["comment"]) {
                      case "taxi_easy_ua_pas1":
                          $application = "PAS1";
                          break;
                      case "taxi_easy_ua_pas2":
                          $application = "PAS2";
                          break;
                      default:
                          $application = "PAS4";
                  }
                  Log::debug("UIDStatusReview application $application");

                  $address = str_replace("http://", "", $connectAPI);
                  switch ($application) {
                      case "PAS1":
                          $serverInfo = City_PAS1::where('address', $address)->first();
                          break;
                      case "PAS2":
                          $serverInfo = City_PAS2::where('address', $address)->first();
                          break;
                      default:
                          $serverInfo = City_PAS4::where('address', $address)->first();
                  }

                  // Проверка, найден ли сервер
                  if ($serverInfo && $serverInfo->online == "true") {
                      Log::debug("UIDStatusReview serverInfo online: true");
                      $identificationId = $value["comment"];
                      switch ($closeReason) {
                          case "-1":
                              if ($timeElapsed >= 60) {
                                  UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                              }
                              break;
                          case "0":
                          case "1":
                          case "2":
                          case "3":
                          case "4":
                          case "5":
                              switch ($closeReasonI) {
                                  case 1:
                                      if ($timeElapsed5 >= 5 * 60 && $timeElapsed >= 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                                  case 2:
                                      if ($timeElapsed >= 60 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                                  case 3:
                                      if ($timeElapsed >= 24 * 60 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                                  case 4:
                                      if ($timeElapsed >= 3 * 24 * 60 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                              }
                              break;
                          case "6":
                          case "7":
                          case "8":
                          case "9":
                              switch ($closeReasonI) {
                                  case "1":
                                      if ($timeElapsed >= 5 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                                  case "2":
                                      if ($timeElapsed >= 60 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                                  case "3":
                                      if ($timeElapsed >= 24 * 60 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                                  case "4":
                                      if ($timeElapsed >= 3 * 24 * 60 * 60) {
                                          UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                      }
                                      break;
                              }
                              break;
                      }
                  } else {
                      Log::error("UIDStatusReview serverInfo is null or offline for address $address");
                  }
              }
            }

        }
    }

    public function UIDStatusReviewService($order)
    {
        $value = $order->toArray();
        Log::debug("UIDStatusReview", $value);
        $uid = $value["dispatching_order_uid"];

        $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

        if ($uid_history) {
            self::UIDStatusReviewCard($uid);
        } else {
            if (!in_array($value['closeReason'],  ['101', '102', '103', '104'] )) {
                $connectAPI = $value["server"];

                switch ($value["comment"]) {
                    case "taxi_easy_ua_pas1":
                        $application = "PAS1";
                        break;
                    case "taxi_easy_ua_pas2":
                        $application = "PAS2";
                        break;
                    default:
                        $application = "PAS4";
                }
                Log::debug("UIDStatusReview application $application");

                $address = str_replace("http://", "", $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $serverInfo = City_PAS1::where('address', $address)->first();
                        break;
                    case "PAS2":
                        $serverInfo = City_PAS2::where('address', $address)->first();
                        break;
                    default:
                        $serverInfo = City_PAS4::where('address', $address)->first();
                }

                // Проверка, найден ли сервер
                if ($serverInfo && $serverInfo->online == "true") {
                    Log::debug("UIDStatusReview serverInfo online: true");
                    $identificationId = $value["comment"];
                    UIDController::closeReasonUIDStatusService($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                } else {
                    Log::error("UIDStatusReview serverInfo is null or offline for address $address");
                }
            }
        }


    }

    public function UIDStatusReviewCard($dispatching_order_uid)
    {

        $startTime = time(); // Запоминаем начальное время

        do {
            // Попробуем найти запись
            $uid_history = Uid_history::where("uid_bonusOrderHold", $dispatching_order_uid)->first();

            if ($uid_history) {
                // Если запись найдена, выходим из цикла
                $nalOrderInput = $uid_history->double_status;
                $cardOrderInput = $uid_history->bonus_status;
                break;
            } else {
                $uid_history = Uid_history::where("uid_doubleOrder", $dispatching_order_uid)->first();

                if ($uid_history) {
                    // Если запись найдена, выходим из цикла
                    $nalOrderInput = $uid_history->double_status;
                    $cardOrderInput = $uid_history->bonus_status;
                    $dispatching_order_uid = $uid_history->uid_bonusOrder;
                    break;
                }
            }

            // Ждём одну секунду перед следующим проверочным циклом
            sleep(1);
        } while (time() - $startTime < 60); // Проверяем, не прошло ли 60 секунд

        if ($uid_history) {
            $messageAdmin = "getOrderStatusMessageResultPush: nal: $nalOrderInput, card: $cardOrderInput";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $nalOrder = json_decode($nalOrderInput, true);
            $cardOrder = json_decode($cardOrderInput, true);

            $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
            $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';

            $messageAdmin = "getOrderStatusMessageResultPush real: nalState: $nalState, cardState: $cardState";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();

            if (isset($orderweb)) {

                // Блок 1: Состояния "Поиск авто"
                if (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) &&
                    in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'SearchesForCar' && $cardState === 'CostCalculation') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'SearchesForCar') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'Canceled' && $cardState === 'SearchesForCar') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'SearchesForCar' && $cardState === 'Canceled') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'Canceled' && $cardState === 'WaitingCarSearch') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'WaitingCarSearch' && $cardState === 'Canceled') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])){
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }
                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) && $cardState === 'CostCalculation') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                }

                // Блок 2: Состояния "Авто найдено"
                elseif ($nalState === 'SearchesForCar' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'SearchesForCar') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'WaitingCarSearch' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'WaitingCarSearch') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'CarFound' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'Running' && $cardState === 'CarFound') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'Running' && $cardState === 'Running') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif ($nalState === 'Canceled' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'Canceled') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                }
                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'CostCalculation') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                }

                // Блок 3: Состояния "Заказ выполнен"
                elseif ($nalState === 'Executed' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running'])) {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                }
                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running']) && $cardState === 'Executed') {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                }
                elseif ($nalState === 'Executed' && $cardState === 'CostCalculation') {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'Executed') {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                }
                // Блок 4: Состояния "Заказ снят" с проверкой close_reason
                elseif ($nalState === 'Canceled' && $cardState === 'CostCalculation') {
                    $closeReason = $nalOrder['close_reason'] ?? -1;
                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                    if ($closeReason == "-1") {
                        $orderweb->auto = null;
                    }
                    $orderweb->closeReason = $closeReason;

                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'Canceled') {
                    $closeReason = $cardOrder['close_reason'] ?? -1;
                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                    $orderweb->closeReason = $closeReason;
                    if($closeReason == "-1") {
                        $orderweb->auto = null;
                    }
                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'CostCalculation') {
                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
                        $action = 'Заказ снят';
                        $orderweb->closeReason = "1";
                    } else {
                        $action = 'Поиск авто';
                        $orderweb->auto = null;
                        $orderweb->closeReason = "-1";
                    }

                }
                elseif ($nalState === 'Canceled' && $cardState === 'Canceled') {
                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
                        $action = 'Заказ снят';
                        $orderweb->closeReason = "1";
                    } else {
                        $action = 'Поиск авто';
                        $orderweb->auto = null;
                        $orderweb->closeReason = "-1";
                    }
                    $response = $cardOrderInput; // БЕЗНАЛ
                } else {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";

                }

                $orderweb->save();

            }
        }











    }

    public function autorization($connectApi)
    {

        $city = City::where('address', str_replace('http://', '', $connectApi))->first();
        if (!$city) {
            // Логирование ошибки для диагностики
            Log::error("City не найден для адреса: " . $connectApi);
            return null;
        }
        $username = $city->login;
        $password = hash('SHA512', $city->password);

        return 'Basic ' . base64_encode($username . ':' . $password);
    }

}
