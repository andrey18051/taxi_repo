<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AndroidController extends Controller
{

    public function costMap($originLatitude, $originLongitude, $destLatitude, $destLongitude)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $connectAPI = 'http://31.43.107.151:7303';
        /**
         * Параметры запроса
         */
        $params['lat'] = $originLatitude;
        $params['lng'] = $originLongitude;
        $params['lat2'] = $destLatitude;
        $params['lng2'] = $destLongitude;

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
//        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        /**
         * Откуда
         */
//        $connectAPI = WebOrderController::connectApi();
//        if ($connectAPI == 400) {
//            return  null;
//        }
        $url = $connectAPI . '/api/geodata/nearest';
        $response_from = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => $originLatitude, //Обязательный. Широта
            'lng' => $originLongitude, //Обязательный. Долгота
            'r' => '500' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.
        ]);
        $response_arr_from = json_decode($response_from, true);
//        dd($response_arr_from);

        if ($response_arr_from['geo_streets']['geo_street'] == null && $response_arr_from['geo_objects']['geo_object']  == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Помилка пошуку місця відправлення";
            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
//        $params['routefrom'] = $response_arr_from['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
//        $params['routefromnumber'] = $response_arr_from['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
        /**
         * Куда
         */

        $url = $connectAPI . '/api/geodata/nearest';
        $response_to = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => $params['lat2'], //Обязательный. Широта
            'lng' => $params['lng2'], //Обязательный. Долгота
             'r' => '500' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
        ]);
        $response_arr_to = json_decode($response_to, true);
//dd($response_arr_to);
        if ($response_arr_to['geo_streets']['geo_street'] == null && $response_arr_to['geo_objects']['geo_object']  == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Помилка пошуку місця призначення";
            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        $params['user_full_name'] = "user_full_name";
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

        $params['flexible_tariff_name'] = "Базовый"; //Гибкий тариф
        $params['comment'] = "comment"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False

//        if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
//            $params['routeto'] =  $params['routefrom']; //Обязательный. Улица куда.
//            $params['routetonumber'] = $params['routefromnumber']; //Обязательный. Дом куда.
//            $params['route_undefined'] = 1; //По городу: True, False
//        };
        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/

//        $json_arr = WebOrderController::tariffs();
        /**
         * Проверка адреса назначения
         */

//        if ($response_arr_to['geo_streets']['geo_street'] == null) {
////                Ошибка адреса назначения
//            $response["order_cost"] = 0;
//            return  response($response, 200)
//                ->header('Content-Type', 'json');
//
//        }


                $user_full_name = "user_full_name";
                $user_phone = "user_phone";
//
//                $from = $params['routefrom'];
//                $from_number = $params['routefromnumber'];

//                if (Combo::where('name', $from)->first()->street == 0) {
//                    $from_number_info = '';
//                } else {
//                    $from_number_info = "(будинок №$from_number)";
//                };


                $taxiColumnId = config('app.taxiColumnId');

                $route_undefined = false;
//                $to = $params['routeto'];

//                $to_number = $params['routetonumber'];
//                if ($params['route_undefined'] == 1) {
//                    $route_undefined = true;
//                    $to = $from;
//                    $to_number = $from_number;
//                };

//                if (Combo::where('name', $to)->first()->street == 0) {
//                    $to_number_info = '';
//                } else {
//                    $to_number_info = "(будинок №$to_number)";
//                };


//                $connectAPI = WebOrderController::connectApi();
//                if ($connectAPI == 400) {
//
//                    return 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.';
//                }
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
                    'add_cost' => 0,
                    'wagon' => 0, //Универсал: True, False
                    'minibus' => 0, //Микроавтобус: True, False
                    'premium' => 0, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => "Базовый", //Гибкий тариф
                    'route_undefined' => false, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)

                        ['name' => 'текст от Визикома', 'lat' => $originLatitude, 'lng' => $originLongitude ],
                        ['name' => 'текст от Визикома', 'lat' => $destLatitude, 'lng' => $destLongitude ],
//                        ['name' => $from, 'number' => $from_number],
//                        ['name' => $to, 'number' => $to_number],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);
//dd($response->body());
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



