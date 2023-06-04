<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\Combo;
use App\Models\ComboTest;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

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

    private function checkDomain($domain): bool
    {
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return false;
        }
        $curlInit = curl_init($domain);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 12);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response) {
            return true;
//            return false;
        }
        return false;
    }

    public function connectAPI(): string
    {

//        $server1 = config('app.taxi2012Url_1');
//        $server2 = config('app.taxi2012Url_2');
//        $server3 = config('app.taxi2012Url_3');

        IPController::getIP('/android');

        $subject = 'Отсутствует доступ к серверу.';

        /**
         * тест
         */
        $connectAPI = 'http://31.43.107.151:7303';
        $server1 = $connectAPI;
        $server2 = $connectAPI;
        $server3 = $connectAPI;

        $url = "/api/time";

        $url = $server1 . $url;
        if (self::checkDomain($url)) {
            return $server1;
        } else {
            $url = $server2 . $url;
            if (self::checkDomain($url)) {
                $messageAdmin = "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
                    "Произведено подключение к серверу " . $server2 . ".";
                $paramsAdmin = [
                    'subject' => $subject,
                    'message' => $messageAdmin,
                ];

                $alarmMessage = new TelegramController();
                $alarmMessage->sendAlarmMessage($messageAdmin);

                Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
                return $server2;
            } else {
                $url = $server3 . $url;
                if (self::checkDomain($url)) {
                    $messageAdmin = "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
                        "Ошибка подключения к серверу " . $server2 . ".   " . PHP_EOL .
                        "Произведено подключение к серверу " . $server3 . ".";
                    $paramsAdmin = [
                        'subject' => $subject,
                        'message' => $messageAdmin,
                    ];

                    $alarmMessage = new TelegramController();
                    $alarmMessage->sendAlarmMessage($messageAdmin);

                    Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                    Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
                    return $server3;
                } else {
                    $messageAdmin = "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
                        "Ошибка подключения к серверу " . $server2 . ".   " . PHP_EOL .
                        "Ошибка подключения к серверу " . $server3 . ".";
                    $paramsAdmin = [
                        'subject' => $subject,
                        'message' => $messageAdmin,
                    ];

                    $alarmMessage = new TelegramController();
//                    $alarmMessage->sendAlarmMessage($messageAdmin);

//                    Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                    Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

                    return '400';
                }
            }
        }
    }
