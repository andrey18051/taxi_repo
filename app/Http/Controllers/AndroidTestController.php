<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\BlackList;
use App\Models\Combo;
use App\Models\ComboTest;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;
use SebastianBergmann\Diff\Exception;

class AndroidTestController extends Controller
{

    public function index(): int
    {
        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            return 400;
        } else {
            return 200;
        }
    }
    public function version()
    {
        $response_error["resp_result"] = 200;
        $response_error["message"] = "2.714";

        return  response($response_error, 200)
            ->header('Content-Type', 'json');
    }

    private function checkDomain($domain): bool
    {
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return false;
        }
        $curlInit = curl_init($domain);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response) {
            return true;
        }
        return false;
    }

    public function startIP()
    {
        if (self::connectAPI() == 'http://31.43.107.151:7303') {
            IPController::getIP('/android/PAS2/startPage');
        } else {
            IPController::getIP('/android/PAS1/startPage');
        }
    }

    public function connectAPI(): string
    {
        $subject = 'Отсутствует доступ к серверу.';

        /**
         * тест
         */

        IPController::getIP('/android/PAS2');
        $connectAPI = 'http://31.43.107.151:7303';
        $server0 = $connectAPI;

        /**
         * ПАС1
         */
//        IPController::getIP('/android/PAS1');
//        $server0 = config('app.taxi2012Url_0');
//        $server1 = config('app.taxi2012Url_1');
//        $server2 = config('app.taxi2012Url_2');
//        $server3 = config('app.taxi2012Url_3');

        $url = "/api/time";
        $alarmMessage = new TelegramController();

        if (self::checkDomain($server0 . $url)) {
            return $server0;
        } else return 400;
//        else {
//            try {
//                $alarmMessage->sendAlarmMessage("Отключен " . $server0);
//            } catch (Exception $e) {
//                $subject = 'Ошибка в телеграмм';
//                $paramsCheck = [
//                    'subject' => $subject,
//                    'message' => $e,
//                ];
//                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
//            };
//
//            if (self::checkDomain($server1 . $url)) {
//                $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
//                    "Произведено подключение к серверу " . $server1 . ".";
//                $paramsAdmin = [
//                    'subject' => $subject,
//                    'message' => $messageAdmin,
//                ];
//
//                try {
//                    $alarmMessage->sendAlarmMessage($messageAdmin);
//                } catch (Exception $e) {
//                    $subject = 'Ошибка в телеграмм';
//                    $paramsCheck = [
//                        'subject' => $subject,
//                        'message' => $e,
//                    ];
//                    Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
//                };
//
//                Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
//                Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
//                return $server1;
//            } else {
//                if (self::checkDomain($server2 . $url)) {
//                    $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
//                        "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
//                        "Произведено подключение к серверу " . $server2 . ".";
//                    $paramsAdmin = [
//                        'subject' => $subject,
//                        'message' => $messageAdmin,
//                    ];
//                    try {
//                        $alarmMessage->sendAlarmMessage($messageAdmin);
//                    } catch (Exception $e) {
//                        $subject = 'Ошибка в телеграмм';
//                        $paramsCheck = [
//                            'subject' => $subject,
//                            'message' => $e,
//                        ];
//                        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsCheck));
//                    };
//                    Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
//                    Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
//                    return $server2;
//                } else {
//                    if (self::checkDomain($server3 . $url)) {
//                        $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
//                            "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
//                            "Ошибка подключения к серверу " . $server2 . ".   " . PHP_EOL .
//                            "Произведено подключение к серверу " . $server3 . ".";
//                        $paramsAdmin = [
//                            'subject' => $subject,
//                            'message' => $messageAdmin,
//                        ];
//                        try {
//                            $alarmMessage->sendAlarmMessage($messageAdmin);
//                        } catch (Exception $e) {
//                            $subject = 'Ошибка в телеграмм';
//                            $paramsCheck = [
//                                'subject' => $subject,
//                                'message' => $e,
//                            ];
//                            Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsCheck));
//                        };
//                        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
//                        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
//                        return $server3;
//                    } else {
//                        $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
//                            "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
//                            "Ошибка подключения к серверу " . $server2 . ".   " . PHP_EOL .
//                            "Ошибка подключения к серверу " . $server3 . ".";
//                        $paramsAdmin = [
//                            'subject' => $subject,
//                            'message' => $messageAdmin,
//                        ];
//
//                        $alarmMessage = new TelegramController();
//                        $alarmMessage->sendAlarmMessage($messageAdmin);
//
//                        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
//                        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
//
//                        return '400';
//                    }
//                }
//            }
//        }
    }

    public function costSearch($from, $from_number, $to, $to_number, $tariff, $phone, $user, $services)
    {
//dd($to . "-" . $to_number . "-");
        /**
         * Параметры запроса
         */

        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = null; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');


        if ($from == $to) {
            $route_undefined = true;
            if ($connectAPI == 'http://31.43.107.151:7303') {
                $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
            } else {
                $combos_from = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
            }
            $combos_to = $combos_from;
        } else {
            $route_undefined = false;
            if ($connectAPI == 'http://31.43.107.151:7303') {
                $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
            } else {
                $combos_from = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
            }
        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->name;
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $to = $combos_to->name;
        }

        $routFrom = ['name' => $from, 'number' => $from_number];
        $routTo =   ['name' => $to, 'number' => $to_number];

        $LatLngFrom = self::geoDataSearch($from, $from_number);
        $from_lat = $LatLngFrom["lat"];
        $from_lng =  $LatLngFrom["lng"];

        $LatLngTo = self::geoDataSearch($to, $to_number);
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];

        if ($from_lat != 0 && $from_lng != 0) {
            $routFrom = ['name' => $from, 'lat' => $from_lat, 'lng' => $from_lng];
        }

        if ($to_lat != 0 && $to_lng != 0) {
            $routTo = ['name' => $to,  'lat' => $to_lat, 'lng' => $to_lng];
        }
        $rout  = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            $routFrom,
            $routTo,
        ];
