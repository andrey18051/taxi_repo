<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Mail\ServerServiceMessage;
use App\Models\BlackList;
use App\Models\CherkasyCombo;
use App\Models\City;
use App\Models\Combo;
use App\Models\ComboTest;
use App\Models\Config;
use App\Models\DniproCombo;
use App\Models\DoubleOrder;
use App\Models\OdessaCombo;
use App\Models\Orderweb;
use App\Models\ZaporizhzhiaCombo;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class AndroidTestOSMController extends Controller
{

    /**
     * @throws \Exception
     */
    public function index(): int
    {
        $city = "OdessaTest";
        $connectAPI = self::connectApi($city);
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

        return response($response_error, 200)
            ->header('Content-Type', 'json');
    }

    public function identificationId(string $application)
    {
        $applicationId = null;
        switch ($application) {
            case "PAS1":
                $applicationId = config("app.X-WO-API-APP-ID-PAS1");
                break;
            case "PAS2":
                $applicationId = config("app.X-WO-API-APP-ID-PAS2");
                break;
            case "PAS3":
                $applicationId = config("app.X-WO-API-APP-ID-PAS3");
                break;
            case "PAS4":
                $applicationId = config("app.X-WO-API-APP-ID-PAS4");
                break;
        }
        return $applicationId;
    }

    /**
     * @throws \Exception
     */
    public function connectAPI(string $city): string
    {
        return self::onlineAPI($city);
    }

    /**
     * @throws \Exception
     */
    public function costSearch(
        $from,
        $from_number,
        $to,
        $to_number,
        $tariff,
        $phone,
        $user,
        $services,
        $city,
        $application
    ) {
//        $city = "OdessaTest";
//        $application = "PAS2";


        $connectAPI = self::connectApi($city);
//dd($connectAPI);
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


        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);
        $authorization = $authorizationChoiceArr["authorization"];
        $payment_type = $authorizationChoiceArr["payment_type"];

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

        switch ($city) {
            case "Kyiv City":
                $combos_from = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
                break;
            case "Dnipropetrovsk Oblast":
                $combos_from = DniproCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                break;
            case "Odessa":
                $combos_from = OdessaCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                break;
            case "Zaporizhzhia":
                $combos_from = ZaporizhzhiaCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                break;
            case "Cherkasy Oblast":
                $combos_from = CherkasyCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                break;
            case "OdessaTest":
                $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
                break;
        }


        if ($from == $to) {
            $route_undefined = true;
            $combos_to = $combos_from;
        } else {
            $route_undefined = false;

            switch ($city) {
                case "Kyiv City":
                    $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Dnipropetrovsk Oblast":
                    $combos_to = DniproCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Odessa":
                    $combos_to = OdessaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Zaporizhzhia":
                    $combos_to = ZaporizhzhiaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Cherkasy Oblast":
                    $combos_to = CherkasyCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "OdessaTest":
                    $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
            }

        }
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->name;
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
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
            $routTo = ['name' => $to, 'number' => $to_number];
        } else {
            $routTo = ['name' => $to];
        }
        $LatLngFrom = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI),
            $connectAPI
        );

        $from_lat = $LatLngFrom["lat"];
        $from_lng = $LatLngFrom["lng"];

        $LatLngTo = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI),
            $connectAPI
        );
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];

        if ($from_lat != 0 && $from_lng != 0) {
            $routFrom = ['name' => $from, 'number' => $from_number, 'lat' => $from_lat, 'lng' => $from_lng];
        }

        if ($to_lat != 0 && $to_lng != 0) {
            $routTo = ['name' => $to, 'number' => $to_number, 'lat' => $to_lat, 'lng' => $to_lng];
        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];
//dd($parameter);
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        );
//        dd($response->body());
        if ($response->status() == 200) {
            return response($response, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    /**
     * @throws \Exception
     */
    public function orderSearch(
        $from,
        $from_number,
        $to,
        $to_number,
        $tariff,
        $phone,
        $user,
        $add_cost,
        $time,
        $comment,
        $date,
        $services
    ) {
        $city = "OdessaTest";
        $application = "PAS2";

        $connectAPI = self::connectApi($city);

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


        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);
        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];




        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI);

        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */
        $params["from"] = $from;
        $params["from_number"] = $from_number;
        $params["routefromnumber"] = $from_number;

        $params["to"] = $to;
        $params["to_number"] = $to_number;

        if ($from == $to) {
            $route_undefined = true;
        } else {
            $route_undefined = false;
        }

        $params['route_undefined'] = $route_undefined; //По городу: True, False
        switch ($city) {
            case "Kyiv City":
                $combos_from = Combo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
                break;
            case "Dnipropetrovsk Oblast":
                $combos_from = DniproCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = DniproCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                break;
            case "Odessa":
                $combos_from = OdessaCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = OdessaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                break;
            case "Zaporizhzhia":
                $combos_from = ZaporizhzhiaCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = ZaporizhzhiaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                break;
            case "Cherkasy Oblast":
                $combos_from = CherkasyCombo::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = CherkasyCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                break;
            case "OdessaTest":
                $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
                $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
                break;
        }