//                if ($response->status() == "200") {
//                    /**
//                     * Сохранние расчетов в базе
//                     */
//                    $order = new Order();
//                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;;//IP пользователя
//                    $order->user_full_name = $user_full_name;//Полное имя пользователя
//                    $order->user_phone = $user_phone;//Телефон пользователя
//                    $order->client_sub_card = null;
//                    $order->required_time = $required_time; //Время подачи предварительного заказа
//                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
//                    $order->route_address_entrance_from = null;
//                    $order->comment = $comment;  //Комментарий к заказу
//                    $order->add_cost = $add_cost; //Добавленная стоимость
//                    $order->wagon = $wagon; //Универсал: True, False
//                    $order->minibus = $minibus; //Микроавтобус: True, False
//                    $order->premium = $premium; //Машина премиум-класса: True, False
//                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
//                    $order->route_undefined = $route_undefined; //По городу: True, False
//                    $order->routefrom = $from; //Обязательный. Улица откуда.
//                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
//                    $order->routeto = $to; //Обязательный. Улица куда.
//                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
//                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
//                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
//                    $order->save();
//                    $id = $order;
//                    $json_arr = json_decode($response, true);
//
//                    $order_cost  = $json_arr['order_cost'];
//
//                    if ($route_undefined === true) {
//                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
//                        $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
//                    } else {
//                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
//                        $from $from_number_info до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
//                    };
//
//
//                    return redirect()->route('home-id', ['id' => $id])
//                        ->with('success', $order)
//                        ->with('order_cost', $order_cost);
//
//                } else {
//                    $params['routefromnumberBlockNone'] = 'block';
//                    $params['routetonumberBlockNone'] = 'block';
//                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
//                            відправлення/призначення або не вибрана опція поїздки по місту.
//                            Правильно вводьте або зверніться до оператора.";
//                    $json_arr = WebOrderController::tariffs();
//                    return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
//                        'info' => $info]);
//                }

    }

    public function orderMap($originLatitude, $originLongitude, $destLatitude, $destLongitude)
    {
        /**
         * Test
         */
        $username = '0936734488';
        $password = hash('SHA512', '22223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $connectAPI = 'http://31.43.107.151:7303';
        $city = ", місто Одеса";
        /**
         * Параметры запроса
         */
        $params['lat'] = $originLatitude;
        $params['lng'] = $originLongitude;
        $params['lat2'] = $destLatitude;
        $params['lng2'] = $destLongitude;

//        $username = config('app.username');
//        $password = hash('SHA512', config('app.password'));
//        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
//        $city = ", місто Київ";
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

        if ($response_arr_from != null) {
            $from = $response_arr_from["properties"]["street_type"]
                . $response_arr_from["properties"]["street"]
                . ", буд." . $response_arr_from["properties"]["name"]
                . ", " . $response_arr_from["properties"]["settlement_type"]
                . " " . $response_arr_from["properties"]["settlement"];
        } else {
            $url = $connectAPI . '/api/geodata/nearest';

            $response_from = Http::withHeaders([
                'Authorization' => $authorization,
            ])->get($url, [
                'lat' => $originLatitude, //Обязательный. Широта
                'lng' => $originLongitude, //Обязательный. Долгота
                /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
            ]);
            $response_arr_from = json_decode($response_from, true);

                if ($response_arr_from['geo_streets']['geo_street'] != null) {
                    $params['routefrom'] = $response_arr_from['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
                    $params['routefromnumber'] = $response_arr_from['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.

                    $from = $params['routefrom'] . ", буд." . $params['routefromnumber']  . $city;
                } else {
                    $params['routefrom'] = $response_arr_from['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
                    $params['routefromnumber'] = null; //Обязательный. Дом откуда.
                    $from = $params['routefrom'] . $city;
                }



        }

         /**
         * Куда
         */
        $r = 50;

        do {
            $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                . $destLongitude
                . "," . $destLatitude
                . "&r=" . $r . "&l=1&key="
                . config("app.keyVisicom");

            $response = Http::get($url);
            $response_arr_to = json_decode($response, true);
            $r += 50;
        } while (empty($response_arr_to)  && $r < 200);

        if (!empty($response_arr_to)) {
            $to = $response_arr_to["properties"]["street_type"]
                . $response_arr_to["properties"]["street"]
                . ", буд." . $response_arr_to["properties"]["name"]
                . ", " . $response_arr_to["properties"]["settlement_type"]
                . " " . $response_arr_to["properties"]["settlement"];
        } else {
            $url = $connectAPI . '/api/geodata/nearest';
            $response_to = Http::withHeaders([
            'Authorization' => $authorization,
            ])->get($url, [
            'lat' => $destLatitude, //Обязательный. Широта
            'lng' => $destLongitude, //Обязательный. Долгота
                /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
            ]);
            $response_arr_to = json_decode($response_to, true);
            if ($response_arr_to['geo_streets']['geo_street'] != null) {
                $params['routeto'] = $response_arr_to['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
                $params['routetonumber'] = $response_arr_to['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
                $to = $params['routeto'] . ", буд." . $params['routetonumber']  . $city;
            } else {
                $params['routeto'] = $response_arr_to['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
                $params['routetonumber'] = null; //Обязательный. Дом откуда.
                $to = $params['routeto'] . $city;
            }

        }

        $params['user_full_name'] = "user_full_name";
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

        $params['flexible_tariff_name'] = "Базовый"; //Гибкий тариф
        $params['comment'] = "comment"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False

//        if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
//            $params['routeto'] =  $params['routefrom']; //Обязательный. Улица куда.
//            $params['routetonumber'] = $params['routefromnumber']; //Обязательный. Дом куда.
//            $params['route_undefined'] = 1; //По городу: True, False
//        };
        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/

//        $json_arr = WebOrderController::tariffs();

                $user_full_name = "user_full_name";
                $user_phone = "user_phone";


//                if (Combo::where('name', $from)->first()->street == 0) {
//                    $from_number_info = '';
//                } else {
//                    $from_number_info = "(будинок №$from_number)";
//                };


                $taxiColumnId = config('app.taxiColumnId');

                $route_undefined = false;



//                if ($params['route_undefined'] == 1) {
//                    $route_undefined = true;
//                    $to = $from;
//                    $to_number = $from_number;
//                };

//                if (Combo::where('name', $to)->first()->street == 0) {
//                    $to_number_info = '';
//                } else {
//                    $to_number_info = "(будинок №$to_number)";
//                };


//                $connectAPI = WebOrderController::connectApi();
//                if ($connectAPI == 400) {
//
//                    return 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.';
//                }
                $url = $connectAPI . '/api/weborders';
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => 'Андрей ТЕСТ!!!!', //Полное имя пользователя
                    'user_phone' => "0936734488", //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => null, //Время подачи предварительного заказа
                    'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
                    'route_address_entrance_from' => null,
                    'comment' => "ТЕСТ!!!!", //Комментарий к заказу
                    'add_cost' => 0,
                    'wagon' => 0, //Универсал: True, False
                    'minibus' => 0, //Микроавтобус: True, False
                    'premium' => 0, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => "Базовый", //Гибкий тариф
                    'route_undefined' => false, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)

                        ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                        ['name' => $to, 'lat' => $destLatitude, 'lng' => $destLongitude ],

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




//                if ($response->status() == "200") {
//                    /**
//                     * Сохранние расчетов в базе
//                     */
//                    $order = new Order();
//                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;;//IP пользователя
//                    $order->user_full_name = $user_full_name;//Полное имя пользователя
//                    $order->user_phone = $user_phone;//Телефон пользователя
//                    $order->client_sub_card = null;
//                    $order->required_time = $required_time; //Время подачи предварительного заказа
//                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
//                    $order->route_address_entrance_from = null;
//                    $order->comment = $comment;  //Комментарий к заказу
//                    $order->add_cost = $add_cost; //Добавленная стоимость
//                    $order->wagon = $wagon; //Универсал: True, False
//                    $order->minibus = $minibus; //Микроавтобус: True, False
//                    $order->premium = $premium; //Машина премиум-класса: True, False
//                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
//                    $order->route_undefined = $route_undefined; //По городу: True, False
//                    $order->routefrom = $from; //Обязательный. Улица откуда.
//                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
//                    $order->routeto = $to; //Обязательный. Улица куда.
//                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
//                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
//                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
//                    $order->save();
//                    $id = $order;
//                    $json_arr = json_decode($response, true);
//
//                    $order_cost  = $json_arr['order_cost'];
//
//                    if ($route_undefined === true) {
//                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
//                        $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
//                    } else {
//                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
//                        $from $from_number_info до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
//                    };
//
//
//                    return redirect()->route('home-id', ['id' => $id])
//                        ->with('success', $order)
//                        ->with('order_cost', $order_cost);
//
//                } else {
//                    $params['routefromnumberBlockNone'] = 'block';
//                    $params['routetonumberBlockNone'] = 'block';
//                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
//                            відправлення/призначення або не вибрана опція поїздки по місту.
//                            Правильно вводьте або зверніться до оператора.";
//                    $json_arr = WebOrderController::tariffs();
//                    return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
//                        'info' => $info]);
//                }

    }
}