//dd($rout);
        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = $from;
        $params['from_number'] = $from_number;
        $params['to'] = $to;
        $params['to_number'] = $to_number;
        self::saveCoast($params);

        $url = $connectAPI . '/api/weborders/cost';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
//        $response = Http::dd()->withHeaders([
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "Оператору набрать заказчика и согласовать весь заказ", //Комментарий к заказу
            'add_cost' => 0,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response);
        if ($response->status() == 200) {
            return  response($response, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function orderSearch($from, $from_number, $to, $to_number, $tariff, $phone, $user, $add_cost, $time, $comment, $date, $services)
    {

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;
        $params['client_sub_card'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $taxiColumnId = config('app.taxiColumnId');

        $params["from"] = $from;
        $params["from_number"] = $from_number;
        $params["routefromnumber"] =  $from_number;

        $params["to"] = $to;
        $params["to_number"] = $to_number;

        if ($from == $to) {
            $route_undefined = true;
        } else {
            $route_undefined = false;
        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        if ($from == $to) {
            $route_undefined = true;
            if ($connectAPI == 'http://31.43.107.151:7303') {
                $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
            } else {
                $combos_from = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
            }
            $combos_to = $combos_from;
        } else {
            $route_undefined = false;
            if ($connectAPI == 'http://31.43.107.151:7303') {
                $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
            } else {
                $combos_from = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
            }
        }

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->name;
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $to = $combos_to->name;
        }

        $routFrom = ['name' => $from, 'number' => $from_number];
        $routTo =   ['name' => $to, 'number' => $to_number];

        $LatLngFrom = self::geoDataSearch($from, $from_number);
        $from_lat = $LatLngFrom["lat"];
        $from_lng =  $LatLngFrom["lng"];

        $LatLngTo = self::geoDataSearch($to, $to_number);
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];

        if ($from_lat != 0 && $from_lng != 0) {
            $routFrom = ['name' => $from, 'lat' => $from_lat, 'lng' => $from_lng];
        }

        if ($to_lat != 0 && $to_lng != 0) {
            $routTo = ['name' => $to,  'lat' => $to_lat, 'lng' => $to_lng];
        }
        $rout  = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            $routFrom,
            $routTo,
        ];

        $required_time =  null; //Время подачи предварительного заказа
        $reservation = false; //Обязательный. Признак предварительного заказа: True, False
        if ($time != "no_time") {
            $todayDate = strtotime($date);
            $todayDate = date("Y-m-d", $todayDate);
            list($hours, $minutes) = explode(":", $time);
            $required_time = $todayDate . "T" . str_pad($hours, 2, '0', STR_PAD_LEFT) . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
            $reservation = true; //Обязательный. Признак предварительного заказа: True, False
        }

        $params['reservation'] = $reservation;

        $params["required_time"] = $required_time;


        if ($comment == "no_comment") {
            $comment =  "Оператору набрать заказчика и согласовать весь заказ";
        }

        $url = $connectAPI . '/api/weborders';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            $params["order_cost"] = $response_arr["order_cost"];
            $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
            $params['server'] = $connectAPI;
            self::saveOrder($params);

            $LatLng = self::geoDataSearch($from, $from_number);
            $response_ok["from_lat"] = $LatLng["lat"];
            $response_ok["from_lng"] =  $LatLng["lng"];

            $LatLng = self::geoDataSearch($to, $to_number);
            $response_ok["lat"] = $LatLng["lat"];
            $response_ok["lng"] =  $LatLng["lng"];

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $add_cost;
//            $response_ok["recommended_add_cost"] = $response_arr["recommended_add_cost"];
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["discount_trip"] = $response_arr["discount_trip"];
//            $response_ok["find_car_timeout"] = $response_arr["find_car_timeout"];
//            $response_ok["find_car_delay"] = $response_arr["find_car_delay"];

//            $response_ok["route_address_from"] = $response_arr["route_address_from"];

            $response_ok["routefrom"] =  $from;
            $response_ok["routefromnumber"] =   $from_number;

            $response_ok["routeto"] =  $to;
            $response_ok["to_number"] =   $to_number;

//            $response_ok["route_address_to"] = $response_arr["route_address_to"];

            return  response($response_ok, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];
            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function costSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff, $phone, $user, $services)
    {

        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = null; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        if ($originLatitude == $to) {
            $route_undefined = true;
            $params['route_undefined'] = $route_undefined; //По городу: True, False

            $params['to'] = 'по місту';
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ]
            ];

        } else {
            $route_undefined = false;
            if ($connectAPI == 'http://31.43.107.151:7303') {
                $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
            } else {
                $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
            }
            if ($combos_to == null) {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Не вірна адреса";

                return  response($response_error, 200)
                    ->header('Content-Type', 'json');
            } else {
                $to = $combos_to->name;
            }

            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to;
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $to, 'number' => $to_number]
                ];
        }

        $params['from'] = "lat: " . $originLatitude . " lon: " . $originLongitude;
        $params['from_number'] = " ";
        $params['to_number'] = $to_number;

        /**
         * Сохранние расчетов в базе
         */

        self::saveCoast($params);

        $url = $connectAPI . '/api/weborders/cost';
        if ($connectAPI == 'http://31.43.107.151:7303') {
                $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };

        $add_cost = 0;
        $response = Http::withHeaders([
             'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "Оператору набрать заказчика и согласовать весь заказ", //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' =>$extra_charge_codes,
            //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);

            $response_ok["from_lat"] = 0;
            $response_ok["from_lng"] =  0;
            $response_ok["lat"] = 0;
            $response_ok["lng"] = 0;

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $response_arr["add_cost"];
            $response_ok["recommended_add_cost"] = $response_arr["recommended_add_cost"];
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["discount_trip"] = $response_arr["discount_trip"];

            if ($originLatitude != $to) {
                $response_ok["routeto"] =  $to;
                $response_ok["to_number"] =   $to_number;
            } else {
                $response_ok["routeto"] = $originLatitude;
                $response_ok["to_number"] = " ";
            }
            $response_ok["routefrom"] = $originLatitude;
            $response_ok["routefromnumber"] = " ";
//            dd($response_ok);

            return  response($response_ok, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function orderSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff, $phone, $user, $add_cost, $time, $comment, $date, $services)
    {

        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;
        $params['client_sub_card'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */
        $visicom = VisicomController::showLatLng($originLatitude, $originLongitude);
        $from = "Місце відправлення";
        $params['from_number'] = " ";

        if ($visicom == 404) {
            $r = 50;
            do {
                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                    . $originLongitude
                    . "," . $originLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");

                $response = Http::get($url);
                $response_arr_from = json_decode($response, true);
                $r += 50;
            } while (empty($response_arr_from) && $r < 200);

            if ($response_arr_from != null) {
                $params['routefromnumber'] = $response_arr_from["properties"]["name"];

                $from = $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", буд." . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"];

                $request["street_type"] = $response_arr_from["properties"]["street_type"];
                $request["street"] = $response_arr_from["properties"]["street"];
                $request["name"] = $response_arr_from["properties"]["name"];
                $request["settlement_type"] = $response_arr_from["properties"]["settlement_type"];
                $request["settlement"] = $response_arr_from["properties"]["settlement"];
                $request["lat"] = $originLatitude;
                $request["lng"] = $originLongitude;
                $params['from_number'] = $response_arr_from["properties"]["name"];
                VisicomController::store($request);
            }
        } else {
            $params['routefromnumber'] = $visicom["name"];

            $from = $visicom["street_type"]
                . $visicom["street"]
                . ", буд." . $visicom["name"]
                . ", " . $visicom["settlement_type"]
                . " " . $visicom["settlement"];
            $params['from_number'] = $visicom["name"];
        }



        $params["from"] = $from;
        $params['routefrom'] = $from;
        $params["to_number"] = $to_number;

        if ($originLatitude == $to) {
            $route_undefined = true;
            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = 'по місту';

            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ]
            ];

        } else {
            $route_undefined = false;
            if (self::connectAPI() == 'http://31.43.107.151:7303') {
                $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
            } else {
                $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
            }
            if ($combos_to == null) {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Не вірна адреса";

                return  response($response_error, 200)
                    ->header('Content-Type', 'json');
            } else {
                $to = $combos_to->name;
            }

            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to;
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $to, 'number' => $to_number]
            ];
        }


        $required_time =  null; //Время подачи предварительного заказа
        $reservation = false; //Обязательный. Признак предварительного заказа: True, False
        if ($time != "no_time") {

            $todayDate = strtotime($date);
            $todayDate = date("Y-m-d", $todayDate);
            list($hours, $minutes) = explode(":", $time);
            $required_time = $todayDate . "T" . str_pad($hours, 2, '0', STR_PAD_LEFT) . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
            $reservation = true; //Обязательный. Признак предварительного заказа: True, False
        }

        $params['reservation'] = $reservation;

        $params["required_time"] = $required_time;


        if ($comment == "no_comment") {
            $comment =  "Оператору набрать заказчика и согласовать весь заказ";
        }

        $url = $connectAPI . '/api/weborders';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $response = Http::withHeaders([
//            $response = Http::dd()->withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' =>$rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' =>   $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if ($response_arr["order_cost"] != 0) {
//                dd($response_arr);
                $params["order_cost"] = $response_arr["order_cost"];
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $params['server'] = $connectAPI;
                self::saveOrder($params);
                if ($route_undefined == false) {
                    $LatLng = self::geoDataSearch($to, $to_number);
                    $response_ok["lat"] = $LatLng["lat"];
                    $response_ok["lng"] =  $LatLng["lng"];
                } else {
                    $response_ok["lat"] = $originLatitude;
                    $response_ok["lng"] =  $originLongitude;
                }


                $response_ok["from_lat"] = $originLatitude;
                $response_ok["from_lng"] =  $originLongitude;



                $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
                $response_ok["order_cost"] = $response_arr["order_cost"];
                $response_ok["add_cost"] = $add_cost;
                $response_ok["recommended_add_cost"] = $add_cost;
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["discount_trip"] = $response_arr["discount_trip"];

                $response_ok["routefrom"] = $params['routefrom'];
                $response_ok["routefromnumber"] = $params['routefromnumber'];


                $response_ok["routeto"] = $params['to'];
                $response_ok["to_number"] = $to_number;
//                dd($response_ok);
                return response($response_ok, 200)
                    ->header('Content-Type', 'json');
            }
            if ($response_arr["order_cost"] == 0) {
                $response_arr = json_decode($response, true);

                $response_error["order_cost"] = "0";
                $response_error["Message"] = $response_arr["Message"];

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
            if ($response_arr["order_cost"] == null) {
                $response_arr = json_decode($response, true);

                $response_error["order_cost"] = "0";
                $response_error["Message"] = $response_arr["Message"];
//                dd("222");
                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
//            dd($response_arr);
            $response_error["order_cost"] = "0";
            $response_error["Message"] = $response_arr["Message"];
//            dd($response_error);
            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function costSearchMarkers($originLatitude, $originLongitude, $toLatitude, $toLongitude, $tariff, $phone, $user, $services)
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();


        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = null; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');
        $to_geo = "Місце призначення";
        if ($originLatitude == $toLatitude) {
            $route_undefined = true;

            $params['to'] = 'по місту';

        } else {
            $route_undefined = false;

            $visicom = VisicomController::showLatLng($toLongitude, $toLatitude);

            if ($visicom == 404) {
                $r = 50;
                do {
                    $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                        . $toLongitude
                        . "," . $toLatitude
                        . "&r=" . $r . "&l=1&key="
                        . config("app.keyVisicom");

                    $response = Http::get($url);
                    $response_arr_to = json_decode($response, true);
                    $r += 50;
                } while (empty($response_arr_to) && $r < 200);


                if ($response_arr_to != null) {
                    $params['routetonumber'] = $response_arr_to["properties"]["name"];
                    $to = $response_arr_to["properties"]["street_type"]
                        . $response_arr_to["properties"]["street"]
                        . ", буд." . $response_arr_to["properties"]["name"]
                        . ", " . $response_arr_to["properties"]["settlement_type"]
                        . " " . $response_arr_to["properties"]["settlement"];
                    $request["street_type"] = $response_arr_to["properties"]["street_type"];
                    $request["street"] = $response_arr_to["properties"]["street"];
                    $request["name"] = $response_arr_to["properties"]["name"];
                    $request["settlement_type"] = $response_arr_to["properties"]["settlement_type"];
                    $request["settlement"] = $response_arr_to["properties"]["settlement"];
                    $request["lat"] = $originLatitude;
                    $request["lng"] = $originLongitude;

                    VisicomController::store($request);

                } else {
                    $to = "Місце призначення";
                }
            } else {
                $params['routefromnumber'] = $visicom["name"];

                $to = $visicom["street_type"]
                    . $visicom["street"]
                    . ", буд." . $visicom["name"]
                    . ", " . $visicom["settlement_type"]
                    . " " . $visicom["settlement"];
            }


        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
            ['name' => "name", 'lat' => $toLatitude, 'lng' => $toLongitude]
        ];
        $params['route_undefined'] = $route_undefined; //По городу: True, False


        /**
         * Сохранние расчетов в базе
         */
        $addressFrom = self::geoLatLanSearch($originLatitude, $originLongitude);
        if ($addressFrom['name'] != "name") {
            $params['from'] = $addressFrom['name'];
            $params['from_number'] = $addressFrom['house'];
        } else {
            $params['from'] = 'Місце відправлення';
            $params['from_number'] = ' ';
        }

        $addressTo = self::geoLatLanSearch($toLatitude, $toLongitude);
        if ($addressTo['name'] != "name") {
            $params['to'] = $addressTo['name'];
            $params['to_number'] = $addressTo['house'];
        } else {
            $params['to'] = 'Місце призначення';
            $params['to_number'] = " ";
        }
        self::saveCoast($params);

        $url = $connectAPI . '/api/weborders/cost';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $add_cost = 0;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "Оператору набрать заказчика и согласовать весь заказ", //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);

            $response_ok["from_lat"] = $originLatitude;
            $response_ok["from_lng"] =  $originLongitude;

            if ($route_undefined == false) {
                $response_ok["lat"] = $toLatitude;
                $response_ok["lng"] = $toLongitude;
            } else {
                $response_ok["lat"] = 0;
                $response_ok["lng"] = 0;
            }

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $response_arr["add_cost"];
            $response_ok["recommended_add_cost"] = $response_arr["recommended_add_cost"];
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["discount_trip"] = $response_arr["discount_trip"];

            if ($originLatitude != $toLatitude) {
                $response_ok["routeto"] =  $params["to"];
                $response_ok["to_number"] = $params["to_number"];
            } else {
                $response_ok["routeto"] = $toLatitude;
                $response_ok["to_number"] = " ";
            }
            $response_ok["routefrom"] = $originLatitude;
            $response_ok["routefromnumber"] = " ";

            return  response($response_ok, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function orderSearchMarkers($originLatitude, $originLongitude, $toLatitude, $toLongitude, $tariff, $phone, $user, $add_cost, $time, $comment, $date, $services)
    {

        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;
        $params['client_sub_card'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */

        $visicom = VisicomController::showLatLng($originLatitude, $originLongitude);
        $from = "Місце відправлення";
        $params['from_number'] = " ";
        if ($visicom == 404) {
            $r = 50;
            do {
                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                    . $originLongitude
                    . "," . $originLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");

                $response = Http::get($url);
                $response_arr_from = json_decode($response, true);
                $r += 50;
            } while (empty($response_arr_from) && $r < 200);

            if ($response_arr_from != null) {
                $params['routefromnumber'] = $response_arr_from["properties"]["name"];

                $from = $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", буд." . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"];
                $params['from_number'] = $response_arr_from["properties"]["name"];

                $request["street_type"] = $response_arr_from["properties"]["street_type"];
                $request["street"] = $response_arr_from["properties"]["street"];
                $request["name"] = $response_arr_from["properties"]["name"];
                $request["settlement_type"] = $response_arr_from["properties"]["settlement_type"];
                $request["settlement"] = $response_arr_from["properties"]["settlement"];
                $request["lat"] = $originLatitude;
                $request["lng"] = $originLongitude;

                VisicomController::store($request);
            }
        } else {
            $params['routefromnumber'] = $visicom["name"];

            $from = $visicom["street_type"]
                . $visicom["street"]
                . ", буд." . $visicom["name"]
                . ", " . $visicom["settlement_type"]
                . " " . $visicom["settlement"];
            $params['from_number'] = $visicom["name"];
        }
        $params['routefrom'] = $from;

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['to'] = 'по місту';
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];

        } else {
            $route_undefined = false;
            $visicom = VisicomController::showLatLng($toLongitude, $toLatitude);

            if ($visicom == 404) {
                $r = 50;
                do {
                    $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                        . $toLongitude
                        . "," . $toLatitude
                        . "&r=" . $r . "&l=1&key="
                        . config("app.keyVisicom");

                    $response = Http::get($url);
                    $response_arr_to = json_decode($response, true);
                    $r += 50;
                } while (empty($response_arr_to) && $r < 200);


                if ($response_arr_to != null) {
                    $params['routetonumber'] = $response_arr_to["properties"]["name"];
                    $to = $response_arr_to["properties"]["street_type"]
                        . $response_arr_to["properties"]["street"]
                        . ", буд." . $response_arr_to["properties"]["name"]
                        . ", " . $response_arr_to["properties"]["settlement_type"]
                        . " " . $response_arr_to["properties"]["settlement"];
                    $request["street_type"] = $response_arr_to["properties"]["street_type"];
                    $request["street"] = $response_arr_to["properties"]["street"];
                    $request["name"] = $response_arr_to["properties"]["name"];
                    $request["settlement_type"] = $response_arr_to["properties"]["settlement_type"];
                    $request["settlement"] = $response_arr_to["properties"]["settlement"];
                    $request["lat"] = $originLatitude;
                    $request["lng"] = $originLongitude;

                    VisicomController::store($request);

                } else {
                    $to = "Місце призначення";
                }
            } else {
                $params['routefromnumber'] = $visicom["name"];

                $to = $visicom["street_type"]
                    . $visicom["street"]
                    . ", буд." . $visicom["name"]
                    . ", " . $visicom["settlement_type"]
                    . " " . $visicom["settlement"];
            }
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $to, 'lat' => $toLatitude, 'lng' => $toLongitude]
            ];
        }

        $params['route_undefined'] = $route_undefined; //По городу: True, False


        $addressFrom = self::geoLatLanSearch($originLatitude, $originLongitude);
        if ($addressFrom['name'] != "name") {
            $params['from'] = $addressFrom['name'];
            $params['from_number'] = $addressFrom['house'];
        } else {
            $params['from'] = 'Місце відправлення';
            $params['from_number'] = ' ';
        }

        $addressTo = self::geoLatLanSearch($toLatitude, $toLongitude);
        if ($addressTo['name'] != "name") {
            $params['to'] = $addressTo['name'];
            $params['to_number'] = $addressTo['house'];
        } else {
            $params['to'] = 'Місце призначення';
            $params['to_number'] = " ";
        }

        $required_time =  null; //Время подачи предварительного заказа
        $reservation = false; //Обязательный. Признак предварительного заказа: True, False
        if ($time != "no_time") {
            $todayDate = strtotime($date);
            $todayDate = date("Y-m-d", $todayDate);
            list($hours, $minutes) = explode(":", $time);
            $required_time = $todayDate . "T" . str_pad($hours, 2, '0', STR_PAD_LEFT) . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
            $reservation = true; //Обязательный. Признак предварительного заказа: True, False
        }

        $params['reservation'] = $reservation;

        $params["required_time"] = $required_time;


        if ($comment == "no_comment") {
            $comment =  "Оператору набрать заказчика и согласовать весь заказ";
        }

        $url = $connectAPI . '/api/weborders';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if ($response_arr["order_cost"] != 0) {

                $params["order_cost"] = $response_arr["order_cost"];
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $params['server'] = $connectAPI;
                self::saveOrder($params);

                $response_ok["from_lat"] = $originLatitude;
                $response_ok["from_lng"] =  $originLongitude;

                $response_ok["lat"] = $toLatitude;
                $response_ok["lng"] =  $toLongitude;

                $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
                $response_ok["order_cost"] = $response_arr["order_cost"];
                $response_ok["add_cost"] = $add_cost;
                $response_ok["recommended_add_cost"] = $add_cost;
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["discount_trip"] = $response_arr["discount_trip"];

                $response_ok["routefrom"] = $params['from'];
                $response_ok["routefromnumber"] = $params['from_number'];

                $response_ok["routeto"] = $params['to'];
                $response_ok["to_number"] = $params['to_number'];
//                $response_ok["route_address_from"] = $from;
//                $response_ok["route_address_to"] = " ";
//                dd($response_ok);
                return response($response_ok, 200)
                    ->header('Content-Type', 'json');
            }
            if ($response_arr["order_cost"] == 0) {
                $response_arr = json_decode($response, true);

                $response_error["order_cost"] = "0";
                $response_error["Message"] = $response_arr["Message"];
//                dd("111");
                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
            if ($response_arr["order_cost"] == null) {
                $response_arr = json_decode($response, true);

                $response_error["order_cost"] = "0";
                $response_error["Message"] = $response_arr["Message"];
//                dd("222");
                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
//            dd($response_arr);
            $response_error["order_cost"] = "0";
            $response_error["Message"] = $response_arr["Message"];
//            dd("333");
            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function saveCoast($params)
    {
        /**
         * Сохранние расчетов в базе
         */

        $order = new Order();
        $order->IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
        $order->user_full_name = $params['user_full_name'];//Полное имя пользователя
        $order->user_phone = $params['user_phone'];//Телефон пользователя
        $order->client_sub_card = null;
        $order->required_time = $params['required_time']; //Время подачи предварительного заказа
        $order->reservation = $params['reservation']; //Обязательный. Признак предварительного заказа: True, False
        $order->route_address_entrance_from = null;
        $order->comment = $params['comment'];  //Комментарий к заказу
        $order->add_cost = $params['add_cost']; //Добавленная стоимость
        $order->wagon = $params['wagon']; //Универсал: True, False
        $order->minibus = $params['minibus']; //Микроавтобус: True, False
        $order->premium = $params['premium']; //Машина премиум-класса: True, False
        $order->flexible_tariff_name = $params['flexible_tariff_name']; //Гибкий тариф
        $order->route_undefined = $params['route_undefined']; //По городу: True, False
        $order->routefrom = $params['from']; //Обязательный. Улица откуда.
        $order->routefromnumber = $params['from_number']; //Обязательный. Дом откуда.
        $order->routeto = $params['to']; //Обязательный. Улица куда.
        $order->routetonumber = $params['to_number']; //Обязательный. Дом куда.
        $order->taxiColumnId = $params['taxiColumnId']; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->save();

    }

    public function saveOrder($params)
    {

        /**
         * Сохранние расчетов в базе
         */
        $order = new Orderweb();

        $order->user_full_name = $params["user_full_name"];//Полное имя пользователя
        $order->user_phone = $params["user_phone"];//Телефон пользователя
        $order->client_sub_card = null;
        $order->required_time = $params["required_time"]; //Время подачи предварительного заказа
        $order->reservation = $params["reservation"]; //Обязательный. Признак предварительного заказа: True, False
        $order->route_address_entrance_from = null;
        $order->comment = $params["comment"];  //Комментарий к заказу
        $order->add_cost = $params["add_cost"]; //Добавленная стоимость
        $order->wagon = $params["wagon"]; //Универсал: True, False
        $order->minibus = $params["minibus"]; //Микроавтобус: True, False
        $order->premium = $params["premium"]; //Машина премиум-класса: True, False
        $order->flexible_tariff_name = $params["flexible_tariff_name"]; //Гибкий тариф
        $order->route_undefined = $params["route_undefined"]; //По городу: True, False
        $order->routefrom = $params["from"]; //Обязательный. Улица откуда.
        $order->routefromnumber = $params["from_number"]; //Обязательный. Дом откуда.
        $order->routeto = $params["to"]; //Обязательный. Улица куда.
        $order->routetonumber = $params["to_number"]; //Обязательный. Дом куда.
        $order->taxiColumnId = $params["taxiColumnId"]; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->web_cost = $params['order_cost'];
        $order->dispatching_order_uid = $params['dispatching_order_uid'];
        $order->server = $params['server'];

        $order->save();

        /**
         * Сообщение о заказе
         */
//        dd($params);

        if (!$params["route_undefined"]) {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " до "  . $params['to'] . " " . $params['to_number'] .
                ". Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'];
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " по місту. Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'];
        }

        $subject = 'Інформація про нову поїздку:';
        $paramsCheck = [
            'subject' => $subject,
            'message' => $order,
        ];


        Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        $message = new TelegramController();
        try {
            $message->sendMeMessage($order);
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];

            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };
    }

    public function sendCode($phone)
    {

        $url = self::connectApi() . '/api/approvedPhones/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => substr($phone, 3), //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => config('app.taxiColumnId') //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_status["resp_result"] = 200;
            return  response($response_status, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["resp_result"] = 400;
            $response_error["message"] = $response_arr["Message"];
//            $response_error["message"] = "Message";

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function geoDataSearch($to, $to_number): array
    {
        if ($to_number != " ") {
            $LatLng = self::geoDataSearchStreet($to, $to_number);
        } else {
            $LatLng = self::geoDataSearchObject($to);
        }

        return $LatLng;
    }
    public function geoDataSearchStreet($to, $to_number): array
    {

        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();


        $url = $connectAPI . '/api/geodata/search';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->get($url, [
            'q' => $to, //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 1, //Кол-во возвращаемых записей (предел).
            'transliteration' => true, //Разрешить транслитерацию запроса при поиске.
            'qwertySwitcher' => true, //Разрешить преобразование строки запроса в случае ошибочного набора с неверной раскладкой клавиатуры (qwerty). Например, «ghbdtn» - это «привет».
            'fields' => '*', /*Данным параметром можно указать перечень требуемых параметров, которые будут возвращаться в ответе. Разделяются запятой.
                Возможные значения:
                * (возвращает все поля)
                name
                old_name
                houses
                lat
                lng
                locale*/
        ]);
        $response_arr = json_decode($response, true);
//dd($response_arr);
        $LatLng["lat"] = 0;
        $LatLng["lng"] = 0;
        if ((strncmp($to_number, " ", 1) != 0)) {
            if (isset($response_arr["geo_streets"]["geo_street"][0]["houses"])) {
                foreach ($response_arr["geo_streets"]["geo_street"][0]["houses"] as $value) {
                    if ($value['house'] ==  trim($to_number)) {
                        $LatLng["lat"] = $value["lat"];
                        $LatLng["lng"] = $value["lng"];
                        break;
                    }
                }
            }
        }

        return $LatLng;
    }
    public function geoDataSearchObject($to): array
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();


        $url = $connectAPI . '/api/geodata/objects/search';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->get($url, [
            'q' => $to, //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 1, //Кол-во возвращаемых записей (предел).
            'transliteration' => true, //Разрешить транслитерацию запроса при поиске.
            'qwertySwitcher' => true, //Разрешить преобразование строки запроса в случае ошибочного набора с неверной раскладкой клавиатуры (qwerty). Например, «ghbdtn» - это «привет».
            'fields' => '*', /*Данным параметром можно указать перечень требуемых параметров, которые будут возвращаться в ответе. Разделяются запятой.
                Возможные значения:
                * (возвращает все поля)
                name
                old_name
                houses
                lat
                lng
                locale*/
        ]);
        $response_arr = json_decode($response, true);
        $LatLng["lat"] = 0;
        $LatLng["lng"] = 0;

        if (isset($response_arr["geo_object"][0]["name"])) {
            $LatLng["lat"] = $response_arr["geo_object"][0]["lat"];
            $LatLng["lng"] = $response_arr["geo_object"][0]["lng"];
        }

//        dd($LatLng);
        return $LatLng;
    }
    public function fromSearchGeo($originLatitude, $originLongitude)
    {

        $visicom = VisicomController::showLatLng($originLatitude, $originLongitude);
        $addressArr = self::geoLatLanSearch($originLatitude, $originLongitude);

        $response_ok["name"] = $addressArr['name'];
        $response_ok["house"] = $addressArr['house'];

        if ($visicom == 404) {
            $r = 50;
            do {
                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                    . $originLongitude
                    . "," . $originLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");

                $response = Http::get($url);
                $response_arr_from = json_decode($response, true);
                $r += 50;
            } while (empty($response_arr_from) && $r < 200);

            if ($response_arr_from != null) {
                $from = $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", буд." . $response_arr_from["properties"]["name"]
                    . " (" . $response_arr_from["properties"]["settlement"] . ")";

                $response_ok["order_cost"] = 100;
                $response_ok["route_address_from"] = $from;



                $request["street_type"] = $response_arr_from["properties"]["street_type"];
                $request["street"] = $response_arr_from["properties"]["street"];
                $request["name"] = $response_arr_from["properties"]["name"];
                $request["settlement_type"] = $response_arr_from["properties"]["settlement_type"];
                $request["settlement"] = $response_arr_from["properties"]["settlement"];
                $request["lat"] = $originLatitude;
                $request["lng"] = $originLongitude;

                VisicomController::store($request);

                return  response($response_ok, 200)
                    ->header('Content-Type', 'json');
            } else {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Помилка гоепошуку. Спробуйте вказати місце з бази адрес.";
                return  response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $from =  $visicom["street_type"]
                . $visicom["street"]
                . ", буд." . $visicom["name"]
                . ", " . $visicom["settlement_type"]
                . " " . $visicom["settlement"];

            $response_ok["order_cost"] = 100;
            $response_ok["route_address_from"] = $from;
//            dd($response_ok);
            return  response($response_ok, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function geoLatLanSearch($originLatitude, $originLongitude): array
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        $authorization = self::autorization();


        $url = $connectAPI . '/api/geodata/nearest';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $r = 50;
        do {
            $response = Http::withHeaders([
                'Authorization' => $authorization,
                "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
                "X-API-VERSION" => self::apiVersion()
            ])->get($url, [
                'lat' => $originLatitude,
                'lng' => $originLongitude,
                'r' => $r,
            ]);
            $r += 50;
            $response_arr = json_decode($response, true);
        } while (empty($response_arr) && $r < 200);

        $addressArr['name'] = "name";
        $addressArr['house'] =  "house";

        if ($response_arr["geo_streets"]["geo_street"] != null) {
            $addressArr['name'] =  $response_arr["geo_streets"]["geo_street"][0]["name"];
            $addressArr['house'] =  $response_arr["geo_streets"]["geo_street"][0]["houses"][0]["house"];
        }
        if ($response_arr["geo_objects"]["geo_object"] != null) {
            $addressArr['name'] =  $response_arr["geo_objects"]["geo_object"][0]["name"];
            $addressArr['house'] =  " ";
        }
//dd($addressArr);
        return  $addressArr;
    }

    public function approvedPhones($phone, $confirm_code)
    {


        $url = self::connectApi() . '/api/approvedPhones/';
        $response = Http::post($url, [
            'phone' => substr($phone, 3), //Обязательный. Номер мобильного телефона
            'confirm_code' => $confirm_code //Обязательный. Код подтверждения.
        ]);

        if ($response->status() == 200) {
            $response_status["resp_result"] = 200;
            return  response($response_status, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["resp_result"] = 0;
            $response_error["message"] = $response_arr["Message"];

            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function sendCodeTest($phone)
    {
        $response_status["resp_result"] = 200;
        return  response($response_status, 200)
            ->header('Content-Type', 'json');
    }

    public function approvedPhonesTest($phone, $confirm_code)
    {
        $response_status["resp_result"] = 200;
        return  response($response_status, 200)
            ->header('Content-Type', 'json');
    }

    public function autocompleteSearchComboHid($name)
    {
        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["resp_result"] = 200;
            $response_error["message"] = 200;

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            if ($connectAPI == 'http://31.43.107.151:7303') {
                 $combos = ComboTest::where('name', 'like', $name . '%')->first();
            } else {
                 $combos = Combo::where('name', 'like', $name . '%')->first();
            }
            if ($combos != null) {
                $response["resp_result"] = 0;
                $response["message"] = $combos->street;
                return  response($response, 200)
                    ->header('Content-Type', 'json');
            } else {
                $response["resp_result"] = 400;
                $response["message"] = 400;

                return response($response, 200)
                    ->header('Content-Type', 'json');
            }
        }
    }

    public function sentPhone(string $message)
    {

        $subject = "Ожидает звонка";

        $messageAdmin = "Клиент просит о помощи. Его телефон для связи $message.";
        $paramsAdmin = [
            'subject' => $subject,
            'message' => $messageAdmin,
        ];

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];

            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };

        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
    }

    public function addUser($name, $email)
    {
        $newUser =  User::where('email', $email)->first();

        if ($newUser == null) {
            $newUser = new User();
            $newUser->name = $name;
            $newUser->email = $email;
            $newUser->password = "123245687";

            $newUser->facebook_id = null;
            $newUser->google_id = null;
            $newUser->linkedin_id = null;
            $newUser->github_id = null;
            $newUser->twitter_id = null;
            $newUser->telegram_id = null;
            $newUser->viber_id = null;
            $newUser->save();
        }
    }

    public function verifyBlackListUser($email)
    {
        $user =  BlackList::where('email', $email)->first();

        if ($user == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не черном списке";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "В черном списке";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    private function autorization()
    {
        $connectAPI = self::connectApi();
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));

        switch ($connectAPI) {
            case 'http://31.43.107.151:7303':
                $username = '0936734488';
                $password = hash('SHA512', '22223344');
                break;
            case config('app.taxi2012Url_0'):
                $username = "SMS_NADO_OTPR";
                $password = hash('SHA512', "fhHk89)_");
                break;
        }
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function myHistory()
    {

        $connectAPI = self::connectApi();


        $url = $connectAPI . '/api/clients/ordershistory';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $authorization = self::autorization();
        $response = Http::withHeaders([
             'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->get($url);
        return $response;
//dd($response->body());
    }
    public function historyUID($uid)
    {

        $connectAPI = self::connectApi();


        $url = $connectAPI . '/api/weborders/';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $url = $url . $uid;
        $authorization = self::autorization();
        $response = Http::withHeaders([
             'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->get($url);
        return $response;
//dd($response->body());
    }
    public function apiVersion()
    {
        $connectAPI = self::connectApi();

        $url = $connectAPI . '/api/version';
        $response = Http::get($url);
        $response_arr = json_decode($response, true);

        return $response_arr["version"];
    }

    /**
     * Запрос отмены заказа клиентом
     * @return string
     */
    public function webordersCancel($uid)
    {
        $connectAPI = self::connectApi();


        $url = $connectAPI . '/api/weborders/';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }

        $authorization = self::autorization();
        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->put($url);



        $json_arrWeb = json_decode($response, true);

        $resp_answer = "Запит на скасування замовлення надіслано. ";

        switch ($json_arrWeb['order_client_cancel_result']) {
            case '0':
                $resp_answer = $resp_answer . "Замовлення не вдалося скасувати. ";
                break;
            case '1':
                $resp_answer = $resp_answer . "Замовлення скасоване. ";
                break;
            case '2':
                $resp_answer = $resp_answer . "Вимагає підтвердження клієнтом скасування диспетчерської. ";
                break;
        }
        return response()->json(['response' => $resp_answer]);
    }
    public function historyUIDStatus($uid)
    {

        $connectAPI = self::connectApi();


        $url = $connectAPI . '/api/weborders/';
        if ($connectAPI == 'http://31.43.107.151:7303') {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS2");
        } else {
            $X_WO_API_APP_ID = config("app.X-WO-API-APP-ID-PAS1");
        }
        $url = $url . $uid;
        $authorization = self::autorization();
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => $X_WO_API_APP_ID,
            "X-API-VERSION" => self::apiVersion()
        ])->get($url);
        return $response;

    }
}