//        $combos_from = ComboTest::select(['name'])->where('name', 'like', $from . '%')->first();
//        $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
        if ($from == $to) {
            $route_undefined = true;
            $combos_to = $combos_from;
        } else {
            $route_undefined = false;
        }

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->name;
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
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
            $routTo = ['name' => $to, 'number' => $to_number];
        } else {
            $routTo = ['name' => $to];
        }
        $LatLngFrom = (new UniversalAndroidFunctionController)->geoDataSearch(
            $from,
            $from_number,
            $authorization,
            $identificationId,
            $apiVersion,
            $connectAPI
        );
        $from_lat = $LatLngFrom["lat"];
        $from_lng = $LatLngFrom["lng"];

        $LatLngTo = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
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
            $routTo = ['name' => $to, 'number' => $to_number, 'lat' => $to_lat, 'lng' => $to_lng];
        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            $routFrom,
            $routTo,
        ];

        $required_time = null; //Время подачи предварительного заказа
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
            $comment = "Оператору набрать заказчика и согласовать весь заказ";
            if ($userArr[2] == 'bonus_payment' && $from == $to) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $from == $to) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $from == $to) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment' && $from == $to) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $from == $to) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $from == $to) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
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

//            $originalString = $parameter['user_phone'];
//            $parameter['phone'] = substr($originalString, 0, -1);
//            $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';
            $parameter['payment_type'] = 0;

            $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

            $responseDouble = json_decode($responseDouble, true);
//            dd($responseDouble);
            $responseDouble["url"] = $url;
            $responseDouble["parameter"] = $parameter;


        } else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorization,
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

            $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI($city), $authorization, self::identificationId($application));

            (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId($application));

            $LatLng = (new UniversalAndroidFunctionController)->geoDataSearch(
                $from,
                $from_number,
                $authorization,
                self::identificationId($application),
                $apiVersion,
                $connectAPI
            );
            $response_ok["from_lat"] = $LatLng["lat"];
            $response_ok["from_lng"] = $LatLng["lng"];

            $LatLng = (new UniversalAndroidFunctionController)->geoDataSearch(
                $to,
                $to_number,
                $authorization,
                $identificationId,
                $apiVersion,
                $connectAPI
            );
            $response_ok["lat"] = $LatLng["lat"];
            $response_ok["lng"] = $LatLng["lng"];

            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $add_cost;
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["routefrom"] = $from;
            $response_ok["routefromnumber"] = $from_number;
            $response_ok["routeto"] = $to;
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
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];
//            dd($response_error);
            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    /**
     * @throws \Exception
     */
    public function costSearchGeo(
        $originLatitude,
        $originLongitude,
        $to,
        $to_number,
        $tariff,
        $phone,
        $user,
        $services
    ) {
        $city = "OdessaTest";
        $application = "PAS2";
        $connectAPI = self::connectApi($city);

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

        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);
        $authorization = $authorizationChoiceArr["authorization"];
        $payment_type = $authorizationChoiceArr["payment_type"];

