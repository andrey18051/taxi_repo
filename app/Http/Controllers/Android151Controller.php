<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\BlackList;
use App\Models\Combo;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class Android151Controller extends Controller
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
        IPController::getIP('/android/PAS2/startPage');
    }
    public function connectAPI(): string
    {
        $subject = 'Отсутствует доступ к серверу.';

        /**
         * тест
         */

//        IPController::getIP('/android/PAS2');
//        $connectAPI = 'http://31.43.107.151:7303';
//        $server0 = $connectAPI;
//        $server1 = $connectAPI;
//        $server2 = $connectAPI;
//        $server3 = $connectAPI;

        /**
         * ПАС1
         */
        IPController::getIP('/android/PAS1');
        $server0 = config('app.taxi2012Url_0');
        $server1 = config('app.taxi2012Url_1');
        $server2 = config('app.taxi2012Url_2');
        $server3 = config('app.taxi2012Url_3');

        $url = "/api/time";
        $alarmMessage = new TelegramController();

        if (self::checkDomain($server0 . $url)) {
            return $server0;
        } else {
            try {
                $alarmMessage->sendAlarmMessage("Отключен " . $server0);
            } catch (Exception $e) {
                $subject = 'Ошибка в телеграмм';
                $paramsCheck = [
                    'subject' => $subject,
                    'message' => $e,
                ];
                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            };

            if (self::checkDomain($server1 . $url)) {
                $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
                    "Произведено подключение к серверу " . $server1 . ".";
                $paramsAdmin = [
                    'subject' => $subject,
                    'message' => $messageAdmin,
                ];

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
                return $server1;
            } else {
                if (self::checkDomain($server2 . $url)) {
                    $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
                        "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
                        "Произведено подключение к серверу " . $server2 . ".";
                    $paramsAdmin = [
                        'subject' => $subject,
                        'message' => $messageAdmin,
                    ];
                    try {
                        $alarmMessage->sendAlarmMessage($messageAdmin);
                    } catch (Exception $e) {
                        $subject = 'Ошибка в телеграмм';
                        $paramsCheck = [
                            'subject' => $subject,
                            'message' => $e,
                        ];
                        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsCheck));
                    };
                    Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                    Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
                    return $server2;
                } else {
                    if (self::checkDomain($server3 . $url)) {
                        $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
                            "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
                            "Ошибка подключения к серверу " . $server2 . ".   " . PHP_EOL .
                            "Произведено подключение к серверу " . $server3 . ".";
                        $paramsAdmin = [
                            'subject' => $subject,
                            'message' => $messageAdmin,
                        ];
                        try {
                            $alarmMessage->sendAlarmMessage($messageAdmin);
                        } catch (Exception $e) {
                            $subject = 'Ошибка в телеграмм';
                            $paramsCheck = [
                                'subject' => $subject,
                                'message' => $e,
                            ];
                            Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsCheck));
                        };
                        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
                        return $server3;
                    } else {
                        $messageAdmin = "Ошибка подключения к серверу " . $server0 . ".   " . PHP_EOL .
                            "Ошибка подключения к серверу " . $server1 . ".   " . PHP_EOL .
                            "Ошибка подключения к серверу " . $server2 . ".   " . PHP_EOL .
                            "Ошибка подключения к серверу " . $server3 . ".";
                        $paramsAdmin = [
                            'subject' => $subject,
                            'message' => $messageAdmin,
                        ];

                        $alarmMessage = new TelegramController();
                        $alarmMessage->sendAlarmMessage($messageAdmin);

                        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

                        return '400';
                    }
                }
            }
        }
    }

    public function costSearch($from, $from_number, $to, $to_number, $tariff, $phone, $user)
    {

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

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }
        $add_cost = 0;
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

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
        $params['comment'] = " "; //Комментарий к заказу
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

        $combos = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
        $from = $combos->name;

        $combos = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
        $to = $combos->name;

        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = $from;
        $params['from_number'] = $from_number;
        $params['to'] = $to;
        $params['to_number'] = $to_number;
        self::saveCoast($params);

        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => " ", //Комментарий к заказу
            'add_cost' => $add_cost,
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

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }
        $add_cost = 0;
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
        $params['comment'] = " "; //Комментарий к заказу
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

        $combos = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
        $from = $combos->name;

        $combos = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
        $to = $combos->name;


        $url = $connectAPI . '/api/weborders';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => " ", //Комментарий к заказу
            'add_cost' => $add_cost,
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

