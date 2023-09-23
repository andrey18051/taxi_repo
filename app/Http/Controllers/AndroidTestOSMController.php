<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\BlackList;
use App\Models\City;
use App\Models\ComboTest;
use App\Models\Config;
use App\Models\DoubleOrder;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class AndroidTestOSMController extends Controller
{

    /**
     * @throws \Exception
     */
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
        $response_error["message"] = config('app.version-PAS2');

        return  response($response_error, 200)
            ->header('Content-Type', 'json');
    }

    public function identificationId()
    {
        return config("app.X-WO-API-APP-ID-TEST");
    }

    /**
     * @throws \Exception
     */
    public function connectAPI(): string
    {
        return self::onlineAPI();
    }

    /**
     * @throws \Exception
     */
    public function costSearch($from, $from_number, $to, $to_number, $tariff, $phone, $user, $services)
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        if ($tariff == " ") {
            $tariff = null;
        }

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = null; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $params['route_undefined'] = false; //По городу: True, False
        $taxiColumnId = config('app.taxiColumnId');

        $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();

        if ($from == $to) {
            $route_undefined = true;
            $combos_to = $combos_from;
        } else {
            $route_undefined = false;
            $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
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


        if ($from_number !== " ") {
            $routFrom = ['name' => $from, 'number' => $from_number];
        } else {
            $routFrom = ['name' => $from];
        }

        if ($to_number !== " ") {
            $routTo =   ['name' => $to, 'number' => $to_number];
        } else {
            $routTo =   ['name' => $to];
        }
        $LatLngFrom = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            self::autorization(),
            self::identificationId(),
            (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI),
            $connectAPI
        );

        $from_lat = $LatLngFrom["lat"];
        $from_lng =  $LatLngFrom["lng"];

        $LatLngTo = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            self::autorization(),
            self::identificationId(),
            (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI),
            $connectAPI
        );
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];

        if ($from_lat != 0 && $from_lng != 0) {
            $routFrom = ['name' => $from, 'number' => $from_number, 'lat' => $from_lat, 'lng' => $from_lng];
        }

        if ($to_lat != 0 && $to_lng != 0) {
            $routTo = ['name' => $to, 'number' => $to_number,  'lat' => $to_lat, 'lng' => $to_lng];
        }
        $rout  = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            $routFrom,
            $routTo,
        ];

        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = $from;
        $params['from_number'] = $from_number;
        $params['to'] = $to;
        $params['to_number'] = $to_number;

        (new UniversalAndroidFunctionController)->saveCost($params);

        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
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
        ];

        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            self::autorization(),
            self::identificationId(),
            (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        );

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

    /**
     * @throws \Exception
     */
    public function orderSearch($from, $from_number, $to, $to_number, $tariff, $phone, $user, $add_cost, $time, $comment, $date, $services)
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        if ($tariff == " ") {
            $tariff = null;
        }

        $userArr = preg_split("/[*]+/", $user);

        $params['user_full_name'] = $userArr[0];
        if (count($userArr) >= 2) {
            $params['email'] = $userArr[1];
        } else {
            $params['email'] = "no email";
        }
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

        $payment_type = 0;

        $autorization = self::autorization();

        if ($userArr[2] == 'bonus_payment') {
            $authorizationBonus =  (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
            $authorizationDouble =  (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
            $payment_type = 1;
        } else {
            $authorizationBonus =  null;
            $authorizationDouble =  null;
        }
        $identificationId = self::identificationId();
        $apiVersion =  (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI);

        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */
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

        $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
        $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
        if ($from == $to) {
            $route_undefined = true;
            $combos_to = $combos_from;
        } else {
            $route_undefined = false;
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


        if ($from_number !== " ") {
            $routFrom = ['name' => $from, 'number' => $from_number];
        } else {
            $routFrom = ['name' => $from];
        }

        if ($to_number !== " ") {
            $routTo =   ['name' => $to, 'number' => $to_number];
        } else {
            $routTo =   ['name' => $to];
        }
        $LatLngFrom = (new UniversalAndroidFunctionController)->geoDataSearch(
            $from,
            $from_number,
            $autorization,
            $identificationId,
            $apiVersion,
            $connectAPI
        );
        $from_lat = $LatLngFrom["lat"];
        $from_lng =  $LatLngFrom["lng"];

        $LatLngTo = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $autorization,
            $identificationId,
            $apiVersion,
            $connectAPI
        );
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];

        if ($from_lat != 0 && $from_lng != 0) {
            $routFrom = ['name' => $from, 'number' => $from_number, 'lat' => $from_lat, 'lng' => $from_lng];
        }

        if ($to_lat != 0 && $to_lng != 0) {
            $routTo = ['name' => $to, 'number' => $to_number,  'lat' => $to_lat, 'lng' => $to_lng];
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
            if ($userArr[2] == 'bonus_payment' && $from == $to) {
                $comment =  "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment'  && $from == $to) {
                $comment =  $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        }

        $url = $connectAPI . '/api/weborders';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };

        $parameter = [
            'user_full_name' => $userArr[0], //Полное имя пользователя
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];

        if ($authorizationDouble != null) {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationBonus,
                $identificationId,
                $apiVersion
            );
            $responseBonus = json_decode($response, true);
            $responseBonus["url"] = $url;
            $responseBonus["parameter"] = $parameter;

            $originalString = $parameter['user_phone'];
            $parameter['phone'] = substr($originalString, 0, -1);
            $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';
            $parameter['payment_type'] = 0;

            $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

            $responseDouble = json_decode($responseDouble, true);
            $responseDouble["url"] = $url;
            $responseDouble["parameter"] = $parameter;



        } else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $autorization,
                $identificationId,
                $apiVersion
            );
            $responseDouble = null;
        }

        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            $params["order_cost"] = $response_arr["order_cost"];
            $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
            $params['server'] = $connectAPI;
            $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI(), self::autorization(), self::identificationId());

            (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId());

            $LatLng = (new UniversalAndroidFunctionController)->geoDataSearch(
                $from,
                $from_number,
                $autorization,
                self::identificationId(),
                $apiVersion,
                $connectAPI
            );
            $response_ok["from_lat"] = $LatLng["lat"];
            $response_ok["from_lng"] =  $LatLng["lng"];

            $LatLng = (new UniversalAndroidFunctionController)->geoDataSearch(
                $to,
                $to_number,
                $autorization,
                $identificationId,
                $apiVersion,
                $connectAPI
            );
            $response_ok["lat"] = $LatLng["lat"];
            $response_ok["lng"] =  $LatLng["lng"];

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $add_cost;
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["routefrom"] =  $from;
            $response_ok["routefromnumber"] =   $from_number;
            $response_ok["routeto"] =  $to;
            $response_ok["to_number"] =   $to_number;

            if ($responseDouble != null) {
                $response_ok["dispatching_order_uid_Double"] = $responseDouble["dispatching_order_uid"];
                $doubleOrder = new DoubleOrder();
                $doubleOrder->responseBonusStr = json_encode($responseBonus);
                $doubleOrder->responseDoubleStr = json_encode($responseDouble);
                $doubleOrder->authorizationBonus = $authorizationBonus;
                $doubleOrder->authorizationDouble = $authorizationDouble;
                $doubleOrder->connectAPI = $connectAPI;
                $doubleOrder->identificationId = $identificationId;
                $doubleOrder->apiVersion = $apiVersion;
                $doubleOrder->save();

                $response_ok["doubleOrder"] = $doubleOrder->id;
            }
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

        if ($tariff == " ") {
            $tariff = null;
        }
        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = null; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
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
            $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();

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

            if ($to_number !== " ") {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                    ['name' => $to, 'number' => $to_number]
                ];
            } else {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude ],
                    ['name' => $to]
                ];
            }
        }

        $params['from'] = "lat: " . $originLatitude . " lon: " . $originLongitude;
        $params['from_number'] = " ";
        $params['to_number'] = $to_number;

        /**
         * Сохранние расчетов в базе
         */

        (new UniversalAndroidFunctionController)->saveCost($params);

        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
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
            'extra_charge_codes' =>$extra_charge_codes,
            //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];

        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            self::autorization(),
            self::identificationId(),
            (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        );

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

    /**
     * @throws \Exception
     */
    public function orderSearchGeo($originLatitude, $originLongitude, $to, $to_number, $tariff, $phone, $user, $add_cost, $time, $comment, $date, $services)
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        if ($tariff == " ") {
            $tariff = null;
        }

        $userArr = preg_split("/[*]+/", $user);

        $params['user_full_name'] = $userArr[0];
        if (count($userArr) >= 2) {
            $params['email'] = $userArr[1];
        } else {
            $params['email'] = "no email";
        }
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

        $payment_type = 0;

        $autorization = self::autorization();

        if ($userArr[2] == 'bonus_payment') {
            $authorizationBonus =  (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
            $authorizationDouble =  (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
            $payment_type = 1;
        } else {
            $authorizationBonus =  null;
            $authorizationDouble =  null;
        }
        $identificationId = self::identificationId();
        $apiVersion =  (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI);

        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */

        $osmAddress = (new OpenStreetMapController)->reverse($originLatitude, $originLongitude);

        $params['from_number'] = " ";
        if ($osmAddress == "404") {
            $from = "Місце відправлення";
        } else {
            $params['routefromnumber'] = $osmAddress;
            $from = $osmAddress;
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

            $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();

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

            if ($to_number !== " ") {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                    ['name' => $to, 'number' => $to_number]
                ];
            } else {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                    ['name' => $to]
                ];
            }
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
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment =  "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment'  && $route_undefined) {
                $comment =  $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        }

        $url = $connectAPI . '/api/weborders';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };

        $parameter =  [
            'user_full_name' => $userArr[0], //Полное имя пользователя
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' =>   $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];

        if ($authorizationDouble != null) {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationBonus,
                $identificationId,
                $apiVersion
            );
            $responseBonus = json_decode($response, true);
            $responseBonus["url"] = $url;
            $responseBonus["parameter"] = $parameter;

            $originalString = $parameter['user_phone'];
            $parameter['phone'] = substr($originalString, 0, -1);
            $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';
            $parameter['payment_type'] = 0;

            $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

            $responseDouble = json_decode($responseDouble, true);
            $responseDouble["url"] = $url;
            $responseDouble["parameter"] = $parameter;



        } else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $autorization,
                $identificationId,
                $apiVersion
            );
            $responseDouble = null;
        }


        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);

            if ($response_arr["order_cost"] != 0) {
                $params["order_cost"] = $response_arr["order_cost"];
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $params['server'] = $connectAPI;

                $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI(), self::autorization(), self::identificationId());
                (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId());
                if ($route_undefined == false) {
                    $LatLng = (new UniversalAndroidFunctionController)->geoDataSearch(
                        $to,
                        $to_number,
                        $autorization,
                        self::identificationId(),
                        (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI),
                        $connectAPI
                    );
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
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["routefrom"] = $params['routefrom'];
                $response_ok["routefromnumber"] = $params['routefromnumber'];

                $response_ok["routeto"] = $params['to'];
                $response_ok["to_number"] = $to_number;

                if ($responseDouble != null) {
                    $response_ok["dispatching_order_uid_Double"] = $responseDouble["dispatching_order_uid"];
                    $doubleOrder = new DoubleOrder();
                    $doubleOrder->responseBonusStr = json_encode($responseBonus);
                    $doubleOrder->responseDoubleStr = json_encode($responseDouble);
                    $doubleOrder->authorizationBonus = $authorizationBonus;
                    $doubleOrder->authorizationDouble = $authorizationDouble;
                    $doubleOrder->connectAPI = $connectAPI;
                    $doubleOrder->identificationId = $identificationId;
                    $doubleOrder->apiVersion = $apiVersion;
                    $doubleOrder->save();

                    $response_ok["doubleOrder"] = $doubleOrder->id;
                }
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

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = "0";
            $response_error["Message"] = $response_arr["Message"];

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    /**
     * @throws \Exception
     */
    public function costSearchMarkers($originLatitude, $originLongitude, $toLatitude, $toLongitude, $tariff, $phone, $user, $services)
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        if ($tariff == " ") {
            $tariff = null;
        }

        $params['user_full_name'] = $user;
        $params['user_phone'] = $phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = null; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['route_address_entrance_from'] = null;

        $params['flexible_tariff_name'] = $tariff; //Гибкий тариф
        $params['comment'] = " "; //Комментарий к заказу
        $params['add_cost'] = 0; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $params['route_undefined'] = false; //По городу: True, False

        $taxiColumnId = config('app.taxiColumnId');

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['to'] = 'по місту';
        } else {
            $route_undefined = false;

            $osmAddress = (new OpenStreetMapController)->reverse($toLatitude, $toLongitude);

            $params['to_number'] = " ";
            if ($osmAddress == "404") {
                $params['to'] = 'Місце призначення';
            } else {
                $params['to'] = $osmAddress;
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
        $route_undefined = false;

        (new UniversalAndroidFunctionController)->saveCost($params);

        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
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
        ];
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            self::autorization(),
            self::identificationId(),
            (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        );

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
//            $response_ok["add_cost"] = $response_arr["add_cost"];
//            $response_ok["recommended_add_cost"] = $response_arr["recommended_add_cost"];
            $response_ok["currency"] = $response_arr["currency"];
//            $response_ok["discount_trip"] = $response_arr["discount_trip"];

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

    /**
     * @throws \Exception
     */
    public function orderSearchMarkers($originLatitude, $originLongitude, $toLatitude, $toLongitude, $tariff, $phone, $user, $add_cost, $time, $comment, $date, $services)
    {

        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }
        if ($tariff == " ") {
            $tariff = null;
        }

        $userArr = preg_split("/[*]+/", $user);

        $params['user_full_name'] = $userArr[0];
        if (count($userArr) >= 2) {
            $params['email'] = $userArr[1];
        } else {
            $params['email'] = "no email";
        }
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

        $payment_type = 0;

        $autorization = self::autorization();
        if ($userArr[2] == 'bonus_payment') {
            $authorizationBonus =  (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
            $authorizationDouble =  (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
            $payment_type = 1;
        } else {
            $authorizationBonus =  null;
            $authorizationDouble =  null;
        }
        $identificationId = self::identificationId();
        $apiVersion =  (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI);


        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */

        $osmAddress = (new OpenStreetMapController)->reverse($originLatitude, $originLongitude);

        $params['from_number'] = " ";
        if ($osmAddress == "404") {
            $from = "Місце відправлення";
        } else {
            $params['routefromnumber'] = $osmAddress;
            $from = $osmAddress;
        }

        $params['routefrom'] = $from;

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['to'] = 'по місту';
            $params['to_number'] = " ";
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude ],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];

        } else {
            $route_undefined = false;

            $osmAddress = (new OpenStreetMapController)->reverse($toLatitude, $toLongitude);

            $params['to_number'] = " ";
            if ($osmAddress == "404") {
                $params['to'] = 'Місце призначення';
                $to = 'Місце призначення';
                $params['to_number'] = " ";
            } else {
                $params['routetonumber'] = $osmAddress;
                $to = $osmAddress;
                $params['to'] = $osmAddress;
                $params['to_number'] = " ";
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
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment =  "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment'  && $route_undefined) {
                $comment =  $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        }

        $url = $connectAPI . '/api/weborders';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter= [
            'user_full_name' => $userArr[0], //Полное имя пользователя
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];
        if ($authorizationDouble != null) {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationBonus,
                $identificationId,
                $apiVersion
            );
            $responseBonus = json_decode($response, true);
            $responseBonus["url"] = $url;
            $responseBonus["parameter"] = $parameter;

            $originalString = $parameter['user_phone'];
            $parameter['phone'] = substr($originalString, 0, -1);
            $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';
            $parameter['payment_type'] = 0;

            $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

            $responseDouble = json_decode($responseDouble, true);
            $responseDouble["url"] = $url;
            $responseDouble["parameter"] = $parameter;



        } else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $autorization,
                $identificationId,
                $apiVersion
            );
            $responseDouble = null;
        }


        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if ($response_arr["order_cost"] != 0) {
                $params["order_cost"] = $response_arr["order_cost"];
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $params['server'] = $connectAPI;
                $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI(), self::autorization(), self::identificationId());

                (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId());

                $response_ok["from_lat"] = $originLatitude;
                $response_ok["from_lng"] =  $originLongitude;

                $response_ok["lat"] = $toLatitude;
                $response_ok["lng"] =  $toLongitude;

                $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
                $response_ok["order_cost"] = $response_arr["order_cost"];
                $response_ok["add_cost"] = $add_cost;
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["routefrom"] = $params['from'];
                $response_ok["routefromnumber"] = $params['from_number'];
                $response_ok["routeto"] = $params['to'];
                $response_ok["to_number"] = $params['to_number'];

                if ($responseDouble != null) {
                    $response_ok["dispatching_order_uid_Double"] = $responseDouble["dispatching_order_uid"];
                    $doubleOrder = new DoubleOrder();
                    $doubleOrder->responseBonusStr = json_encode($responseBonus);
                    $doubleOrder->responseDoubleStr = json_encode($responseDouble);
                    $doubleOrder->authorizationBonus = $authorizationBonus;
                    $doubleOrder->authorizationDouble = $authorizationDouble;
                    $doubleOrder->connectAPI = $connectAPI;
                    $doubleOrder->identificationId = $identificationId;
                    $doubleOrder->apiVersion = $apiVersion;
                    $doubleOrder->save();

                    $response_ok["doubleOrder"] = $doubleOrder->id;
                }
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

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
            $response_error["order_cost"] = "0";
            $response_error["Message"] = $response_arr["Message"];

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    /**
     * @throws \Exception
     */
    public function fromSearchGeo($originLatitude, $originLongitude)
    {

        $osmAddress = (new OpenStreetMapController)->reverse($originLatitude, $originLongitude);

        if ($osmAddress == "404") {
            $addressArr = self::geoLatLanSearch($originLatitude, $originLongitude);
            if (isset($addressArr["order_cost"]) && $addressArr["order_cost"] != 0) {
                $response["name"] = $addressArr['name'];
                $response["house"] = $addressArr['house'];
            } else {
                $response["name"] = 'name';
                $response["house"] = 'house';
            }

        } else {
            $from =  $osmAddress;

            $response["order_cost"] = 100;
            $response["route_address_from"] = $from;

        }

        return  response($response, 200)
            ->header('Content-Type', 'json');
    }

    /**
     * @throws \Exception
     */
    public function geoLatLanSearch($originLatitude, $originLongitude): array
    {
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/nearest';

        $r = 50;
        do {
            $response = Http::withHeaders([
                "Authorization" => self::autorization(),
                "X-WO-API-APP-ID" => self::identificationId(),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
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

        return  $addressArr;
    }


    /**
     * @throws \Exception
     */
    public function autocompleteSearchComboHid($name)
    {
        $connectAPI = self::connectApi();
        if ($connectAPI == 400) {
            $response_error["resp_result"] = 200;
            $response_error["message"] = 200;

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $combos = ComboTest::where('name', 'like', $name . '%')->first();

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

    private function autorization(): string
    {

        $city = City::where('address', str_replace('http://', '', self::connectApi()))->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function myHistory()
    {

        $connectAPI = self::connectApi();

        $url = $connectAPI . '/api/clients/ordershistory';

        return Http::withHeaders([
            "Authorization" => self::autorization(),
            "X-WO-API-APP-ID" => self::identificationId(),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        ])->get($url);
    }
    public function historyUID($uid)
    {

        $connectAPI = self::connectApi();


        $url = $connectAPI . '/api/weborders/';

        $url = $url . $uid;

        return Http::withHeaders([
            "Authorization" => self::autorization(),
            "X-WO-API-APP-ID" => self::identificationId(),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        ])->get($url);
    }


    /**
     * Запрос отмены заказа клиентом
     * @return string|string[]
     * @throws \Exception
     */
    public function webordersCancel($uid)
    {
        $connectAPI = self::connectApi();

        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => self::autorization(),
            "X-WO-API-APP-ID" => self::identificationId(),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        ])->put($url);

        $json_arrWeb = json_decode($response, true);

        $resp_answer = "Запит на скасування замовлення надіслано. ";

        switch ($json_arrWeb['order_client_cancel_result']) {
            case '0':
                $resp_answer = $resp_answer . "Замовлення не вдалося скасувати.";
                break;
            case '1':
                $resp_answer = $resp_answer . "Замовлення скасоване.";
                break;
            case '2':
                $resp_answer = $resp_answer . "Вимагає підтвердження клієнтом скасування диспетчерської.";
                break;
        }
//        dd($resp_answer);
        return [
            'response' => $resp_answer,
        ];
    }

    /**
     * @throws \Exception
     */
    public function historyUIDStatus($uid)
    {
        $connectAPI = self::connectApi();
        $url = $connectAPI . '/api/weborders/' . $uid;

        return Http::withHeaders([
            "Authorization" => self::autorization(),
            "X-WO-API-APP-ID" => self::identificationId(),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        ])->get($url);
    }


    /**
     * @throws \Exception
     */
    public function onlineAPI(): string
    {

        /**
         * Odessa;
         */
        $city = "OdessaTest";

        return (new CityController)->cityOnline($city);
    }

    /**
     * Контроль версии улиц и объектов
     * @throws \Exception
     */
    public function versionComboOdessa(): \Illuminate\Http\RedirectResponse
    {
        $base = env('DB_DATABASE');
        $marker_update = false;

        $authorization = self::autorization();
        /**
         * Проверка подключения к серверам
         */
        $connectAPI = self::connectApi();

        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');

        }

        /**
         * Проверка даты геоданных в АПИ
         */

        $url = $connectAPI . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            "Authorization" => self::autorization(),
            "X-WO-API-APP-ID" => self::identificationId(),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion("OdessaTest", $connectAPI)
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);
        $json_arr = json_decode($json_str, true);
//        dd($json_arr);
        $url_ob = $connectAPI . '/api/geodata/objects';
        $response_ob = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url_ob);

        $json_arr_ob = json_decode($response_ob, true);

        /**
         * Проверка версии геоданных и обновление или создание базы адресов
         */


        //Проверка версии геоданных и обновление или создание базы адресов

        DB::table('combo_tests')->truncate();

        foreach ($json_arr['geo_street'] as $arrStreet) { //Улицы
            $combo = new ComboTest();
            $combo->name = $arrStreet["name"];
            $combo->street = 1;
            $combo->save();

        }

        foreach ($json_arr_ob['geo_object'] as $arrObject) { // Объекты
            $combo = new ComboTest();
            $combo->name = $arrObject["name"];
            $combo->street = 0;
            $combo->save();

        }

        $svd = Config::where('id', '1')->first();
        $svd->odessa_versionDate = $json_arr['version_date'];
        $svd->save();

        return redirect()->route('home-admin')->with('success', "База $base обновлена.");
    }
}