//        if ($userArr[2] == 'fondy_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'mono_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'bonus_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'nal_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("OdessaTest");
//            $payment_type = 0;
//        }

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
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];

        } else {
            $route_undefined = false;
//            $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
            switch ($city) {
                case "Kyiv City":
                    $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Dnipropetrovsk Oblast":
                    $combos_to = DniproCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Odessa":
                    $combos_to = OdessaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Zaporizhzhia":
                    $combos_to = ZaporizhzhiaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Cherkasy Oblast":
                    $combos_to = CherkasyCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "OdessaTest":
                    $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
            }

            if ($combos_to == null) {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Не вірна адреса";

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            } else {
                $to = $combos_to->name;
            }

            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to;

            if ($to_number !== " ") {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
                    ['name' => $to, 'number' => $to_number]
                ];
            } else {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes,
            //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];

        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        );

        if ($response->status() == 200) {
            return response($response, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    /**
     * @throws \Exception
     */
    public function orderSearchGeo(
        $originLatitude,
        $originLongitude,
        $to,
        $to_number,
        $tariff,
        $phone,
        $user,
        $add_cost,
        $time,
        $comment,
        $date,
        $services
    ) {
        $city = "OdessaTest";
        $application = "PAS2";
        $connectAPI = self::connectApi($city);

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


        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);
        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];


//        $payment_type = 0;
//        $authorizationBonus = null;
//        $authorizationDouble = null;
//        $authorization = (new UniversalAndroidFunctionController)->authorization("OdessaTest");
//        if ($userArr[2] == 'fondy_payment') {
//            $authorizationBonus = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $authorizationDouble = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'mono_payment') {
//            $authorizationBonus = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $authorizationDouble = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'bonus_payment') {
//            $authorizationBonus = (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
//            $authorizationDouble = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
//            $payment_type = 1;
//        }


        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI);

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
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];


        } else {
            $route_undefined = false;

//            $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
            switch ($city) {
                case "Kyiv City":
                    $combos_to = Combo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Dnipropetrovsk Oblast":
                    $combos_to = DniproCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Odessa":
                    $combos_to = OdessaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Zaporizhzhia":
                    $combos_to = ZaporizhzhiaCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "Cherkasy Oblast":
                    $combos_to = CherkasyCombo::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
                case "OdessaTest":
                    $combos_to = ComboTest::select(['name'])->where('name', 'like', $to . '%')->first();
                    break;
            }
            if ($combos_to == null) {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "Не вірна адреса";

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            } else {
                $to = $combos_to->name;
            }

            $params['route_undefined'] = $route_undefined; //По городу: True, False
            $params['to'] = $to;

            if ($to_number !== " ") {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                    ['name' => $to, 'number' => $to_number]
                ];
            } else {
                $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                    ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                    ['name' => $to]
                ];
            }
        }


        $required_time = null; //Время подачи предварительного заказа
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
            $comment = "Оператору набрать заказчика и согласовать весь заказ";
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
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

//            $originalString = $parameter['user_phone'];
//            $parameter['phone'] = substr($originalString, 0, -1);
//            $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';
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
                $authorization,
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

                $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI($city), $authorization, self::identificationId($application));
                (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId($application));
                if ($route_undefined == false) {
                    $LatLng = (new UniversalAndroidFunctionController)->geoDataSearch(
                        $to,
                        $to_number,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI),
                        $connectAPI
                    );
                    $response_ok["lat"] = $LatLng["lat"];
                    $response_ok["lng"] = $LatLng["lng"];
                } else {
                    $response_ok["lat"] = $originLatitude;
                    $response_ok["lng"] = $originLongitude;
                }


                $response_ok["from_lat"] = $originLatitude;
                $response_ok["from_lng"] = $originLongitude;

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
    public function costSearchMarkers(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $tariff,
        $phone,
        $user,
        $services,
        $city,
        $application
    ) {
        if ($city == "foreign countries") {
            $city = "Kyiv City";
        }
//        $city = "OdessaTest";
//        $application = "PAS2";
        $connectAPI = self::connectApi($city);

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

        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);
        $authorization = $authorizationChoiceArr["authorization"];
        $payment_type = $authorizationChoiceArr["payment_type"];

//        if ($userArr[2] == 'fondy_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'mono_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'bonus_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'nal_payment') {
//            $authorization = (new UniversalAndroidFunctionController)->authorization("OdessaTest");
//            $payment_type = 0;
//        }

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

            $params['routetonumber'] = $osmAddress;
            $to = $osmAddress;
            $params['to'] = $osmAddress;
            $params['to_number'] = " ";

        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        );

        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);

            $response_ok["from_lat"] = $originLatitude;
            $response_ok["from_lng"] = $originLongitude;

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
                $response_ok["routeto"] = $params["to"];
                $response_ok["to_number"] = $params["to_number"];
            } else {
                $response_ok["routeto"] = $toLatitude;
                $response_ok["to_number"] = " ";
            }
            $response_ok["routefrom"] = $originLatitude;
            $response_ok["routefromnumber"] = " ";

            return response($response_ok, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = 0;
            $response_error["Message"] = $response_arr["Message"];

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    /**
     * @throws \Exception
     */
    public function orderSearchMarkers(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $tariff,
        $phone,
        $user,
        $add_cost,
        $time,
        $comment,
        $date,
        $services,
        $city,
        $application
    ) {
        if ($city == "foreign countries") {
            $city = "Kyiv City";
        }
        $connectAPI = self::connectApi($city);

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


        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);
        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];