//    public function costMap($originLatitude, $originLongitude, $destLatitude, $destLongitude, $tariff)
//    {
//        /**
//         * Test
//         */
//        $username = '0936734488';
//        $password = hash('SHA512', '22223344');
//        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
//        $connectAPI = 'http://31.43.107.151:7303';
//        /**
//         * Параметры запроса
//         */
//        $params['lat'] = $originLatitude;
//        $params['lng'] = $originLongitude;
//        $params['lat2'] = $destLatitude;
//        $params['lng2'] = $destLongitude;
//
////        $username = config('app.username');
////        $password = hash('SHA512', config('app.password'));
////        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
//        /**
//         * Откуда
//         */
////        $connectAPI = WebOrderController::connectApi();
////        if ($connectAPI == 400) {
////            return  null;
////        }
//        $url = $connectAPI . '/api/geodata/nearest';
//        $response_from = Http::withHeaders([
//            'Authorization' => $authorization,
//        ])->get($url, [
//            'lat' => $originLatitude, //Обязательный. Широта
//            'lng' => $originLongitude, //Обязательный. Долгота
//            'r' => '500' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.
//        ]);
//        $response_arr_from = json_decode($response_from, true);
////        dd($response_arr_from);
//
//        if ($response_arr_from['geo_streets']['geo_street'] == null && $response_arr_from['geo_objects']['geo_object']  == null) {
//            $response_error["order_cost"] = 0;
//            $response_error["Message"] = "Помилка пошуку місця відправлення";
//            return  response($response_error, 200)
//                ->header('Content-Type', 'json');
//        }
////        $params['routefrom'] = $response_arr_from['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
////        $params['routefromnumber'] = $response_arr_from['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
//        /**
//         * Куда
//         */
//
//        $url = $connectAPI . '/api/geodata/nearest';
//        $response_to = Http::withHeaders([
//            'Authorization' => $authorization,
//        ])->get($url, [
//            'lat' => $params['lat2'], //Обязательный. Широта
//            'lng' => $params['lng2'], //Обязательный. Долгота
//             'r' => '500' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
//        ]);
//        $response_arr_to = json_decode($response_to, true);
////dd($response_arr_to);
//        if ($response_arr_to['geo_streets']['geo_street'] == null && $response_arr_to['geo_objects']['geo_object']  == null) {
//            $response_error["order_cost"] = 0;
//            $response_error["Message"] = "Помилка пошуку місця призначення";
//            return  response($response_error, 200)
//                ->header('Content-Type', 'json');
//        }
//
//        $params['user_full_name'] = "Андроід-користувач";
//        $params['user_phone'] = "user_phone";
//
//        $params['client_sub_card'] = null;
////        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
//        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False
//
//        $reservation = $params['reservation'];
//        $required_time = null;
//        $params['wagon'] = 0;
//        $params['minibus'] = 0;
//        $params['premium'] = 0;
//        $params['route_address_entrance_from'] = null;
//
////        if ($req->wagon == 'on' || $req->wagon == 1) {
////            $params['wagon'] = 1; //Универсал: True, False
////        } else {
////            $params['wagon'] = 0;
////        };
////        if ($req->minibus == 'on' || $req->minibus == 1) {
////            $params['minibus'] = 1; //Микроавтобус: True, False
////        } else {
////            $params['minibus'] = 0;
////        };
////        if ($req->premium == 'on' || $req->premium == 1) {
////            $params['premium'] = 1; //Машина премиум-класса: True, False
////        } else {
////            $params['premium'] = 0;
////        };
//
//        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
//        $params['comment'] = "comment"; //Комментарий к заказу
//        $params['add_cost'] = 0; //Добавленная стоимость
//        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
//
//        $payment_type_info = 'готівка';
//
//        $params['route_undefined'] = false; //По городу: True, False
//
////        if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
////            $params['routeto'] =  $params['routefrom']; //Обязательный. Улица куда.
////            $params['routetonumber'] = $params['routefromnumber']; //Обязательный. Дом куда.
////            $params['route_undefined'] = 1; //По городу: True, False
////        };
//        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
//
////        $json_arr = WebOrderController::tariffs();
//        /**
//         * Проверка адреса назначения
//         */
//
////        if ($response_arr_to['geo_streets']['geo_street'] == null) {
//////                Ошибка адреса назначения
////            $response["order_cost"] = 0;
////            return  response($response, 200)
////                ->header('Content-Type', 'json');
////
////        }
//
//
//                $user_full_name = "Андроід-користувач";
//                $user_phone = "user_phone";
////
////                $from = $params['routefrom'];
////                $from_number = $params['routefromnumber'];
//
////                if (ComboTest::where('name', $from)->first()->street == 0) {
////                    $from_number_info = '';
////                } else {
////                    $from_number_info = "(будинок №$from_number)";
////                };
//
//
//                $taxiColumnId = config('app.taxiColumnId');
//
//                $route_undefined = false;
////                $to = $params['routeto'];
//
////                $to_number = $params['routetonumber'];
////                if ($params['route_undefined'] == 1) {
////                    $route_undefined = true;
////                    $to = $from;
////                    $to_number = $from_number;
////                };
//
////                if (ComboTest::where('name', $to)->first()->street == 0) {
////                    $to_number_info = '';
////                } else {
////                    $to_number_info = "(будинок №$to_number)";
////                };
//
//
////                $connectAPI = WebOrderController::connectApi();
////                if ($connectAPI == 400) {
////
////                    return 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.';
////                }
//                $url = $connectAPI . '/api/weborders/cost';
//                $response = Http::withHeaders([
//                    'Authorization' => $authorization,
//                ])->post($url, [
//                    'user_full_name' => null, //Полное имя пользователя
//                    'user_phone' => null, //Телефон пользователя
//                    'client_sub_card' => null,
//                    'required_time' => null, //Время подачи предварительного заказа
//                    'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
//                    'route_address_entrance_from' => null,
//                    'comment' => "comment", //Комментарий к заказу
//                    'add_cost' => 0,
//                    'wagon' => 0, //Универсал: True, False
//                    'minibus' => 0, //Микроавтобус: True, False
//                    'premium' => 0, //Машина премиум-класса: True, False
//                    'flexible_tariff_name' => $tariff, //Гибкий тариф
//                    'route_undefined' => false, //По городу: True, False
//                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
//
//                        ['name' => 'текст от Визикома', 'lat' => $originLatitude, 'lng' => $originLongitude ],
//                        ['name' => 'текст от Визикома', 'lat' => $destLatitude, 'lng' => $destLongitude ],
////                        ['name' => $from, 'number' => $from_number],
////                        ['name' => $to, 'number' => $to_number],
//                    ],
//                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
//                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
//                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
//                ]);
////dd($response->body());
//        if ($response->status() == 200) {
//            return  response($response, 200)
//                ->header('Content-Type', 'json');
//        } else {
//            $response_arr = json_decode($response, true);
//
//            $response_error["order_cost"] = 0;
//            $response_error["Message"] = $response_arr["Message"];
//
//            return  response($response_error, 200)
//                ->header('Content-Type', 'json');
//        }
//
//    }
//
//    public function orderMap($originLatitude, $originLongitude, $destLatitude, $destLongitude, $tariff, $phone)
//    {
//        /**
//         * Test
//         */
//        $username = '0936734488';
//        $password = hash('SHA512', '22223344');
//        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
//        $connectAPI = 'http://31.43.107.151:7303';
//        $city = ", місто Одеса";
//        /**
//         * Параметры запроса
//         */
//        $params['lat'] = $originLatitude;
//        $params['lng'] = $originLongitude;
//        $params['lat2'] = $destLatitude;
//        $params['lng2'] = $destLongitude;
//
////        $username = config('app.username');
////        $password = hash('SHA512', config('app.password'));
////        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
////        $city = ", місто Київ";
//        /**
//         * Откуда
//         */
//
//        //         $destLatitude, $destLongitude
//        $r = 50;
//        do {
//            $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
//                . $originLongitude
//                . "," . $originLatitude
//                . "&r=" . $r . "&l=1&key="
//                . config("app.keyVisicom");
//
//            $response = Http::get($url);
//            $response_arr_from = json_decode($response, true);
//            $r += 50;
//        } while (empty($response_arr_from) && $r < 200);
//
//        if ($response_arr_from != null) {
//            $from = $response_arr_from["properties"]["street_type"]
//                . $response_arr_from["properties"]["street"]
//                . ", буд." . $response_arr_from["properties"]["name"]
//                . ", " . $response_arr_from["properties"]["settlement_type"]
//                . " " . $response_arr_from["properties"]["settlement"];
//        } else {
//            $url = $connectAPI . '/api/geodata/nearest';
//
//            $response_from = Http::withHeaders([
//                'Authorization' => $authorization,
//            ])->get($url, [
//                'lat' => $originLatitude, //Обязательный. Широта
//                'lng' => $originLongitude, //Обязательный. Долгота
//                /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
//            ]);
//            $response_arr_from = json_decode($response_from, true);
//
//                if ($response_arr_from['geo_streets']['geo_street'] != null) {
//                    $params['routefrom'] = $response_arr_from['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
//                    $params['routefromnumber'] = $response_arr_from['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
//
//                    $from = $params['routefrom'] . ", буд." . $params['routefromnumber']  . $city;
//                } else {
//                    $params['routefrom'] = $response_arr_from['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
//                    $params['routefromnumber'] = null; //Обязательный. Дом откуда.
//                    $from = $params['routefrom'] . $city;
//                }
//
//
//
//        }
//
//         /**
//         * Куда
//         */
//        $r = 50;
//
//        do {
//            $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
//                . $destLongitude
//                . "," . $destLatitude
//                . "&r=" . $r . "&l=1&key="
//                . config("app.keyVisicom");
//
//            $response = Http::get($url);
//            $response_arr_to = json_decode($response, true);
//            $r += 50;
//        } while (empty($response_arr_to)  && $r < 200);
//
//        if (!empty($response_arr_to)) {
//            $to = $response_arr_to["properties"]["street_type"]
//                . $response_arr_to["properties"]["street"]
//                . ", буд." . $response_arr_to["properties"]["name"]
//                . ", " . $response_arr_to["properties"]["settlement_type"]
//                . " " . $response_arr_to["properties"]["settlement"];
//        } else {
//            $url = $connectAPI . '/api/geodata/nearest';
//            $response_to = Http::withHeaders([
//            'Authorization' => $authorization,
//            ])->get($url, [
//            'lat' => $destLatitude, //Обязательный. Широта
//            'lng' => $destLongitude, //Обязательный. Долгота
//                /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
//            ]);
//            $response_arr_to = json_decode($response_to, true);
//            if ($response_arr_to['geo_streets']['geo_street'] != null) {
//                $params['routeto'] = $response_arr_to['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
//                $params['routetonumber'] = $response_arr_to['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
//                $to = $params['routeto'] . ", буд." . $params['routetonumber']  . $city;
//            } else {
//                $params['routeto'] = $response_arr_to['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
//                $params['routetonumber'] = null; //Обязательный. Дом откуда.
//                $to = $params['routeto'] . $city;
//            }
//
//        }
//
//        $params['user_full_name'] = "Андроід-користувач";
//        $params['user_phone'] = "user_phone";
//
//        $params['client_sub_card'] = null;
////        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
//        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False
//
//        $reservation = $params['reservation'];
//        $required_time = null;
//        $params['wagon'] = 0;
//        $params['minibus'] = 0;
//        $params['premium'] = 0;
//        $params['route_address_entrance_from'] = null;
//
////        if ($req->wagon == 'on' || $req->wagon == 1) {
////            $params['wagon'] = 1; //Универсал: True, False
////        } else {
////            $params['wagon'] = 0;
////        };
////        if ($req->minibus == 'on' || $req->minibus == 1) {
////            $params['minibus'] = 1; //Микроавтобус: True, False
////        } else {
////            $params['minibus'] = 0;
////        };
////        if ($req->premium == 'on' || $req->premium == 1) {
////            $params['premium'] = 1; //Машина премиум-класса: True, False
////        } else {
////            $params['premium'] = 0;
////        };
//
//        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
//        $params['comment'] = "comment"; //Комментарий к заказу
//        $params['add_cost'] = 0; //Добавленная стоимость
//        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
//
//        $payment_type_info = 'готівка';
//
//        $params['route_undefined'] = false; //По городу: True, False
//
////        if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
////            $params['routeto'] =  $params['routefrom']; //Обязательный. Улица куда.
////            $params['routetonumber'] = $params['routefromnumber']; //Обязательный. Дом куда.
////            $params['route_undefined'] = 1; //По городу: True, False
////        };
//        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
//
////        $json_arr = WebOrderController::tariffs();
//
//                $user_full_name = "Андроід-користувач";
//                $user_phone = "user_phone";
//
//
////                if (ComboTest::where('name', $from)->first()->street == 0) {
////                    $from_number_info = '';
////                } else {
////                    $from_number_info = "(будинок №$from_number)";
////                };
//
//
//                $taxiColumnId = config('app.taxiColumnId');
//
//                $route_undefined = false;
//
//
//
////                if ($params['route_undefined'] == 1) {
////                    $route_undefined = true;
////                    $to = $from;
////                    $to_number = $from_number;
////                };
//
////                if (ComboTest::where('name', $to)->first()->street == 0) {
////                    $to_number_info = '';
////                } else {
////                    $to_number_info = "(будинок №$to_number)";
////                };
//
//
////                $connectAPI = WebOrderController::connectApi();
////                if ($connectAPI == 400) {
////
////                    return 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.';
////                }
//                $url = $connectAPI . '/api/weborders';
//                $response = Http::withHeaders([
//                    'Authorization' => $authorization,
//                ])->post($url, [
//                    'user_full_name' => 'Андрей ТЕСТ!!! СРАЗУ УДАЛЯТЬ ЗАКАЗ!!!!!', //Полное имя пользователя
//                    'user_phone' => $phone, //Телефон пользователя
//                    'client_sub_card' => null,
//                    'required_time' => null, //Время подачи предварительного заказа
//                    'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
//                    'route_address_entrance_from' => null,
//                    'comment' => "ТЕСТ!!!!", //Комментарий к заказу
//                    'add_cost' => 0,
//                    'wagon' => 0, //Универсал: True, False
//                    'minibus' => 0, //Микроавтобус: True, False
//                    'premium' => 0, //Машина премиум-класса: True, False
//                    'flexible_tariff_name' => $tariff, //Гибкий тариф
//                    'route_undefined' => false, //По городу: True, False
//                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
//
//                        ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
//                        ['name' => $to, 'lat' => $destLatitude, 'lng' => $destLongitude ],
//
//                    ],
//                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
//                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
//                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
//                ]);
//
//        if ($response->status() == 200) {
//
//            return  response($response, 200)
//                ->header('Content-Type', 'json');
//        } else {
//            $response_arr = json_decode($response, true);
//
//            $response_error["order_cost"] = 0;
//            $response_error["Message"] = $response_arr["Message"];
//            return  response($response_error, 200)
//                ->header('Content-Type', 'json');
//        }
//
//
//
//
////                if ($response->status() == "200") {
////                    /**
////                     * Сохранние расчетов в базе
////                     */
////                    $order = new Order();
////                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;;//IP пользователя
////                    $order->user_full_name = $user_full_name;//Полное имя пользователя
////                    $order->user_phone = $user_phone;//Телефон пользователя
////                    $order->client_sub_card = null;
////                    $order->required_time = $required_time; //Время подачи предварительного заказа
////                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
////                    $order->route_address_entrance_from = null;
////                    $order->comment = $comment;  //Комментарий к заказу
////                    $order->add_cost = $add_cost; //Добавленная стоимость
////                    $order->wagon = $wagon; //Универсал: True, False
////                    $order->minibus = $minibus; //Микроавтобус: True, False
////                    $order->premium = $premium; //Машина премиум-класса: True, False
////                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
////                    $order->route_undefined = $route_undefined; //По городу: True, False
////                    $order->routefrom = $from; //Обязательный. Улица откуда.
////                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
////                    $order->routeto = $to; //Обязательный. Улица куда.
////                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
////                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
////                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
////                    $order->save();
////                    $id = $order;
////                    $json_arr = json_decode($response, true);
////
////                    $order_cost  = $json_arr['order_cost'];
////
////                    if ($route_undefined === true) {
////                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
////                        $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
////                    } else {
////                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
////                        $from $from_number_info до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
////                    };
////
////
////                    return redirect()->route('home-id', ['id' => $id])
////                        ->with('success', $order)
////                        ->with('order_cost', $order_cost);
////
////                } else {
////                    $params['routefromnumberBlockNone'] = 'block';
////                    $params['routetonumberBlockNone'] = 'block';
////                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
////                            відправлення/призначення або не вибрана опція поїздки по місту.
////                            Правильно вводьте або зверніться до оператора.";
////                    $json_arr = WebOrderController::tariffs();
////                    return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
////                        'info' => $info]);
////                }
//
//    }

    public function costSearch($from, $from_number, $to, $to_number, $tariff)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
                        $response_error["order_cost"] = 0;
            $response_error["Message"] = "Помилка з'єднання з сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        /**
         * Параметры запроса
         */

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $params['user_full_name'] = "Андроід-користувач";
        $params['user_phone'] = "user_phone";

        $params['client_sub_card'] = null;