//    public function sendCode($phone)
//    {
//
//        $connectAPI = 'http://31.43.107.151:7303';
//        $url = $connectAPI . '/api/approvedPhones/sendConfirmCode';
//        $response = Http::post($url, [
//            'phone' => substr($phone, 3), //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
//            'taxiColumnId' => config('app.taxiColumnId') //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
//        ]);
////dd($response->body());
//        if ($response->status() == 200) {
//            $response_status["resp_result"] = 200;
//            return  response($response_status, 200)
//                ->header('Content-Type', 'json');
//        } else {
//            $response_arr = json_decode($response, true);
//
//            $response_error["resp_result"] = 400;
//            $response_error["message"] = $response_arr["Message"];
////            $response_error["message"] = "Message";
//
//            return  response($response_error, 200)
//                ->header('Content-Type', 'json');
//        }
//    }


    public function costSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff, $phone, $user)
    {


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

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }
        $add_cost = 0;
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

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
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        if ($originLatitude == $to) {
            $route_undefined = true;
            $params['route_undefined'] = $route_undefined; //По городу: True, False

            $params['to'] = 'по городу';
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ]
            ];

        } else {
            $route_undefined = false;
            $combos = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
            $to = $combos->name;
            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to;
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $to, 'number' => $to_number]
            ];
        }


        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = "lat: " . $originLatitude . " lon: " . $originLongitude;
        $params['from_number'] = " ";
        $params['to'] = $to;
        $params['to_number'] = $to_number;
        self::saveCoast($params);


        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => " ", //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if($route_undefined == false) {
                $Lat_lan =  self::geoDataSearch($to, $to_number);
                $response_ok["lat"] = $Lat_lan["lat"];
                $response_ok["lng"] = $Lat_lan["lng"];
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

    public function orderSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff, $phone, $user)
    {

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }
        $add_cost = 0;
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
        $params['comment'] = " "; //Комментарий к заказу
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
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas2"
        ])->get($url, [
            'lat' => $originLatitude, //Обязательный. Широта
            'lng' => $originLongitude, //Обязательный. Долгота
            'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
        ]);
        $response_arr_from_api = json_decode($response_from_api, true);

        if ($response_arr_from_api['geo_streets']['geo_street'] != null) {
            $params['routefrom'] = $response_arr_from_api['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
            $params['routefromnumber'] = $response_arr_from_api['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.

            $from_geo = $params['routefrom'] . ", буд." . $params['routefromnumber']  . $city;
        }
        if ($response_arr_from_api['geo_objects']['geo_object'] != null) {
            $params['routefrom'] = $response_arr_from_api['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
            $params['routefromnumber'] = null; //Обязательный. Дом откуда.
            $from_geo = $params['routefrom'] . $city;
        }
        if ($response_arr_from_api['geo_streets']['geo_street'] == null && $response_arr_from_api['geo_objects']['geo_object'] == null) {

            $response_error["order_cost"] = "0";
            $response_error["Message"] = "Помілка розрахунку.";
            $alarmMessageText = "Помілка розрахунку. Клиент $user, телефон $phone.";
            $alarmMessage = new TelegramController();
            try {
                $alarmMessage->sendMeMessage($alarmMessageText);
                $alarmMessage->sendAlarmMessage($alarmMessageText);
            } catch (Exception $e) {
                $subject = 'Ошибка в телеграмм';
                $paramsCheck = [
                    'subject' => $subject,
                    'message' => $e,
                ];

                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            };
            $paramsAdmin = [
                'subject' => "Ошибочный расчет",
                'message' => $alarmMessageText
            ];
            Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
            Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

            $url = $connectAPI . '/api/weborders';
            Http::withHeaders([
                'Authorization' => $authorization,
                "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
            ])->post($url, [
                'user_full_name' => $user,
                'user_phone' => $phone, //Телефон пользователя
                'comment' => "Оператору набрать клиента: Согласовать маршрут и стоимость!", //Комментарий к заказу
                'reservation' => false, //Обязательный. Признак предварительного заказа: True,
                'route_undefined' => true, //По городу: True, False
                'order_cost' => 70, //Добавленная стоимость
                'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => $response_arr_from["properties"]["street_type"]
                        . $response_arr_from["properties"]["street"]
                        . ", буд." . $response_arr_from["properties"]["name"]
                        . ", " . $response_arr_from["properties"]["settlement_type"]
                        . " " . $response_arr_from["properties"]["settlement"], 'lat' => '50.376733115795', 'lng' => '30.609379358341' ],
                ],
                'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы.
            ]);


            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        $to_resp = $params['routefrom'];
        $to_number_resp = $params['routefromnumber'];

        if ($response_arr_from != null) {
            $params['routefromnumber'] = $response_arr_from["properties"]["name"];
            $from = $response_arr_from["properties"]["street_type"]
                . $response_arr_from["properties"]["street"]
                . ", буд." . $response_arr_from["properties"]["name"]
                . ", " . $response_arr_from["properties"]["settlement_type"]
                . " " . $response_arr_from["properties"]["settlement"];
        } else {
            $from = $from_geo;
        }

        $params["from"] = $from;

        $params["to_number"] = $to_number;

        if ($originLatitude == $to) {
            $route_undefined = true;
            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = 'по городу';

            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ]
            ];

        } else {
            $route_undefined = false;
            $combos = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
            $to = $combos->name;
            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to;
            $to_resp = $to;
            $to_number_resp = $to_number;
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $to, 'number' => $to_number]
            ];
        }


        $url = $connectAPI . '/api/weborders';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
        ])->post($url, [
            'user_full_name' => $user, //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => " ", //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);
//dd($response->body());
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if ($response_arr["order_cost"] != 0) {
//                dd($response_arr);
                $params["order_cost"] = $response_arr["order_cost"];
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                self::saveOrder($params);

                $response_ok["lat"] = 0;
                $response_ok["lng"] = 0;

                $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
                $response_ok["order_cost"] = $response_arr["order_cost"];
                $response_ok["add_cost"] = $add_cost;
                $response_ok["recommended_add_cost"] = $add_cost;
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["discount_trip"] = $response_arr["discount_trip"];

                $response_ok["routefrom"] = $params['routefrom'];
                $response_ok["routefromnumber"] = $params['routefromnumber'];


                $response_ok["routeto"] = $to_resp;
                $response_ok["to_number"] = $to_number_resp;

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

    public function costSearchMarkers($originLatitude, $originLongitude, $toLatitude, $toLongitude, $tariff, $phone, $user)
    {
        /**
         * Test
         */
        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }

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

        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

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
        $params['comment'] = "ТЕСТ!!!"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        $route_undefined = false;
        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['route_undefined'] = $route_undefined; //По городу: True, False

            $params['to'] = 'по городу';
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ]
            ];

        } else {
            $route_undefined = false;
//
//            $r = 50;
//            do {
//                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
//                    . $toLongitude
//                    . "," . $toLatitude
//                    . "&r=" . $r . "&l=1&key="
//                    . config("app.keyVisicom");
////dd($url);
//                $response = Http::get($url);
//                $response_arr_to = json_decode($response, true);
//                $r += 50;
//            } while (empty($response_arr_to) && $r < 200);
//            dd($response_arr_to);

            $url = $connectAPI . '/api/geodata/nearest';

            $response = Http::withHeaders([
                'Authorization' => $authorization,
                "X-WO-API-APP-ID" => "taxi_easy_ua_pas2"
            ])->get($url, [
                'lat' => $toLatitude, //Обязательный. Широта
                'lng' => $toLongitude, //Обязательный. Долгота
                'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
            ]);
            $response_arr = json_decode($response, true);

            if ($response_arr['geo_streets']['geo_street'] == null && $response_arr['geo_objects']['geo_object'] == null) {

                $response_error["order_cost"] = "0";
                $response_error["Message"] = "Помілка розрахунку.";
                $alarmMessageText = "Помілка розрахунку. Клиент $user, телефон $phone.";
                $alarmMessage = new TelegramController();
                try {
                    $alarmMessage->sendMeMessage($alarmMessageText);
//                    $alarmMessage->sendAlarmMessage($alarmMessageText);
                } catch (Exception $e) {
                    $subject = 'Ошибка в телеграмм';
                    $paramsCheck = [
                        'subject' => $subject,
                        'message' => $e,
                    ];

                    Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
                };


                $paramsAdmin = [
                    'subject' => "Ошибочный расчет",
                    'message' => $alarmMessageText
                ];
                Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Помілка розрахунку. Місце призначення не знайдено. Спробуйте ще, або вібирить пошук за адресою";
//dd($response_error);
                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }

            $city = "(Київ та область)";
            if ($response_arr['geo_streets']['geo_street'] != null) {
                $params['to'] = $response_arr['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
                $params['to_number'] = $response_arr['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.

                $to_geo = $params['to'] . ", буд." . $params['to_number']  . $city;
            }
            if ($response_arr['geo_objects']['geo_object'] != null) {
                $params['to'] = $response_arr['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
                $params['to_number'] = null; //Обязательный. Дом откуда.
                $to_geo = $params['to'] . $city;
            }


            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to_geo;
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => "name", 'lat' => $toLatitude, 'lng' => $toLongitude]
            ];
        }


        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = "lat: " . $originLatitude . " lon: " . $originLongitude;
        $params['from_number'] = " ";
        self::saveCoast($params);


        $url = $connectAPI . '/api/weborders/cost';

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas2"
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
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if($route_undefined == false) {

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

    public function orderSearchMarkers($originLatitude, $originLongitude, $toLatitude, $toLongitude, $tariff, $phone, $user)
    {
        /**
         * Test
         */
        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }

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

        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

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
        $params['comment'] = "ТЕСТ!!!"; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */
        $city = "(Одесса)";

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
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas2"
        ])->get($url, [
            'lat' => $originLatitude, //Обязательный. Широта
            'lng' => $originLongitude, //Обязательный. Долгота
            'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
        ]);
        $response_arr_from_api = json_decode($response_from_api, true);

        if ($response_arr_from_api['geo_streets']['geo_street'] != null) {
            $params['routefrom'] = $response_arr_from_api['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
            $params['routefromnumber'] = $response_arr_from_api['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.

            $from_geo = $params['routefrom'] . ", буд." . $params['routefromnumber']  . $city;
        }
        if ($response_arr_from_api['geo_objects']['geo_object'] != null) {
            $params['routefrom'] = $response_arr_from_api['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
            $params['routefromnumber'] = null; //Обязательный. Дом откуда.
            $from_geo = $params['routefrom'] . $city;
        }
        if ($response_arr_from_api['geo_streets']['geo_street'] == null && $response_arr_from_api['geo_objects']['geo_object'] == null) {

            $response_error["order_cost"] = "0";
            $response_error["Message"] = "Помілка розрахунку.";
            $alarmMessageText = "Помілка розрахунку. Клиент $user, телефон $phone.";
            $alarmMessage = new TelegramController();
            try {
                $alarmMessage->sendMeMessage($alarmMessageText);
                $alarmMessage->sendAlarmMessage($alarmMessageText);
            } catch (Exception $e) {
                $subject = 'Ошибка в телеграмм';
                $paramsCheck = [
                    'subject' => $subject,
                    'message' => $e,
                ];

                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            };


            $paramsAdmin = [
                'subject' => "Ошибочный расчет",
                'message' => $alarmMessageText
            ];
            Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
            Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

            $url = $connectAPI . '/api/weborders';
            Http::withHeaders([
                'Authorization' => $authorization,
            ])->post($url, [
                'user_full_name' => $user,
                'user_phone' => $phone, //Телефон пользователя
                'comment' => "Оператору набрать клиента: Согласовать маршрут и стоимость!", //Комментарий к заказу
                'reservation' => false, //Обязательный. Признак предварительного заказа: True,
                'route_undefined' => true, //По городу: True, False
                'order_cost' => 70, //Добавленная стоимость
                'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => $response_arr_from["properties"]["street_type"]
                        . $response_arr_from["properties"]["street"]
                        . ", буд." . $response_arr_from["properties"]["name"]
                        . ", " . $response_arr_from["properties"]["settlement_type"]
                        . " " . $response_arr_from["properties"]["settlement"], 'lat' => '50.376733115795', 'lng' => '30.609379358341' ],
                ],
                'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы.
            ]);


            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        if ($response_arr_from != null) {
            $params['routefromnumber'] = $response_arr_from["properties"]["name"];
            $from = $response_arr_from["properties"]["street_type"]
                . $response_arr_from["properties"]["street"]
                . ", буд." . $response_arr_from["properties"]["name"]
                . ", " . $response_arr_from["properties"]["settlement_type"]
                . " " . $response_arr_from["properties"]["settlement"];
        } else {
            $from = $from_geo;
        }

        $params["from"] = $from;

        $route_undefined = false;
        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['route_undefined'] = $route_undefined; //По городу: True, False

            $params['to'] = 'по городу';
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ]
            ];

        } else {
            $route_undefined = false;

            $r = 50;
            do {
                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                    . $toLongitude
                    . "," . $toLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");
//dd($url);
                $response = Http::get($url);
                $response_arr_to = json_decode($response, true);
                $r += 50;
            } while (empty($response_arr_to) && $r < 200);
//            dd($response_arr_to);

            $url = $connectAPI . '/api/geodata/nearest';

            $response = Http::withHeaders([
                'Authorization' => $authorization,
                "X-WO-API-APP-ID" => "taxi_easy_ua_pas2"
            ])->get($url, [
                'lat' => $toLatitude, //Обязательный. Широта
                'lng' => $toLongitude, //Обязательный. Долгота
                'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
            ]);
            $response_arr = json_decode($response, true);

            if ($response_arr['geo_streets']['geo_street'] == null && $response_arr['geo_objects']['geo_object'] == null) {

                $response_error["order_cost"] = "0";
                $response_error["Message"] = "Помілка розрахунку.";
                $alarmMessageText = "Помілка розрахунку. Клиент $user, телефон $phone.";
                $alarmMessage = new TelegramController();
                try {
                    $alarmMessage->sendMeMessage($alarmMessageText);
                    $alarmMessage->sendAlarmMessage($alarmMessageText);
                } catch (Exception $e) {
                    $subject = 'Ошибка в телеграмм';
                    $paramsCheck = [
                        'subject' => $subject,
                        'message' => $e,
                    ];

                    Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
                };


                $paramsAdmin = [
                    'subject' => "Ошибочный расчет",
                    'message' => $alarmMessageText
                ];
                Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

                $url = $connectAPI . '/api/weborders';
                Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => $user,
                    'user_phone' => $phone, //Телефон пользователя
                    'comment' => "Оператору набрать клиента: Согласовать маршрут и стоимость!", //Комментарий к заказу
                    'reservation' => false, //Обязательный. Признак предварительного заказа: True,
                    'route_undefined' => true, //По городу: True, False
                    'order_cost' => 70, //Добавленная стоимость
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $response_arr_from["properties"]["street_type"]
                            . $response_arr_from["properties"]["street"]
                            . ", буд." . $response_arr_from["properties"]["name"]
                            . ", " . $response_arr_from["properties"]["settlement_type"]
                            . " " . $response_arr_from["properties"]["settlement"], 'lat' => '50.376733115795', 'lng' => '30.609379358341' ],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы.
                ]);
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Помілка розрахунку. Місце призначення не знайдено. Спробуйте ще, або вібирить пошук за адресою";
//dd($response_error);
                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }

            $city = " (Одеса)";
            if ($response_arr['geo_streets']['geo_street'] != null) {
                $params['to'] = $response_arr['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
                $params['to_number'] = $response_arr['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.

                $to_geo = $params['to'] . ", буд." . $params['to_number']  . $city;
            }
            if ($response_arr['geo_objects']['geo_object'] != null) {
                $params['to'] = $response_arr['geo_objects']['geo_object'][0]['name']; //Обязательный. Улица откуда.
                $params['to_number'] = " "; //Обязательный. Дом откуда.
                $to_geo = $params['to'] . $city;
            }


            $params['route_undefined'] = $route_undefined; //По городу: True, False

            if ($response_arr_to != null) {
                $params['routetonumber'] = $response_arr_to["properties"]["name"];
                $to = $response_arr_to["properties"]["street_type"]
                    . $response_arr_to["properties"]["street"]
                    . ", буд." . $response_arr_to["properties"]["name"]
                    . ", " . $response_arr_to["properties"]["settlement_type"]
                    . " " . $response_arr_to["properties"]["settlement"];
            } else {
                $to = $to_geo;
            }


            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $to, 'lat' => $toLatitude, 'lng' => $toLongitude]
            ];
        }


        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = $from;
        $params['from_number'] = " ";
        self::saveCoast($params);


        $url = $connectAPI . '/api/weborders/cost';
        $add_cost= -39;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas2"
        ])->post($url, [
            'user_full_name' => null, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "comment", //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => 0, //Универсал: True, False
            'minibus' => 0, //Микроавтобус: True, False
            'premium' => 0, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $tariff, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if ($response_arr["order_cost"] != 0) {

                $params["order_cost"] = $response_arr["order_cost"];
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                self::saveOrder($params);

                $response_ok["lat"] = 0;
                $response_ok["lng"] = 0;

                $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
                $response_ok["order_cost"] = $response_arr["order_cost"];
                $response_ok["add_cost"] = $add_cost;
                $response_ok["recommended_add_cost"] = $add_cost;
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["discount_trip"] = $response_arr["discount_trip"];

                $response_ok["routefrom"] = $params['routefrom'];
                $response_ok["routefromnumber"] = $params['routefromnumber'];

                $response_ok["routeto"] = $params['to'];
                $response_ok["to_number"] = $params['to_number'];
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
        $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
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
                " за маршрутом від " . $params['from'] .
                " до "  . $params['to'] . " " . $params['to_number'] .
                ". Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'];
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] .
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

    public function geoDataSearch($to, $to_number)
    {

        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }

        if ($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);


        $url = $connectAPI . '/api/geodata/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
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
        if ((strncmp($to_number, " ", 1) != 0)) {
            if (isset($response_arr["geo_streets"]["geo_street"][0]["houses"])) {
                foreach ($response_arr["geo_streets"]["geo_street"][0]["houses"] as $value) {
                    if (strncmp($value['house'], $to_number, strlen($to_number)-1) != 0) {
                        $LatLng["lat"] = $value["lat"];
                        $LatLng["lng"] = $value["lng"];
                        break;
                    }
                }
            }
        } else {
            if ($response_arr["geo_objects"]["geo_object"] != null) {
                foreach ($response_arr["geo_objects"]["geo_object"] as $value) {
                    if (strncmp($value['name'], $to, strlen($to)) != 0) {
                        $LatLng["lat"] = $value["lat"];
                        $LatLng["lng"] = $value["lng"];
                        break;
                    }
                }
                $LatLng["lat"] = $response_arr["geo_objects"]["geo_object"][0]["lat"];
                $LatLng["lng"] = $response_arr["geo_objects"]["geo_object"][0]["lng"];
            }

        }
        return $LatLng;
    }
    public function fromSearchGeo($originLatitude, $originLongitude)
    {
        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }

        if($connectAPI === config('app.taxi2012Url_0')) {
            $username = "SMS_NADO_OTPR";
            $password = hash('SHA512', "fhHk89)_");
        } else {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
        }
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        /**
         * Откуда
         */

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
//        "X-WO-API-APP-ID" => "taxi_easy_ua_pas1"
//        ])->get($url, [
//            'lat' => $originLatitude, //Обязательный. Широта
//            'lng' => $originLongitude, //Обязательный. Долгота
//            'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
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
            $response_error["Message"] = "Помилка гоепошуку. Спробуйте вказати місце з бази адрес.";
            return  response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function approvedPhones($phone, $confirm_code)
    {

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
        $combos = Combo::select(['name', 'street'])->where('name', 'like', $name . '%')->first();
        if ($combos != null) {
            $response["message"] = $combos->street;
        } else {
            $response["message"] = 1;
        }
        $response["resp_result"] = 0;

        return  response($response, 200)
            ->header('Content-Type', 'json');
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
}