//        $payment_type = 0;
//        $authorizationBonus = null;
//        $authorizationDouble = null;
//        $authorization = (new UniversalAndroidFunctionController)->authorization("OdessaTest");
////        dd($userArr[2]);
//        if ($userArr[2] == 'fondy_payment') {
//            $authorizationBonus = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $authorizationDouble = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'mono_payment') {
//            $authorizationBonus = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay");
//            $authorizationDouble = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
//            $payment_type = 1;
//        }
//        if ($userArr[2] == 'bonus_payment') {
//            $authorizationBonus = (new UniversalAndroidFunctionController)->authorization("BonusTestOne");
//            $authorizationDouble = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo");
//            $payment_type = 1;
//        }


        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI);

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
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];

        } else {
            $route_undefined = false;

            $osmAddress = (new OpenStreetMapController)->reverse($toLatitude, $toLongitude);


            $params['routetonumber'] = $osmAddress;
            $to = $osmAddress;
            $params['to'] = $osmAddress;
            $params['to_number'] = " ";

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

        $required_time = null; //Время подачи предварительного заказа
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
            $comment = "Оператору набрать заказчика и согласовать весь заказ";
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
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
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];
//        dd($authorizationDouble);
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

//            $originalString = $parameter['user_phone'];
//            $parameter['phone'] = substr($originalString, 0, -1);
//            $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';
            $parameter['payment_type'] = 0;

            $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

            $responseDouble = json_decode($responseDouble, true);