//        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

//        if ($req->wagon == 'on' || $req->wagon == 1) {
//            $params['wagon'] = 1; //Универсал: True, False
//        } else {
//            $params['wagon'] = 0;
//        };
//        if ($req->minibus == 'on' || $req->minibus == 1) {
//            $params['minibus'] = 1; //Микроавтобус: True, False
//        } else {
//            $params['minibus'] = 0;
//        };
//        if ($req->premium == 'on' || $req->premium == 1) {
//            $params['premium'] = 1; //Машина премиум-класса: True, False
//        } else {
//            $params['premium'] = 0;
//        };

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = "comment"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        if ($from == $to) {
            $route_undefined = true;
        } else {
            $route_undefined = false;
        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        $combos = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
        $from = $combos->name;

        $combos = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
        $to = $combos->name;

        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "comment", //Комментарий к заказу
            'add_cost' => -39,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $from, 'number' => $from_number],
                        ['name' => $to, 'number' => $to_number],
            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

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


    public function orderSearch($from, $from_number, $to, $to_number, $tariff, $phone, $user)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
//        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;

        $params["required_time"] = $required_time;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

//        if ($req->wagon == 'on' || $req->wagon == 1) {
//            $params['wagon'] = 1; //Универсал: True, False
//        } else {
//            $params['wagon'] = 0;
//        };
//        if ($req->minibus == 'on' || $req->minibus == 1) {
//            $params['minibus'] = 1; //Микроавтобус: True, False
//        } else {
//            $params['minibus'] = 0;
//        };
//        if ($req->premium == 'on' || $req->premium == 1) {
//            $params['premium'] = 1; //Машина премиум-класса: True, False
//        } else {
//            $params['premium'] = 0;
//        };

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = "comment"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $taxiColumnId = config('app.taxiColumnId');

        $params["from"] = $from;
        $params["routefromnumber"] =  $from_number;
        $params["to"] = $to;
        $params["to_number"] = $to_number;

        if ($from == $to) {
            $route_undefined = true;
        } else {
            $route_undefined = false;
        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        $combos = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
        $from = $combos->name;

        $combos = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
        $to = $combos->name;


        $url = $connectAPI . '/api/weborders';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua"
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "ТЕСТ!!!!", //Комментарий к заказу
            'add_cost' => -39,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)

                ['name' => $from, 'number' => $from_number],
                ['name' => $to, 'number' => $to_number],

            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        if ($response->status() == 200) {

            $response_arr = json_decode($response, true);

            $params["order_cost"] = $response_arr["order_cost"];
            $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];

            self::saveOrder($params);

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["discount_trip"] = $response_arr["discount_trip"];
            $response_ok["find_car_timeout"] = $response_arr["find_car_timeout"];
            $response_ok["find_car_delay"] = $response_arr["find_car_delay"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["currency"] = $response_arr["currency"];

            $response_ok["route_address_from"] = $response_arr["route_address_from"];

            $response_ok["routefrom"] =  $from;
            $response_ok["routefromnumber"] =   $from_number;

            $response_ok["route_address_to"] = $response_arr["route_address_to"];

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

    public function sendCode($phone)
    {
//        $phone = "+380936734488";

//        $connectAPI = WebOrderController::connectApi();

        $connectAPI = 'http://31.43.107.151:7303';
        $url = $connectAPI . '/api/approvedPhones/sendConfirmCode';
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


    public function costSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Помилка з'єднання з сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        /**
         * Параметры запроса
         */

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $params['user_full_name'] = "Андроід-користувач";
        $params['user_phone'] = "user_phone";

        $params['client_sub_card'] = null;
//        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

//        if ($req->wagon == 'on' || $req->wagon == 1) {
//            $params['wagon'] = 1; //Универсал: True, False
//        } else {
//            $params['wagon'] = 0;
//        };
//        if ($req->minibus == 'on' || $req->minibus == 1) {
//            $params['minibus'] = 1; //Микроавтобус: True, False
//        } else {
//            $params['minibus'] = 0;
//        };
//        if ($req->premium == 'on' || $req->premium == 1) {
//            $params['premium'] = 1; //Машина премиум-класса: True, False
//        } else {
//            $params['premium'] = 0;
//        };

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = "comment"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        $route_undefined = false;
//        if ($from == $to) {
//            $route_undefined = true;
//        } else {
//            $route_undefined = false;
//        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False


        $combos = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
        $to = $combos->name;

        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua"
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "comment", //Комментарий к заказу
            'add_cost' => -39,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $to, 'number' => $to_number],
            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            $Lat_lan =  self::geoDataSearch($to, $to_number);

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $response_arr["add_cost"];
            $response_ok["recommended_add_cost"] = $response_arr["recommended_add_cost"];
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["discount_trip"] = $response_arr["discount_trip"];
            $response_ok["lat"] = $Lat_lan["lat"];
            $response_ok["lng"] = $Lat_lan["lng"];

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

    public function orderSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff, $phone, $user)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