//            dd($responseDouble);
            $responseDouble["url"] = $url;
            $responseDouble["parameter"] = $parameter;


        } else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorization,
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

                $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI($city), $authorization, self::identificationId($application));

                (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId($application));

                $response_ok["from_lat"] = $originLatitude;
                $response_ok["from_lng"] = $originLongitude;

                $response_ok["lat"] = $toLatitude;
                $response_ok["lng"] = $toLongitude;

                $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
                $response_ok["order_cost"] = $response_arr["order_cost"];
                $response_ok["add_cost"] = $add_cost;
                $response_ok["currency"] = $response_arr["currency"];
                $response_ok["routefrom"] = $from;
                $response_ok["routefromnumber"] = $params['from_number'];
                $response_ok["routeto"] = $to;
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
    public function orderSearchMarkersVisicom(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $tariff,
        $phone,
        $user,
        $add_cost,
        $time,
        $comment,
        $date,
        $start,
        $finish,
        $services,
        $city,
        $application
    ) {
        if ($city == "foreign countries") {
            $city = "Kyiv City";
        }
        $connectAPI = self::connectApi($city);

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


        $authorizationChoiceArr = self::authorizationChoice($userArr[2], $city, $connectAPI);

        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];


        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI);


        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */

        $params['from_number'] = " ";

        $from = $start;
        $params['routefrom'] = $start;

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['to'] = 'по місту';
            $params['to_number'] = " ";
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];

        } else {
            $route_undefined = false;

            $params['to'] = $finish;
            $params['to_number'] = " ";

            $to = $finish;
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $to, 'lat' => $toLatitude, 'lng' => $toLongitude]
            ];
        }

        $params['route_undefined'] = $route_undefined; //По городу: True, False

        $params['from'] = $start;
        $params['from_number'] = "";

        $required_time = null; //Время подачи предварительного заказа
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
            $comment = "Оператору набрать заказчика и согласовать весь заказ";
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $route_undefined) {
                $comment = "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
        } else {
            if ($userArr[2] == 'bonus_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'fondy_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
                $route_undefined = false;
            }
            if ($userArr[2] == 'mono_payment' && $route_undefined) {
                $comment = $comment . "Может быть продление маршрута. Оператору набрать заказчика и согласовать весь заказ";
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
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ];
//dd($parameter);
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
                $authorization,
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

                $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst($response_arr['dispatching_order_uid'], self::connectAPI($city), $authorization, self::identificationId($application));

                (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId($application));

                $response_ok["from_lat"] = $originLatitude;
                $response_ok["from_lng"] = $originLongitude;

                $response_ok["lat"] = $toLatitude;
                $response_ok["lng"] = $toLongitude;

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
                $response["house"] = 'house' . "\t";
            }

        } else {
            $from = $osmAddress;

            $response["order_cost"] = 100;
            $response["route_address_from"] = $from . "\t";

        }

        return response($response, 200)
            ->header('Content-Type', 'json');
    }

    /**
     * @throws \Exception
     */
    public function geoLatLanSearch(
        $originLatitude,
        $originLongitude
    ): array {
        $city = "OdessaTest";
        $application = "PAS2";
        $connectAPI = self::connectApi($city);

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/nearest';


        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        $r = 50;
        do {
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => self::identificationId($application),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
            ])->get($url, [
                'lat' => $originLatitude,
                'lng' => $originLongitude,
                'r' => $r,
            ]);
            $r += 50;
            $response_arr = json_decode($response, true);
        } while (empty($response_arr) && $r < 200);

        $addressArr['name'] = "name";
        $addressArr['house'] = "house";

        if ($response_arr["geo_streets"]["geo_street"] != null) {
            $addressArr['name'] = $response_arr["geo_streets"]["geo_street"][0]["name"];
            $addressArr['house'] = $response_arr["geo_streets"]["geo_street"][0]["houses"][0]["house"];
        }
        if ($response_arr["geo_objects"]["geo_object"] != null) {
            $addressArr['name'] = $response_arr["geo_objects"]["geo_object"][0]["name"];
            $addressArr['house'] = " ";
        }

        return $addressArr;
    }


    /**
     * @throws \Exception
     */
    public function autocompleteSearchComboHid(
        $name,
        $city
    ) {
//        dd($city);
//        $city = "OdessaTest";
        $connectAPI = self::connectApi($city);
        if ($connectAPI == 400) {
            $response_error["resp_result"] = 200;
            $response_error["message"] = 200;


            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
//            $combos = ComboTest::where('name', 'like', $name . '%')->first();

            switch ($city) {
                case "Kyiv City":
                    $combos = Combo::where('name', 'like', $name . '%')->first();
                    break;
                case "Dnipropetrovsk Oblast":
                    $combos = DniproCombo::where('name', 'like', $name . '%')->first();
                    break;
                case "Odessa":
                    $combos = OdessaCombo::where('name', 'like', $name . '%')->first();
                    break;
                case "Zaporizhzhia":
                    $combos = ZaporizhzhiaCombo::where('name', 'like', $name . '%')->first();
                    break;
                case "Cherkasy Oblast":
                    $combos = CherkasyCombo::where('name', 'like', $name . '%')->first();
                    break;
                case "OdessaTest":
                    $combos = ComboTest::where('name', 'like', $name . '%')->first();
                    break;
            }

            if ($combos != null) {
                $response["resp_result"] = 0;
                $response["message"] = $combos->street;
            } else {
                $response["resp_result"] = 400;
                $response["message"] = 400;

            }
            return response($response, 200)
                ->header('Content-Type', 'json');
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

    /**
     * @throws \Exception
     */
    public function sentCancelInfo($orderweb)
    {

        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $web_cost = $orderweb->web_cost;
        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Отмена заказа";

        $messageAdmin = "Клиент $user_full_name (телефон $user_phone, email $email) отменил заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн. Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas. Время отмены $updated_at";
        $paramsAdmin = [
            'subject' => $subject,
            'message' => $messageAdmin,
        ];

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };
        Mail::to('cartaxi4@gmail.com')->send(new ServerServiceMessage($paramsAdmin));
        Mail::to('taxi.easy.ua@gmail.com')->send(new ServerServiceMessage($paramsAdmin));
    }

    /**
     * @throws \Exception
     */
    public function myHistory()
    {

        $city = "OdessaTest";
        $application = "PAS2";
        $connectAPI = self::connectApi($city);

        $url = $connectAPI . '/api/clients/ordershistory';
        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        ])->get($url);
    }

    /**
     * @throws \Exception
     */
    public function historyUID($uid)
    {

        $city = "OdessaTest";
        $application = "PAS2";
        $connectAPI = self::connectApi($city);


        $url = $connectAPI . '/api/weborders/';

        $url = $url . $uid;
        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        ])->get($url);
    }


    /**
     * Запрос отмены заказа клиентом
     * @return string|string[]
     * @throws \Exception
     */
    public function webordersCancel(
        $uid,
        $city,
        $application
    ) {
        if ($city == "foreign countries") {
            $city = "Kyiv City";
        }

        $connectAPI = self::connectApi($city);
        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
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
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($orderweb) {
            $orderweb->closeReason = "1";
            $orderweb->save();
            self::sentCancelInfo($orderweb);
        }

        return [
            'response' => $resp_answer,
        ];
    }

    /**
     * @throws \Exception
     */
    public function historyUIDStatus(
        $uid,
        $city,
        $application
    ) {
        if ($city == "foreign countries") {
            $city = "Kyiv City";
        }
        $connectAPI = self::connectApi($city);
        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        $url = $connectAPI . '/api/weborders/' . $uid;

        $response = Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        ])->get($url);

        $response_arr = json_decode($response, true);
        Log::debug($response_arr);

        $order = Orderweb:: where("dispatching_order_uid", $uid)->first();
        if($order != null) {
            $order->auto = $response_arr["order_car_info"];

            $order->save();
        }

        return $response;
    }


    /**
     * @throws \Exception
     */
    public function onlineAPI(string $city): string
    {

        /**
         * Odessa;
         */
//        $city = "OdessaTest";

        return (new CityController)->cityOnline($city);
    }

    /**
     * Контроль версии улиц и объектов
     * @throws \Exception
     */
    public function versionComboOdessa(): \Illuminate\Http\RedirectResponse
    {
        $city = "OdessaTest";

        $base = env('DB_DATABASE');

        $application = "PAS2";
        $connectAPI = self::connectApi($city);
        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');

        }

        /**
         * Проверка даты геоданных в АПИ
         */

        $url = $connectAPI . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersion($city, $connectAPI)
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);
        $json_arr = json_decode($json_str, true);
//        dd($json_arr);
        $url_ob = $connectAPI . '/api/geodata/objects';
        $authorization = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
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

    private function authorizationChoice(
        $payment,
        $city,
        $connectAPI
    ): array {
        $authorizationChoiceArr = array();

        $authorizationChoiceArr["authorization"] = (new UniversalAndroidFunctionController)->authorization($city, $connectAPI);
        $authorizationChoiceArr["payment_type"] = 0;

        switch ($payment) {
            case 'fondy_payment':
            case 'mono_payment':
                $authorizationChoiceArr["payment_type"] = 1;

                switch ($city) {
                    case "OdessaTest":
                        $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorization("GoogleTestPay", $connectAPI);
                        $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo", $connectAPI);
                        break;
                    case "Kyiv City":
                    case "Dnipropetrovsk Oblast":
                    case "Odessa":
                    case "Zaporizhzhia":
                    case "Cherkasy Oblast":
                        $authorizationChoiceArr["payment_type"] = 0;
                        $authorizationChoiceArr["authorizationBonus"] = null;
                        $authorizationChoiceArr["authorizationDouble"] = null;
                        break;
                }
                break;
            case 'bonus_payment':
                $authorizationChoiceArr["payment_type"] = 1;

                switch ($city) {
                    case "OdessaTest":
                        $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorization("BonusTestOne", $connectAPI);
                        $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorization("BonusTestTwo", $connectAPI);
                        break;
                    case "Kyiv City":
                    case "Dnipropetrovsk Oblast":
                    case "Odessa":
                    case "Zaporizhzhia":
                    case "Cherkasy Oblast":
                        $authorizationChoiceArr["payment_type"] = 0;
                        $authorizationChoiceArr["authorizationBonus"] = null;
                        $authorizationChoiceArr["authorizationDouble"] = null;
                        break;
                }
                break;
            case 'nal_payment':
                $authorizationChoiceArr["payment_type"] = 0;

                $authorizationChoiceArr["authorizationBonus"] = null;
                $authorizationChoiceArr["authorizationDouble"] = null;
                break;
        }

        return $authorizationChoiceArr;
    }

    public function lastVersion($app_name)
    {
        // Путь к файлу
        $file = "/var/www/www-root/data/www/m.easy-order-taxi.site/last_versions/$app_name/app-debug.apk";

        // Проверяем, существует ли файл
        if (file_exists($file)) {
            // Устанавливаем заголовки для скачивания файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.android.package-archive');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));

            // Отправляем содержимое файла в вывод
            readfile($file);
            exit;
        } else {
            // Если файл не найден, возвращаем ошибку 404
            http_response_code(404);
            echo 'Файл не найден.';
        }
    }

}