//        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = null;

        $params["required_time"] = $required_time;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

//        if ($req->wagon == 'on' || $req->wagon == 1) {
//            $params['wagon'] = 1; //Универсал: True, False
//        } else {
//            $params['wagon'] = 0;
//        };
//        if ($req->minibus == 'on' || $req->minibus == 1) {
//            $params['minibus'] = 1; //Микроавтобус: True, False
//        } else {
//            $params['minibus'] = 0;
//        };
//        if ($req->premium == 'on' || $req->premium == 1) {
//            $params['premium'] = 1; //Машина премиум-класса: True, False
//        } else {
//            $params['premium'] = 0;
//        };

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = "comment"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */
        $city = "(Київ та область)";

        //         $destLatitude, $destLongitude
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


        $url = $connectAPI . '/api/geodata/nearest';

        $response_from_api = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => $originLatitude, //Обязательный. Широта
            'lng' => $originLongitude, //Обязательный. Долгота
            /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
        ]);
        $response_arr_from_api = json_decode($response_from_api, true);

        if ($response_arr_from_api['geo_streets']['geo_street'] != null) {
            $params['routefrom'] = $response_arr_from_api['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
            $params['routefromnumber'] = $response_arr_from_api['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.

            $from_geo = $params['routefrom'] . ", буд." . $params['routefromnumber']  . $city;
        } else {
            $params['routefrom'] = $response_arr_from_api['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
            $params['routefromnumber'] = null; //Обязательный. Дом откуда.
            $from_geo = $params['routefrom'] . $city;
        }



        if ($response_arr_from != null) {
            $from = $response_arr_from["properties"]["street_type"]
                . $response_arr_from["properties"]["street"]
                . ", буд." . $response_arr_from["properties"]["name"]
                . ", " . $response_arr_from["properties"]["settlement_type"]
                . " " . $response_arr_from["properties"]["settlement"];
        } else {
            $from = $from_geo;
        }

        $params["from"] = $from;
        $params["to"] = $to;
        $params["to_number"] = $to_number;

        if ($from == $to) {
            $route_undefined = true;
        } else {
            $route_undefined = false;
        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False


        $combos = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
        $to = $combos->name;


        $url = $connectAPI . '/api/weborders';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua"
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "ТЕСТ!!!!  Сразу удалить этот заказ", //Комментарий к заказу
            'add_cost' => -39,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],

                ['name' => $to, 'number' => $to_number],

            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200)
        {
            $response_arr = json_decode($response, true);

            $params["order_cost"] = $response_arr["order_cost"];
            $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
            self::saveOrder($params);

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["discount_trip"] = $response_arr["discount_trip"];
            $response_ok["find_car_timeout"] = $response_arr["find_car_timeout"];
            $response_ok["find_car_delay"] = $response_arr["find_car_delay"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["currency"] = $response_arr["currency"];

            $response_ok["route_address_from"] = $response_arr["route_address_from"];

            $response_ok["routefrom"] =  $params['routefrom'];
            $response_ok["routefromnumber"] =   $params['routefromnumber'];

            $response_ok["route_address_to"] = $response_arr["route_address_to"];

//dd($response_ok);


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
        $order->routefromnumber = $params["routefromnumber"]; //Обязательный. Дом откуда.
        $order->routeto = $params["to"]; //Обязательный. Улица куда.
        $order->routetonumber = $params["to_number"]; //Обязательный. Дом куда.
        $order->taxiColumnId = $params["taxiColumnId"]; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->web_cost = $params['order_cost'];
        $order->dispatching_order_uid = $params['dispatching_order_uid'];

        $order->save();

        /**
         * Сообщение о заказе
         */

        if (!$params["route_undefined"]) {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] . " " . $params['routefromnumber'] .
                " до "  . $params['to'] . " " . $params['to_number'] .
                ". Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'];
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] . " " . $params['routefromnumber'] .
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
        $message->sendMeMessage($order);
    }

    public function geoDataSearch($to, $to_number)
    {
        $username = '0936734488';
        $password = hash('SHA512', '22223344');

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
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
//        dd($response_arr);
//        dd($response_arr["geo_streets"]["geo_street"][0]["houses"][0]['house']);
        $LatLng["lat"] = 0;
        $LatLng["lng"] = 0;
        if ((strncmp($to_number, " ", 1) != 0)) {
            if (isset($response_arr["geo_streets"]["geo_street"][0]["houses"])) {
                foreach ($response_arr["geo_streets"]["geo_street"][0]["houses"] as $value) {
//                    dd(strncmp($value['house'], $to_number, strlen($to_number)));
                    if (strncmp($value['house'], $to_number, strlen($to_number)-1) != 0) {
                        $LatLng["lat"] = $value["lat"];
                        $LatLng["lng"] = $value["lng"];
                        break;
                    }
                }
            }
        } else {
//               dd($response_arr["geo_objects"]["geo_object"]);
            if ($response_arr["geo_objects"]["geo_object"] != null) {
                foreach ($response_arr["geo_objects"]["geo_object"] as $value) {
//                    dd(strncmp($value['house'], $to_number, strlen($to_number)));
                    if (strncmp($value['name'], $to, strlen($to)) != 0) {
                        $LatLng["lat"] = $value["lat"];
                        $LatLng["lng"] = $value["lng"];
                        break;
                    }
                }
                $LatLng["lat"] = $response_arr["geo_objects"]["geo_object"][0]["lat"];
                $LatLng["lng"] = $response_arr["geo_objects"]["geo_object"][0]["lng"];
            }

//            $LatLng["lat"] = $response_arr["geo_objects"]["geo_object"][0]["lat"];
//            $LatLng["lng"] = $response_arr["geo_objects"]["geo_object"][0]["lng"];
        }
//dd($LatLng);
        return $LatLng;
    }
    public function fromSearchGeo($originLatitude, $originLongitude)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        /**
         * Откуда
         */

        //         $destLatitude, $destLongitude
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


//        $url = $connectAPI . '/api/geodata/nearest';
//
//        $response_from_api = Http::withHeaders([
//            'Authorization' => $authorization,
//        ])->get($url, [
//            'lat' => $originLatitude, //Обязательный. Широта
//            'lng' => $originLongitude, //Обязательный. Долгота
//            /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
//        ]);
//        $response_arr_from_api = json_decode($response_from_api, true);
//dd($response_arr_from_api);
//        if ($response_arr_from_api['geo_streets']['geo_street'] != null) {
//            $from_geo = $response_arr_from_api['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
//            $from_geo_number = $response_arr_from_api['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
//
//            $from_geo = $from_geo . ", буд." . $from_geo_number;
//        } else {
//            $from_geo = $response_arr_from_api['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
//        }



        if ($response_arr_from != null) {
            $from = $response_arr_from["properties"]["street_type"]
                . $response_arr_from["properties"]["street"]
                . ", буд." . $response_arr_from["properties"]["name"]
//                . ", " . $response_arr_from["properties"]["settlement_type"]
                . " (" . $response_arr_from["properties"]["settlement"] . ")";

            $response_ok["order_cost"] = 100;
            $response_ok["route_address_from"] = $from;

//dd($response_ok);

            return  response($response_ok, 200)
                ->header('Content-Type', 'json');
        } else {
//            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Помилка гоепошуку. Спробуйте вказати місце надсилання з бази адрес.";
            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function approvedPhones($phone, $confirm_code)
    {

//        $phone = "+380936734488";

//        $connectAPI = WebOrderController::connectApi();

        $connectAPI = 'http://31.43.107.151:7303';
        $url = $connectAPI . '/api/approvedPhones/';
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
        $combos = ComboTest::select(['name', 'street'])->where('name', 'like', $name . '%')->first();
        $response["resp_result"] = 0;
        $response["message"] = $combos->street;
//dd($combos);
        return  response($response, 200)
            ->header('Content-Type', 'json');
    }

    public function sentPhone(string $message)
    {
        $subject = "Ошибка подключения к серверу";

        $messageAdmin = "Ошибка подключения к серверу. Ожидает звонка $message.";
        $paramsAdmin = [
            'subject' => $subject,
            'message' => $messageAdmin,
        ];

        $alarmMessage = new TelegramController();
        $alarmMessage->sendAlarmMessage($messageAdmin);

        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
    }
}
