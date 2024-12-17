<?php

namespace App\Http\Controllers;

use App\Jobs\SearchOrderToDeleteJob;
use App\Jobs\StartStatusPaymentReview;
use App\Jobs\StartNewProcessExecution;
use App\Mail\Check;
use App\Mail\Server;
use App\Models\CherkasyCombo;
use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\Combo;
use App\Models\ComboTest;
use App\Models\Config;
use App\Models\DniproCombo;
use App\Models\DoubleOrder;
use App\Models\DriverMemoryOrder;
use App\Models\MemoryOrderChange;
use App\Models\OdessaCombo;
use App\Models\Orderweb;
use App\Models\Uid_history;
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
    public function searchOrderToDelete(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $email,
        $start,
        $finish,
        $payment_type,
        $city,
        $application
    ) {
        $uid = "";
        $uid_Double = "";

        Log::info("Запуск searchOrderToDelete", [
            'originLatitude' => $originLatitude,
            'originLongitude' => $originLongitude,
            'toLatitude' => $toLatitude,
            'toLongitude' => $toLongitude,
            'email' => $email,
            'start' => $start,
            'finish' => $finish,
            'payment_type' => $payment_type,
            'city' => $city,
            'application' => $application,
        ]);

        // Поиск заказа по координатам
        $order = Orderweb::where("email", $email)
            ->whereIn('closeReason', ['-1', '101', '102'])
            ->where("startLat", $originLatitude)
            ->where("startLan", $originLongitude)
            ->where("to_lat", $toLatitude)
            ->where("to_lng", $toLongitude)
            ->first();

        Log::info("Результат первого поиска Orderweb", ['order' => $order]);

        if (!$order) {
            // Если запись не найдена, проверяем только routefrom и routeto
            $order = Orderweb::where("email", $email)
                ->whereIn('closeReason', ['-1', '101', '102'])
                ->where("routefrom", $start)
                ->where("routeto", $finish)
                ->first();

            Log::info("Результат второго поиска Orderweb (по routefrom и routeto)", ['order' => $order]);
        }

        if ($order) {
            $uid = $order->dispatching_order_uid;
            Log::info("UID заказа найден", ['uid' => $uid]);

            $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
            Log::info("Результат поиска в Uid_history", ['uid_history' => $uid_history]);

            if ($uid_history) {
                $uid_Double = $uid_history->uid_doubleOrder;
                Log::info("UID Double найден", ['uid_Double' => $uid_Double]);
            }
        }

        if (empty($uid_Double) && !empty($uid)) {
            Log::info("Вызов webordersCancel", ['uid' => $uid, 'city' => $city, 'application' => $application]);
            (new AndroidTestOSMController)->webordersCancel(
                $uid,
                $city,
                $application
            );
        } elseif (!empty($uid_Double)) {
            Log::info("Вызов webordersCancelDouble", [
                'uid' => $uid,
                'uid_Double' => $uid_Double,
                'payment_type' => $payment_type,
                'city' => $city,
                'application' => $application
            ]);
            (new AndroidTestOSMController)->webordersCancelDouble(
                $uid,
                $uid_Double,
                $payment_type,
                $city,
                $application
            );
        }
    }


    /**
     * @throws \Exception
     */
    public static function repeatCancel(
        $url,
        $authorization,
        $application,
        $city,
        $connectAPI,
        $uid
    ): void {
        $maxExecutionTime = 3*60*60; // Максимальное время выполнения - 3 часа
        $maxExecutionTime = 2*60; // Максимальное время выполнения - 3 часа

        $startTime = time();
        $result = false;

        do {
            // Проверка статуса после отмены
            sleep(5);
            $urlCheck = $connectAPI . '/api/weborders/' . $uid;
            try {
                $response_uid = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => (new AndroidTestOSMController)->identificationId($application),
                ])->get($urlCheck);

                if ($response_uid->successful() && $response_uid->status() == 200) {
                    $response_arr = json_decode($response_uid->body(), true);
                    if ($response_arr['close_reason'] == 1) {
                        Log::debug("repeatCancel: close_reason is 1, exiting.");
                        return;
                    } else {
                        Log::debug("repeatCancel: close_reason is not 1, continuing.");
                    }
                } else {
                    // Логируем ошибки в случае неудачного запроса
                    Log::error("repeatCancel Request failed with status: " . $response_uid->status());
                    Log::error("repeatCancel Response: " . $response_uid->body());
                }
            } catch (\Exception $e) {
                // Обработка исключений
                Log::error("repeatCancel Exception caught: " . $e->getMessage());
            }
            sleep(5);
        } while (time() - $startTime < $maxExecutionTime);
        if (!$result) {
            (new MessageSentController())->sentNoCancelInfo($uid);
        }
    }

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

    public function connectAPIApp(string $city, $app): string
    {
        return self::onlineAPIApp($city, $app);
    }
    /**
     * @throws \Exception
     */

    public function connectAPIAppOrder(string $city, $app): string
    {
        return self::onlineAPIAppOrder($city, $app);
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

        $connectAPI = self::connectAPIAppOrder($city, $application);
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

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


        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
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
        Log::debug("combos_from $combos_from");
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
        Log::debug("combos_to $combos_to");
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->toArray()['name'];
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $to = $combos_to->toArray()['name'];
        }


        if ($from_number !== " ") {
            $routFrom = ['name' => $from, 'number' => $from_number];
        } else {
            $routFrom = ['name' => $from];
        }
        Log::debug("routFrom", $routFrom);
        if ($to_number !== " ") {
            $routTo = ['name' => $to, 'number' => $to_number];
        } else {
            $routTo = ['name' => $to];
        }
        Log::debug("routTo", $routTo);
        $LatLngFrom = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application),
            $connectAPI
        );

        Log::debug("LatLngFrom", $LatLngFrom);
        $from_lat = $LatLngFrom["lat"];
        $from_lng = $LatLngFrom["lng"];
        $params["startLat"] = $from_lat; //
        $params["startLan"] = $from_lng; //

        $LatLngTo = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application),
            $connectAPI
        );

        Log::debug("LatLngTo", $LatLngTo);
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];
        $params["to_lat"] = $to_lat; //
        $params["to_lng"] = $to_lng; //
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
        Log::debug("rout", $rout);
        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = $from;
        $params['from_number'] = $from_number;
        $params['to'] = $to;
        $params['to_number'] = $to_number;

//        (new UniversalAndroidFunctionController)->saveCost($params);

        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']), //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
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
        Log::debug("costSearch", $parameter);
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );
        Log::debug("costSearch" . $response->body());
        $response_arr = json_decode($response, true);
        Log::debug("response_arr: ", $response_arr);
        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        if (isset($response_arr["Message"]) && $city_count > 1) {
            $connectAPI = str_replace('http://', '', $connectAPI);
            switch ($application) {
                case "PAS1":
                    $cityServer = City_PAS1::where('address', $connectAPI)->first();
                    break;
                case "PAS2":
                    $cityServer = City_PAS2::where('address', $connectAPI)->first();
                    break;
                //case "PAS4":
                default:
                    $cityServer = City_PAS4::where('address', $connectAPI)->first();
                    break;
            }
            $cityServer->online = "false";
            $cityServer->save();
            (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

            while (self::connectAPIAppOrder($city, $application) != 400) {
                $connectAPI = self::connectAPIAppOrder($city, $application);
                $url = $connectAPI . '/api/weborders/tariffs/cost';
                Log::debug(" _____________________________");
                Log::debug(" connectAPI while $userArr[2]");
                Log::debug(" connectAPI while $city ");
                Log::debug(" connectAPI while $connectAPI ");
                Log::debug(" connectAPI while $url ");
                Log::debug(" ______________________________");

                $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

                if ($payment_type == 0) {
                    $authorization = $authorizationChoiceArr["authorization"];
                    Log::debug("authorization $authorization");
                } else {
                    $authorization = $authorizationChoiceArr["authorizationBonus"];
                    Log::debug("authorizationBonus $authorization");
                }
                $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                    $url,
                    $parameter,
                    $authorization,
                    self::identificationId($application),
                    (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                );
                $response_arr = json_decode($response, true);
                Log::debug("response_arr: ", $response_arr);
                if (!isset($response_arr[0]['error'])) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } elseif ($city_count > 1) {
                    $connectAPI = str_replace('http://', '', $connectAPI);
                    switch ($application) {
                        case "PAS1":
                            $cityServer = City_PAS1::where('address', $connectAPI)->first();
                            break;
                        case "PAS2":
                            $cityServer = City_PAS2::where('address', $connectAPI)->first();
                            break;
                        //case "PAS4":
                        default:
                            $cityServer = City_PAS4::where('address', $connectAPI)->first();
                            break;
                    }
                    $cityServer->online = "false";
                    $cityServer->save();
                    (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                } else {
                    $connectAPI = str_replace('http://', '', $connectAPI);
                    switch ($application) {
                        case "PAS1":
                            $cityServer = City_PAS1::where('address', $connectAPI)->first();
                            break;
                        case "PAS2":
                            $cityServer = City_PAS2::where('address', $connectAPI)->first();
                            break;
                        //case "PAS4":
                        default:
                            $cityServer = City_PAS4::where('address', $connectAPI)->first();
                            break;
                    }
                    (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
            if (self::connectAPIAppOrder($city, $application) == 400) {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            Log::debug("response Message 33333333");

            if ($response->status() == 200) {
                return response($response, 200)
                    ->header('Content-Type', 'json');
            } else {
                $message = "Сбой в приложение $application, сервер $connectAPI: " . $response_arr;
                (new UniversalAndroidFunctionController)->sentErrorMessage($message);

                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        }

        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response == null) {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }


                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";
                Log::debug("response_error", $response_error);

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                Log::debug("response Message 33333333");

                if ($response->status() == 200) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } else {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        }

    }

    public function costSearchTime(
        $from,
        $from_number,
        $to,
        $to_number,
        $tariff,
        $phone,
        $user,
        $time,
        $date,
        $services,
        $city,
        $application
    ) {

        $connectAPI = self::connectAPIAppOrder($city, $application);
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

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


        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
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
        Log::debug("combos_from $combos_from");
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
        Log::debug("combos_to $combos_to");
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->toArray()['name'];
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $to = $combos_to->toArray()['name'];
        }


        if ($from_number !== " ") {
            $routFrom = ['name' => $from, 'number' => $from_number];
        } else {
            $routFrom = ['name' => $from];
        }
        Log::debug("routFrom", $routFrom);
        if ($to_number !== " ") {
            $routTo = ['name' => $to, 'number' => $to_number];
        } else {
            $routTo = ['name' => $to];
        }
        Log::debug("routTo", $routTo);
        $LatLngFrom = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application),
            $connectAPI
        );

        Log::debug("LatLngFrom", $LatLngFrom);
        $from_lat = $LatLngFrom["lat"];
        $from_lng = $LatLngFrom["lng"];
        $params["startLat"] = $from_lat; //
        $params["startLan"] = $from_lng; //

        $LatLngTo = (new UniversalAndroidFunctionController)->geoDataSearch(
            $to,
            $to_number,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application),
            $connectAPI
        );

        Log::debug("LatLngTo", $LatLngTo);
        $to_lat = $LatLngTo["lat"];
        $to_lng = $LatLngTo["lng"];
        $params["to_lat"] = $to_lat; //
        $params["to_lng"] = $to_lng; //
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
        Log::debug("rout", $rout);
        /**
         * Сохранние расчетов в базе
         */
        $params['from'] = $from;
        $params['from_number'] = $from_number;
        $params['to'] = $to;
        $params['to_number'] = $to_number;

        $required_time = null; //Время подачи предварительного заказа
        $reservation = false; //Обязательный. Признак предварительного заказа: True, False
        if ($time != "no_time") {
            $todayDate = strtotime($date);
            $todayDate = date("Y-m-d", $todayDate);
            list($hours, $minutes) = explode(":", $time);
            $required_time = $todayDate . "T" . str_pad($hours, 2, '0', STR_PAD_LEFT) . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
            $reservation = true; //Обязательный. Признак предварительного заказа: True, False
        }
//        (new UniversalAndroidFunctionController)->saveCost($params);

        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']), //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
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
        Log::debug("costSearch", $parameter);
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );
        Log::debug("costSearch" . $response->body());
        $response_arr = json_decode($response, true);
        Log::debug("response_arr: ", $response_arr);
        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        if (isset($response_arr["Message"]) && $city_count > 1) {
            $connectAPI = str_replace('http://', '', $connectAPI);
            switch ($application) {
                case "PAS1":
                    $cityServer = City_PAS1::where('address', $connectAPI)->first();
                    break;
                case "PAS2":
                    $cityServer = City_PAS2::where('address', $connectAPI)->first();
                    break;
                //case "PAS4":
                default:
                    $cityServer = City_PAS4::where('address', $connectAPI)->first();
                    break;
            }
            $cityServer->online = "false";
            $cityServer->save();
            (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

            while (self::connectAPIAppOrder($city, $application) != 400) {
                $connectAPI = self::connectAPIAppOrder($city, $application);
                $url = $connectAPI . '/api/weborders/tariffs/cost';
                Log::debug(" _____________________________");
                Log::debug(" connectAPI while $userArr[2]");
                Log::debug(" connectAPI while $city ");
                Log::debug(" connectAPI while $connectAPI ");
                Log::debug(" connectAPI while $url ");
                Log::debug(" ______________________________");

                $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

                if ($payment_type == 0) {
                    $authorization = $authorizationChoiceArr["authorization"];
                    Log::debug("authorization $authorization");
                } else {
                    $authorization = $authorizationChoiceArr["authorizationBonus"];
                    Log::debug("authorizationBonus $authorization");
                }
                $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                    $url,
                    $parameter,
                    $authorization,
                    self::identificationId($application),
                    (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                );
                $response_arr = json_decode($response, true);
                Log::debug("response_arr: ", $response_arr);
                if (!isset($response_arr[0]['error'])) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } elseif ($city_count > 1) {
                    $connectAPI = str_replace('http://', '', $connectAPI);
                    switch ($application) {
                        case "PAS1":
                            $cityServer = City_PAS1::where('address', $connectAPI)->first();
                            break;
                        case "PAS2":
                            $cityServer = City_PAS2::where('address', $connectAPI)->first();
                            break;
                        //case "PAS4":
                        default:
                            $cityServer = City_PAS4::where('address', $connectAPI)->first();
                            break;
                    }
                    $cityServer->online = "false";
                    $cityServer->save();
                    (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                } else {
                    $connectAPI = str_replace('http://', '', $connectAPI);
                    switch ($application) {
                        case "PAS1":
                            $cityServer = City_PAS1::where('address', $connectAPI)->first();
                            break;
                        case "PAS2":
                            $cityServer = City_PAS2::where('address', $connectAPI)->first();
                            break;
                        //case "PAS4":
                        default:
                            $cityServer = City_PAS4::where('address', $connectAPI)->first();
                            break;
                    }
                    (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
            if (self::connectAPIAppOrder($city, $application) == 400) {
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            Log::debug("response Message 33333333");

            if ($response->status() == 200) {
                return response($response, 200)
                    ->header('Content-Type', 'json');
            } else {
                $message = "Сбой в приложение $application, сервер $connectAPI: " . $response_arr;
                (new UniversalAndroidFunctionController)->sentErrorMessage($message);

                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        }

        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response == null) {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }


                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";
                Log::debug("response_error", $response_error);

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                Log::debug("response Message 33333333");

                if ($response->status() == 200) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } else {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
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
        $services,
        $city,
        $application
    ) {

        $connectAPI = self::connectAPIAppOrder($city, $application);

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

            return $response_error;
        }
        if ($tariff == " ") {
            $tariff = null;
        }

        $userArr = preg_split("/[*]+/", $user);

        $params['user_full_name'] = $userArr[0];
        if (count($userArr) >= 2) {
            $params['email'] = $userArr[1];
            (new UniversalAndroidFunctionController)->addUserNoNameWithEmailAndPhoneApp($params['email'], $phone, $application);
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


        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];




        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application);

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
            if ($payment_type == 0) {
                $route_undefined = true;
            } else {
                $route_undefined = false;
            }
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

        if ($from == $to) {
            $route_undefined = true;
            $combos_to = $combos_from;
            if ($comment == "no_comment") {
                $comment = "ПО ГОРОДУ.";
            } else {
                $comment = "ПО ГОРОДУ. " . $comment;
            }
        } else {
            $route_undefined = false;
        }

        if ($combos_from == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $from = $combos_from->toArray()['name'];
        }
        if ($combos_to == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не вірна адреса";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $to = $combos_to->toArray()['name'];
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
        $params["startLat"] = $from_lat; //
        $params["startLan"] = $from_lng; //
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
        $params["to_lat"] = $to_lat; //
        $params["to_lng"] = $to_lng; //

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

        if (strpos($comment, "ПО ГОРОДУ.") !== false) {
            $comment .= " ";
            if ($userArr[2] == 'bonus_payment'
                || $userArr[2] == 'fondy_payment'
                || $userArr[2] == 'mono_payment'
                || $userArr[2] == 'wfp_payment'
            ) {
                $comment .= "(Может быть продление маршрута)";
                $route_undefined = false;
            }
        }

        $url = $connectAPI . '/api/weborders';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $comment = str_replace("no_comment", "", $comment);
        if ($userArr[2] == 'nal_payment') {
            $comment = str_replace("ПО ГОРОДУ.", "", $comment);
        }

        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']), //Полное имя пользователя
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
        $responseBonusArr = null;

        if ($authorizationDouble != null) {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationBonus,
                $identificationId,
                $apiVersion
            );
            $responseBonusArr = json_decode($response, true);
            $responseFinal = $response;

            $responseBonusArr["url"] = $url;
            $responseBonusArr["parameter"] = $parameter;

            $parameter['payment_type'] = 0;

            $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

            $responseDoubleArr = json_decode($responseDouble, true);
            //Сообщение что нет обоих заказаов безнального и дубля
            if ($responseBonusArr != null
                && isset($responseBonusArr["Message"])
                && $responseDouble != null
                && isset($responseDoubleArr["Message"])
            ) {
                $response_error["order_cost"] = "0";
                $response_error["Message"] = $responseBonusArr["Message"];

                $message = $responseBonusArr["Message"];
                $blacklist_phrase = "Вы в черном списке";

                if (strpos($message, $blacklist_phrase) !== false) {
                    Log::debug("Сообщение содержит фразу 'Вы в черном списке'.");
                    $cityArr = (new CityController)->maxPayValueApp($city, $application);
                    $response_error["Message"] = $cityArr["black_list"];
                } else {
                    Log::debug("Сообщение не содержит фразу 'Вы в черном списке'.");
                }
                $message = "Ошибка заказа: " . $responseBonusArr["Message"]
                    . "Параметры запроса: " . json_encode($parameter, JSON_UNESCAPED_UNICODE);

                Log::error("orderSearchMarkersVisicom 111" . $message);
                (new DailyTaskController)->sentTaskMessage($message);
                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }

            if ($responseBonusArr == null
                || isset($responseBonusArr["Message"])
                && $responseDouble != null
                && !isset($responseDoubleArr["Message"])
            ) {
                $responseFinal = $responseDouble;
            }
            if (!isset($responseDoubleArr["Message"])) {
                $responseDoubleArr["url"] = $url;
                $responseDoubleArr["parameter"] = $parameter;
            }
        } else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorization,
                $identificationId,
                $apiVersion
            );
            $responseDoubleArr = null;
            $responseFinal = $response;
        }

        if ($responseFinal->status() == 200) {
            $response_arr = json_decode($responseFinal, true);

            $params["order_cost"] = $response_arr["order_cost"];

            $params["add_cost"] = $add_cost;
            $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
            $params['server'] = $connectAPI;

            $params['closeReason'] = "-1";
            $params['comment_info'] = $comment;
            $params['extra_charge_codes'] = implode(',', $extra_charge_codes);
            $params['payment_type'] = $payment_type;
            $params['pay_system'] = $userArr[2];
            if ($params['pay_system'] == "bonus_payment") {
                $params['bonus_status'] = 'hold';
            } else {
                $params['bonus_status'] = '';
            }
            Log::debug('Order Parameters:', $params);

            $response_ok["from_lat"] = $params["startLat"];
            $response_ok["from_lng"] = $params["startLan"];

            $response_ok["lat"] = $params["to_lat"];
            $response_ok["lng"] = $params["to_lng"];



            $response_ok["dispatching_order_uid"] = $response_arr["dispatching_order_uid"];
            $response_ok["order_cost"] = $response_arr["order_cost"];
            $response_ok["add_cost"] = $add_cost;
            $response_ok["currency"] = $response_arr["currency"];
            $response_ok["routefrom"] = $from;

            Log::debug("routefrom" . $from);

            $response_ok["routefromnumber"] = $from_number;
            $response_ok["routeto"] = $to;
            $response_ok["to_number"] = $to_number;
            $response_ok["required_time"] = date('d.m.Y H:i', strtotime($required_time));
            $response_ok["flexible_tariff_name"] = $tariff;
            $response_ok["comment_info"] = $comment;
            $response_ok["extra_charge_codes"] = $params['extra_charge_codes'];

            (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId($application));
            if (isset($responseBonusArr)
                && !isset($responseBonusArr["Message"])
                && $responseDoubleArr == null
            ) {
                //60 секунд на оплату водителю на карту
                Log::debug("StartStatusPaymentReview " . $responseFinal);
                Log::debug("dispatching_order_uid " .  $params['dispatching_order_uid']);
                StartStatusPaymentReview::dispatch ($params['dispatching_order_uid']);
            }

            //Запуск вилки
            if ($responseBonusArr != null
                && $responseDoubleArr != null
                && isset($responseBonusArr["dispatching_order_uid"])
                && isset($responseDoubleArr["dispatching_order_uid"])
            ) {
                $response_ok["dispatching_order_uid_Double"] = $responseDoubleArr["dispatching_order_uid"];
                $doubleOrder = new DoubleOrder();
                $doubleOrder->responseBonusStr = json_encode($responseBonusArr);
                $doubleOrder->responseDoubleStr = json_encode($responseDoubleArr);
                $doubleOrder->authorizationBonus = $authorizationBonus;
                $doubleOrder->authorizationDouble = $authorizationDouble;
                $doubleOrder->connectAPI = $connectAPI;
                $doubleOrder->identificationId = $identificationId;
                $doubleOrder->apiVersion = $apiVersion;
                $doubleOrder->save();

                $response_ok["doubleOrder"] = $doubleOrder->id;
                StartNewProcessExecution::dispatch($doubleOrder->id);

            }



            if (count($userArr) > 3) {
                $email = $params['email'];

                Log::debug("from_lat" . $response_ok["from_lat"]);
                Log::debug("from_lng" . $response_ok["from_lng"]);
                Log::debug("lat" . $response_ok["lat"]);
                Log::debug("lng" . $response_ok["lng"]);
                Log::debug("email" . $email);
                Log::debug("from" . $params["from"]);
                Log::debug("to" . $params["to"]);
                Log::debug("payment_type" . $payment_type);
                Log::debug("city" . $city);
                Log::debug("application" . $application);

                SearchOrderToDeleteJob::dispatch(
//                self::searchOrderToDelete(
                    $response_ok["from_lat"],
                    $response_ok["from_lng"],
                    $response_ok["lat"],
                    $response_ok["lng"],
                    $email,
                    $params["from"],
                    $params["to"],
                    $payment_type,
                    $city,
                    $application
                );
            }

            return response($response_ok, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = "0";
            $response_error["Message"] = $response_arr["Message"];

            $message = $response_arr["Message"];
            $blacklist_phrase = "Вы в черном списке";

            if (strpos($message, $blacklist_phrase) !== false) {
                Log::debug("Сообщение содержит фразу 'Вы в черном списке'.");
                $cityArr = (new CityController)->maxPayValueApp($city, $application);
                $response_error["Message"] = $cityArr["black_list"];
            } else {
                Log::debug("Сообщение не содержит фразу 'Вы в черном списке'.");
            }

            $message = "Ошибка заказа: " . $response_arr["Message"]
                . "Параметры запроса: " . json_encode($parameter, JSON_UNESCAPED_UNICODE);
            Log::error("orderSearchMarkersVisicom 111" . $message);
            (new DailyTaskController)->sentTaskMessage($message);
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }

        $connectAPI = self::connectAPIAppOrder($city, $application);
        Log::debug("1 connectAPI $connectAPI");

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

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

        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
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

        $route_undefined = false;


        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']),
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
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
        Log::debug("parameter ", $parameter);
        Log::debug("payment_type  $payment_type");
        if ($payment_type == 0) {
            $authorization = $authorizationChoiceArr["authorization"];
            Log::debug("authorization $authorization");
        } else {
            $authorization = $authorizationChoiceArr["authorizationBonus"];
            Log::debug("authorizationBonus $authorization");
        }
        Log::debug("____________________________________");
        Log::debug("authorization  $authorization");
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );

        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response == null) {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
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

                        $response_ok["currency"] = $response_arr["currency"];

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
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";
                Log::debug("response_error", $response_error);

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
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

                        $response_ok["currency"] = $response_arr["currency"];

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
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                Log::debug("response Message 33333333");

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
                    $response_ok["currency"] = $response_arr["currency"];

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
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function costSearchMarkersTime(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $tariff,
        $phone,
        $user,
        $time,
        $date,
        $services,
        $city,
        $application
    ) {
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }


        $connectAPI = self::connectAPIAppOrder($city, $application);
        Log::debug("1 connectAPI $connectAPI");

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

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

        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
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

        $route_undefined = false;
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

        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']),
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
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
        Log::debug("parameter ", $parameter);
        Log::debug("payment_type  $payment_type");
        if ($payment_type == 0) {
            $authorization = $authorizationChoiceArr["authorization"];
            Log::debug("authorization $authorization");
        } else {
            $authorization = $authorizationChoiceArr["authorizationBonus"];
            Log::debug("authorizationBonus $authorization");
        }
        Log::debug("____________________________________");
        Log::debug("authorization  $authorization");
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );

        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response == null) {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
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

                        $response_ok["currency"] = $response_arr["currency"];

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
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";
                Log::debug("response_error", $response_error);

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
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

                        $response_ok["currency"] = $response_arr["currency"];

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
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                Log::debug("response Message 33333333");

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
                    $response_ok["currency"] = $response_arr["currency"];

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
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        }
    }
    /**
     * @throws \Exception
     */
    public function costSearchMarkersLocal(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $tariff,
        $phone,
        $user,
        $services,
        $city,
        $application,
        $local
    ) {
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }

        $connectAPI = self::connectAPIAppOrder($city, $application);
//        Log::debug("1 connectAPI $connectAPI");

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

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

        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
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

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
            $params['to'] = 'по місту';
        } else {
            $route_undefined = false;

            $osmAddress = (new OpenStreetMapController)->reverseAddressLocal($toLatitude, $toLongitude, $local);

            $params['routetonumber'] = $osmAddress;

            $params['to'] = $osmAddress["result"];
            $params['to_number'] = " ";

        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
            ['name' => "name", 'lat' => $toLatitude, 'lng' => $toLongitude]
        ];
        $params['route_undefined'] = $route_undefined; //По городу: True, False

        $route_undefined = false;


        $url = $connectAPI . '/api/weborders/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']), //Полное имя пользователя
            'user_phone' => $phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
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
        Log::debug("parameter ", $parameter);
        if ($payment_type == 0) {
            $authorization = $authorizationChoiceArr["authorization"];
            Log::debug("authorization $authorization");
        } else {
            $authorization = $authorizationChoiceArr["authorizationBonus"];
            Log::debug("authorizationBonus $authorization");
        }
        Log::debug("____________________________________");
        Log::debug("authorization  $authorization");
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );
        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response == null) {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
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

                        $response_ok["currency"] = $response_arr["currency"];

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
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }


                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                $response_error["order_cost"] = 0;
                $response_error["Message"] = "ErrorMessage";
                Log::debug("response_error", $response_error);

                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }

                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
                    Log::debug("payment_type (while) $payment_type");
                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (isset($response_arr["order_cost"])) {
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

                        $response_ok["currency"] = $response_arr["currency"];

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
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }

            } else {
                Log::debug("response Message 33333333");

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
                    $response_ok["currency"] = $response_arr["currency"];

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
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function costSearchMarkersLocalTariffs(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $user,
        $services,
        $city,
        $application
    ) {
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }

        $connectAPI = self::connectAPIAppOrder($city, $application);
        Log::debug("1 connectAPI $connectAPI");

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

            return $response_error;
        }

        $userArr = preg_split("/[*]+/", $user);

        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
        $payment_type = $authorizationChoiceArr["payment_type"];

        $taxiColumnId = config('app.taxiColumnId');

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
        } else {
            $route_undefined = false;

        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
            ['name' => "name", 'lat' => $toLatitude, 'lng' => $toLongitude]
        ];

        $url = $connectAPI . '/api/weborders/tariffs/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };

        $calculated_tariff_names = [
            "Базовый",
            "Эконом-класс",
            "Бизнес-класс",
            "Премиум-класс",
            "Универсал",
            "Микроавтобус",
        ];

        $parameter = [
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'calculated_tariff_names' => $calculated_tariff_names,
            'required_time' => null, //Время подачи предварительного заказа
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
            'add_cost' => 0,
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
            'route_undefined' => $route_undefined, //По городу: True, False
        ];
        Log::debug("parameter ", $parameter);
        if ($payment_type == 0) {
            $authorization = $authorizationChoiceArr["authorization"];
        } else {
            $authorization = $authorizationChoiceArr["authorizationBonus"];
        }

        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );
        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response != null) {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }
                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/tariffs/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (!isset($response_arr[0]['error'])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            } else {
                Log::debug("response Message 33333333");

                if ($response->status() == 200) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } else {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        } else {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }
                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/tariffs/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (!isset($response_arr[0]['error'])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            } else {
                Log::debug("response Message 33333333");

                if ($response->status() == 200) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } else {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        }
    }
    public function costSearchMarkersLocalTariffsTime(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $user,
        $time,
        $date,
        $services,
        $city,
        $application
    ) {
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }

        $connectAPI = self::connectAPIAppOrder($city, $application);
        Log::debug("1 connectAPI $connectAPI");

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

            return $response_error;
        }

        $userArr = preg_split("/[*]+/", $user);

        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
        $payment_type = $authorizationChoiceArr["payment_type"];

        $taxiColumnId = config('app.taxiColumnId');

        if ($originLatitude == $toLatitude) {
            $route_undefined = true;
        } else {
            $route_undefined = false;

        }
        $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
            ['name' => "name", 'lat' => $originLatitude, 'lng' => $originLongitude],
            ['name' => "name", 'lat' => $toLatitude, 'lng' => $toLongitude]
        ];

        $url = $connectAPI . '/api/weborders/tariffs/cost';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };

        $required_time = null; //Время подачи предварительного заказа
        $reservation = false; //Обязательный. Признак предварительного заказа: True, False
        if ($time != "no_time") {
            $todayDate = strtotime($date);
            $todayDate = date("Y-m-d", $todayDate);
            list($hours, $minutes) = explode(":", $time);
            $required_time = $todayDate . "T" . str_pad($hours, 2, '0', STR_PAD_LEFT) . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
            $reservation = true; //Обязательный. Признак предварительного заказа: True, False
        };

        $calculated_tariff_names = [
            "Базовый",
            "Эконом-класс",
            "Бизнес-класс",
            "Премиум-класс",
            "Универсал",
            "Микроавтобус",
        ];

        $parameter = [
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route' => $rout,
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'calculated_tariff_names' => $calculated_tariff_names,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation,
            'route_address_entrance_from' => null,
            'comment' => "", //Комментарий к заказу
            'add_cost' => 0,
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            'extra_charge_codes' => $extra_charge_codes, //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
//            'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
            'route_undefined' => $route_undefined, //По городу: True, False
        ];
        Log::debug("parameter ", $parameter);
        if ($payment_type == 0) {
            $authorization = $authorizationChoiceArr["authorization"];
        } else {
            $authorization = $authorizationChoiceArr["authorizationBonus"];
        }

        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorization,
            self::identificationId($application),
            (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        );
        switch ($application) {
            case "PAS1":
                $city_count = City_PAS1::where('name', $city)->count();
                break;
            case "PAS2":
                $city_count = City_PAS2::where('name', $city)->count();
                break;
            //case "PAS4":
            default:
                $city_count = City_PAS4::where('name', $city)->count();
                break;
        }
        Log::debug("city_count: " . $city_count);

        if ($response != null) {
            $response_arr = json_decode($response, true);
            Log::debug("response_arr: ", $response_arr);

            if (isset($response_arr["Message"]) && $city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }
                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/tariffs/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (!isset($response_arr[0]['error'])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            } else {
                Log::debug("response Message 33333333");

                if ($response->status() == 200) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } else {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
        } else {
            if ($city_count > 1) {
                $connectAPI = str_replace('http://', '', $connectAPI);
                switch ($application) {
                    case "PAS1":
                        $cityServer = City_PAS1::where('address', $connectAPI)->first();
                        break;
                    case "PAS2":
                        $cityServer = City_PAS2::where('address', $connectAPI)->first();
                        break;
                    //case "PAS4":
                    default:
                        $cityServer = City_PAS4::where('address', $connectAPI)->first();
                        break;
                }
                $cityServer->online = "false";
                $cityServer->save();
                (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                while (self::connectAPIAppOrder($city, $application) != 400) {
                    $connectAPI = self::connectAPIAppOrder($city, $application);
                    $url = $connectAPI . '/api/weborders/tariffs/cost';
                    Log::debug(" _____________________________");
                    Log::debug(" connectAPI while $userArr[2]");
                    Log::debug(" connectAPI while $city ");
                    Log::debug(" connectAPI while $connectAPI ");
                    Log::debug(" connectAPI while $url ");
                    Log::debug(" ______________________________");

                    $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

                    if ($payment_type == 0) {
                        $authorization = $authorizationChoiceArr["authorization"];
                        Log::debug("authorization $authorization");
                    } else {
                        $authorization = $authorizationChoiceArr["authorizationBonus"];
                        Log::debug("authorizationBonus $authorization");
                    }
                    $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                        $url,
                        $parameter,
                        $authorization,
                        self::identificationId($application),
                        (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    );
                    $response_arr = json_decode($response, true);
                    Log::debug("response_arr: ", $response_arr);
                    if (!isset($response_arr[0]['error'])) {
                        return response($response, 200)
                            ->header('Content-Type', 'json');
                    } elseif ($city_count > 1) {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        $cityServer->online = "false";
                        $cityServer->save();
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);
                    } else {
                        $connectAPI = str_replace('http://', '', $connectAPI);
                        switch ($application) {
                            case "PAS1":
                                $cityServer = City_PAS1::where('address', $connectAPI)->first();
                                break;
                            case "PAS2":
                                $cityServer = City_PAS2::where('address', $connectAPI)->first();
                                break;
                            //case "PAS4":
                            default:
                                $cityServer = City_PAS4::where('address', $connectAPI)->first();
                                break;
                        }
                        (new UniversalAndroidFunctionController)->cityNoOnlineMessage($cityServer->id, $application);

                        $response_error["order_cost"] = 0;
                        $response_error["Message"] = "ErrorMessage";

                        return response($response_error, 200)
                            ->header('Content-Type', 'json');
                    }
                }
                if (self::connectAPIAppOrder($city, $application) == 400) {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            } else {
                Log::debug("response Message 33333333");

                if ($response->status() == 200) {
                    return response($response, 200)
                        ->header('Content-Type', 'json');
                } else {
                    $response_error["order_cost"] = 0;
                    $response_error["Message"] = "ErrorMessage";

                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
            }
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }
        $connectAPI = self::connectAPIAppOrder($city, $application);

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

            return $response_error;
        }
        $params["startLat"] = $originLatitude; //
        $params["startLan"] = $originLongitude; //
        $params["to_lat"] = $toLatitude; //
        $params["to_lng"] = $toLongitude; //


        if ($tariff == " ") {
            $tariff = null;
        }

        $userArr = preg_split("/[*]+/", $user);

        $params['user_full_name'] = $userArr[0];
        if (count($userArr) >= 2) {
            $params['email'] = $userArr[1];
            (new UniversalAndroidFunctionController)->addUserNoNameWithEmailAndPhoneApp($params['email'], $phone, $application);
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


        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);
        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];


        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application);

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
            if ($payment_type == 0) {
                $route_undefined = true;
            } else {
                $route_undefined = false;
            }
            $params['to'] = 'по місту';
            $params['to_number'] = " ";
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];
            if ($comment == "no_comment") {
                $comment = "ПО ГОРОДУ.";
            } else {
                $comment = "ПО ГОРОДУ. " . $comment;
            }
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


        $addressFrom = self::geoLatLanSearch($originLatitude, $originLongitude, $city, $application);
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

        if (strpos($comment, "ПО ГОРОДУ.") !== false) {
            $comment .= " ";
            if ($userArr[2] == 'bonus_payment'
                || $userArr[2] == 'fondy_payment'
                || $userArr[2] == 'mono_payment'
                || $userArr[2] == 'wfp_payment'
            ) {
                $comment .= "(Может быть продление маршрута)";
                $route_undefined = false;
            }
        }

        $url = $connectAPI . '/api/weborders';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $comment = str_replace("no_comment", "", $comment);
        if ($userArr[2] == 'nal_payment') {
            $comment = str_replace("ПО ГОРОДУ.", "", $comment);
        }
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']), //Полное имя пользователя
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

        Log::debug("response_arr: 000000 ", $parameter);
        $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $url,
            $parameter,
            $authorizationBonus,
            $identificationId,
            $apiVersion
        );
        $responseBonusArr = json_decode($response, true);
        $responseBonusArr["url"] = $url;
        Log::debug("response_arr: 00002222 ", $responseBonusArr);

        if (!isset($responseBonusArr["Message"])) {
            if ($authorizationDouble != null) {
                $responseBonusArr["parameter"] = $parameter;

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
                 $responseDouble = null;
            }
        }
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            if ($response_arr["order_cost"] != 0) {
                $params["order_cost"] = $response_arr["order_cost"];
//                $params["add_cost"] = 0;
                $params["add_cost"] = $add_cost;
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $params['server'] = $connectAPI;
//                sleep(5);
//                $params['closeReason'] = (new UIDController)->closeReasonUIDStatusFirst(
//                    $response_arr['dispatching_order_uid'],
//                    self::connectAPIAppOrder($city, $application),
//                    $authorization,
//                    self::identificationId($application)
//                );
                $params['closeReason'] = "-1";
                $params['comment_info'] = $comment;
                $params['extra_charge_codes'] = implode(',', $extra_charge_codes);
                $params['payment_type'] = $payment_type;
                $params['pay_system'] = $userArr[2];
                if ($params['pay_system'] == "bonus_payment") {
                    $params['bonus_status'] = 'hold';
                } else {
                    $params['bonus_status'] = '';
                }
                Log::debug('Order Parameters:', $params);
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
                $response_ok["required_time"] = date('d.m.Y H:i', strtotime($required_time));
                $response_ok["flexible_tariff_name"] = $tariff;
                $response_ok["comment_info"] = $comment;
                $response_ok["extra_charge_codes"] = $params['extra_charge_codes'];

                if ($responseDouble != null) {
                    $response_ok["dispatching_order_uid_Double"] = $responseDouble["dispatching_order_uid"];
                    $doubleOrder = new DoubleOrder();
                    $doubleOrder->responseBonusStr = json_encode($responseBonusArr);
                    $doubleOrder->responseDoubleStr = json_encode($responseDouble);
                    $doubleOrder->authorizationBonus = $authorizationBonus;
                    $doubleOrder->authorizationDouble = $authorizationDouble;
                    $doubleOrder->connectAPI = $connectAPI;
                    $doubleOrder->identificationId = $identificationId;
                    $doubleOrder->apiVersion = $apiVersion;
                    $doubleOrder->save();

                    $response_ok["doubleOrder"] = $doubleOrder->id;
                    StartNewProcessExecution::dispatch($doubleOrder->id);
//                    (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusEmu($doubleOrder->id);
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }

        $connectAPI = self::connectAPIAppOrder($city, $application);

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

            return $response_error;
        }
        $params["startLat"] = $originLatitude; //
        $params["startLan"] = $originLongitude; //
        $params["to_lat"] = $toLatitude; //
        $params["to_lng"] = $toLongitude; //

        if ($tariff == " ") {
            $tariff = null;
        }

        $userArr = preg_split("/[*]+/", $user);

        $params['user_full_name'] = $userArr[0];
        if (count($userArr) >= 2) {
            $params['email'] = $userArr[1];
            (new UniversalAndroidFunctionController)->addUserNoNameWithEmailAndPhoneApp($params['email'], $phone, $application);
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


        $authorizationChoiceArr = self::authorizationChoiceApp($userArr[2], $city, $connectAPI, $application);

        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];


        $identificationId = self::identificationId($application);
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application);


        $params['route_undefined'] = false; //По городу: True, False


        $taxiColumnId = config('app.taxiColumnId');

        /**
         * Откуда
         */

        $params['from_number'] = " ";

        $from = $start;
        $params['routefrom'] = $start;

        if ($originLatitude == $toLatitude) {
            if ($payment_type == 0) {
                $route_undefined = true;
            } else {
                $route_undefined = false;
            }

            $params['to'] = 'по місту';
            $params['to_number'] = " ";
            $rout = [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude],
                ['name' => $from, 'lat' => $originLatitude, 'lng' => $originLongitude]
            ];
            if ($comment == "no_comment") {
                $comment = "ПО ГОРОДУ.";
            } else {
                $comment = "ПО ГОРОДУ. " . $comment;
            }
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

        if (strpos($comment, "ПО ГОРОДУ.") !== false) {
            $comment .= " ";
            if ($userArr[2] == 'bonus_payment'
                || $userArr[2] == 'fondy_payment'
                || $userArr[2] == 'mono_payment'
                || $userArr[2] == 'wfp_payment'
            ) {
                $comment .= "(Может быть продление маршрута)";
                $route_undefined = false;
            }
        }


        $url = $connectAPI . '/api/weborders';

        $extra_charge_codes = preg_split("/[*]+/", $services);
        if ($extra_charge_codes[0] == "no_extra_charge_codes") {
            $extra_charge_codes = [];
        };
        $comment = str_replace("no_comment", "", $comment);
        if ($userArr[2] == 'nal_payment') {
            $comment = str_replace("ПО ГОРОДУ.", "", $comment);
        }
        $parameter = [
            'user_full_name' => preg_replace('/\s*\(.*?\)/', '', $params['user_full_name']), //Полное имя пользователя
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
        Log::debug("response_arr: 1111111122 ", $parameter);

        $responseDoubleArr = null;
        $responseBonusArr = null;

        if ($payment_type == 0) {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorization,
                $identificationId,
                $apiVersion
            );
            $responseArr = json_decode($response, true);
            Log::debug("response_arr: 2222222233 ", $responseArr);
            $responseFinal = $response;
            if (isset($responseArr['dispatching_order_uid'])) {
                (new DriverMemoryOrderController)->store(
                    $responseArr['dispatching_order_uid'],
                    json_encode($parameter, JSON_UNESCAPED_UNICODE),
                    $authorization,
                    $url,
                    $identificationId,
                    $apiVersion
                );
            }

        }
        else {
            $response = (new UniversalAndroidFunctionController)->postRequestHTTP(
                $url,
                $parameter,
                $authorizationBonus,
                $identificationId,
                $apiVersion
            );
            $responseFinal = $response;
            $responseBonusArr = json_decode($response, true);
            $responseBonusArr["url"] = $url;
            Log::debug("responseBonusArr: 333333333344 ", $responseBonusArr);

            if (isset($responseBonusArr['dispatching_order_uid'])) {
                (new DriverMemoryOrderController)->store(
                    $responseBonusArr['dispatching_order_uid'],
                    json_encode($parameter, JSON_UNESCAPED_UNICODE),
                    $authorization,
                    $url,
                    $identificationId,
                    $apiVersion
                );
            }
            if ($authorizationDouble != null) {
                $responseBonusArr["parameter"] = $parameter;

                $parameter['payment_type'] = 0;

                $responseDouble = (new UniversalAndroidFunctionController)->postRequestHTTP(
                    $url,
                    $parameter,
                    $authorizationDouble,
                    $identificationId,
                    $apiVersion
                );

                $responseDoubleArr = json_decode($responseDouble, true);
                Log::debug("responseDoubleArr: 44444444444 ", $responseDoubleArr);

                //Сообщение что нет обоих заказаов безнального и дубля
                if ($responseBonusArr != null
                    && isset($responseBonusArr["Message"])
                    && $responseDoubleArr != null
                    && isset($responseDoubleArr["Message"])
                ) {

                    $response_error["order_cost"] = "0";
                    $response_error["Message"] = $responseBonusArr["Message"];

                    $response_error["order_cost"] = "0";

                    $message = $response_error["Message"];
                    $blacklist_phrase = "Вы в черном списке";

                    if (strpos($message, $blacklist_phrase) !== false) {
                        Log::debug("Сообщение содержит фразу 'Вы в черном списке'.");
                        $cityArr = (new CityController)->maxPayValueApp($city, $application);
                        $response_error["Message"] = $cityArr["black_list"];
                    } else {
                        Log::debug("Сообщение не содержит фразу 'Вы в черном списке'.");
                    }


                    $message = "Ошибка заказа: " . $responseBonusArr["Message"]
                        . "Параметры запроса: " . json_encode($parameter, JSON_UNESCAPED_UNICODE);
                    Log::error("orderSearchMarkersVisicom 111" . $message);
                    (new MessageSentController)->sentMessageAdmin($message);
                    return response($response_error, 200)
                        ->header('Content-Type', 'json');
                }
                if ($responseBonusArr == null
                    || isset($responseBonusArr["Message"])
                    && $responseDoubleArr != null
                    && !isset($responseDoubleArr["Message"])
                ) {
                    $responseFinal = $responseDouble;
                }
                if (!isset($responseDoubleArr["Message"])) {
                    $responseDoubleArr["url"] = $url;
                    $responseDoubleArr["parameter"] = $parameter;
                } else {

                    $messageAdmin = "orderSearchMarkersVisicom: дубль  не создался";
                    (new MessageSentController)->sentMessageAdmin($messageAdmin);
                    $responseDoubleArr = null;
                }

            }
        }

        if ($responseFinal->status() == 200) {
            $response_arr = json_decode($responseFinal, true);
            if (isset($response_arr["order_cost"]) && $response_arr["order_cost"] != 0) {
                $params["order_cost"] = $response_arr["order_cost"];

                $params["add_cost"] = $add_cost;
                $params['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $params['server'] = $connectAPI;

                $params['closeReason'] = "-1";
                $params['comment_info'] = $comment;
                $params['extra_charge_codes'] = implode(',', $extra_charge_codes);
                $params['payment_type'] = $payment_type;
                $params['pay_system'] = $userArr[2];
                if ($params['pay_system'] == "bonus_payment") {
                    $params['bonus_status'] = 'hold';
                } else {
                    $params['bonus_status'] = '';
                }
                Log::debug('Order Parameters:', $params);
                (new UniversalAndroidFunctionController)->saveOrder($params, self::identificationId($application));
                Log::debug("Перед проверкой условий:");
                Log::debug("Содержимое \$responseBonusArr: " . json_encode($responseBonusArr));
                Log::debug("Содержимое \$responseDoubleArr: " . json_encode($responseDoubleArr));
                Log::debug("Содержимое \$params: " . json_encode($params));
                if (isset($responseBonusArr)
                    && !isset($responseBonusArr["Message"])
                    && $responseDoubleArr == null
                ) {
                    //60 секунд на оплату водителю на карту
                    Log::debug("StartStatusPaymentReview " . $responseFinal);
                    Log::debug("dispatching_order_uid " .  $params['dispatching_order_uid']);
                    StartStatusPaymentReview::dispatch ($params['dispatching_order_uid']);
                }

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
                $response_ok["required_time"] = date('d.m.Y H:i', strtotime($required_time));
                $response_ok["flexible_tariff_name"] = $tariff;
                $response_ok["comment_info"] = $comment;
                $response_ok["extra_charge_codes"] = $params['extra_charge_codes'];
                if ($responseBonusArr != null) {
                    Log::debug("responseBonusArr", $responseBonusArr);
                }
                if ($responseDoubleArr != null) {
                    Log::debug("responseDoubleArr", $responseDoubleArr);
                }

                ;
                //Запуск вилки
                if ($responseBonusArr != null
                    && $responseDoubleArr != null
                    && isset($responseBonusArr["dispatching_order_uid"])
                    && isset($responseDoubleArr["dispatching_order_uid"])
                ) {
                    Log::debug("responseDoubleArr: 44444444 ", $responseDoubleArr);
                    $response_ok["dispatching_order_uid_Double"] = $responseDoubleArr["dispatching_order_uid"];

                    Log::debug("******************************");
                    Log::debug("DoubleOrder parameters1111: ", [
                        'responseBonusStr' => json_encode($responseBonusArr),
                        'responseDoubleStr' => json_encode($responseDoubleArr),
                        'authorizationBonus' => $authorizationBonus,
                        'authorizationDouble' => $authorizationDouble,
                        'connectAPI' => $connectAPI,
                        'identificationId' => $identificationId,
                        'apiVersion' => $apiVersion
                    ]);

                    Log::debug("******************************");



                    $response_ok["dispatching_order_uid_Double"] = $responseDoubleArr["dispatching_order_uid"];
                    $doubleOrder = new DoubleOrder();
                    $doubleOrder->responseBonusStr = json_encode($responseBonusArr);
                    $doubleOrder->responseDoubleStr = json_encode($responseDoubleArr);
                    $doubleOrder->authorizationBonus = $authorizationBonus;
                    $doubleOrder->authorizationDouble = $authorizationDouble;
                    $doubleOrder->connectAPI = $connectAPI;
                    $doubleOrder->identificationId = $identificationId;
                    $doubleOrder->apiVersion = $apiVersion;

                    Log::debug("Values set in DoubleOrder:", [
                        'responseBonusStr' => $doubleOrder->responseBonusStr,
                        'responseDoubleStr' => $doubleOrder->responseDoubleStr,
                        'authorizationBonus' => $doubleOrder->authorizationBonus,
                        'authorizationDouble' => $doubleOrder->authorizationDouble,
                        'connectAPI' => $doubleOrder->connectAPI,
                        'identificationId' => $doubleOrder->identificationId,
                        'apiVersion' => $doubleOrder->apiVersion,
                    ]);

                    $doubleOrder->save();

                    $response_ok["doubleOrder"] = $doubleOrder->id;
                    Log::info("doubleOrder->id" . $doubleOrder->id);
                    Log::debug("StartNewProcessExecution 5895");
                    Log::debug("response_arr22222:" . json_encode($doubleOrder->toArray()));
                    StartNewProcessExecution::dispatch($doubleOrder->id);
//                    (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusEmu($doubleOrder->id);


                }
                if (count($userArr) > 3) {
                    $email = $params['email'];
//                    SearchOrderToDeleteJob::dispatch(
                    self::searchOrderToDelete(
                        $originLatitude,
                        $originLongitude,
                        $toLatitude,
                        $toLongitude,
                        $email,
                        $start,
                        $finish,
                        $payment_type,
                        $city,
                        $application
                    );
                }
                return response($response_ok, 200)
                    ->header('Content-Type', 'json');
            } else {
                $response_arr = json_decode($response, true);

                $response_error["order_cost"] = "0";

                $message = $response_arr["Message"];
                $blacklist_phrase = "Вы в черном списке";

                if (strpos($message, $blacklist_phrase) !== false) {
                    Log::debug("Сообщение содержит фразу 'Вы в черном списке'.");
                    $cityArr = (new CityController)->maxPayValueApp($city, $application);
                    $response_error["Message"] = $cityArr["black_list"];
                } else {
                    Log::debug("Сообщение не содержит фразу 'Вы в черном списке'.");
                    $response_error["Message"] = $response_arr["Message"];
                }

                $message = "Ошибка заказа в приложение $application, сервер $connectAPI: " . json_encode($response_arr);
                (new DailyTaskController)->sentTaskMessage($message);


                return response($response_error, 200)
                    ->header('Content-Type', 'json');
            }
        } else {
            $response_arr = json_decode($response, true);

            $response_error["order_cost"] = "0";

            $message = $response_arr["Message"];
            $blacklist_phrase = "Вы в черном списке";

            if (strpos($message, $blacklist_phrase) !== false) {
                Log::debug("Сообщение содержит фразу 'Вы в черном списке'.");
                $cityArr = (new CityController)->maxPayValueApp($city, $application);
                $response_error["Message"] = $cityArr["black_list"];
            } else {
                Log::debug("Сообщение не содержит фразу 'Вы в черном списке'.");
                $response_error["Message"] = $response_arr["Message"];
            }

            $message = "Ошибка заказа в приложение $application, сервер $connectAPI: " . json_encode($response_arr, JSON_UNESCAPED_UNICODE);
            (new DailyTaskController)->sentTaskMessage($message);

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

        if ($osmAddress != "404") {
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
    public function fromSearchGeoLocal($originLatitude, $originLongitude, $local)
    {

        $osmAddress = (new OpenStreetMapController)->reverseAddressLocal($originLatitude, $originLongitude, $local);

        if ($osmAddress != "404") {
            $from = $osmAddress;

            $response["order_cost"] = 100;
            $response["route_address_from"] = $osmAddress['result'] . "\t";

        }
//        dd( $response);
        return response($response, 200)
            ->header('Content-Type', 'json');
    }

    /**
     * @throws \Exception
     */
    public function geoLatLanSearch(
        $originLatitude,
        $originLongitude,
        $city,
        $application
    ): array {

        $connectAPI = self::connectAPIAppOrder($city, $application);

        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "ErrorMessage";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/nearest';


        $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
        $r = 50;
        do {
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => self::identificationId($application),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
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
//        $connectAPI = self::connectApi($city);
//        if ($connectAPI == 400) {
//            $response_error["resp_result"] = 200;
//            $response_error["message"] = 200;
//
//
//            return response($response_error, 200)
//                ->header('Content-Type', 'json');
//        } else {
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
        Log::debug("autocompleteSearchComboHid", $combos->toArray());
        Log::debug("autocompleteSearchComboHid $combos->toArray()['street']");
        if ($combos != null) {
            $response["resp_result"] = 0;
            $response["message"] = $combos->toArray()['street'];
        } else {
            $response["resp_result"] = 400;
            $response["message"] = 400;

        }
        return response($response, 200)
            ->header('Content-Type', 'json');
//        }
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
    public function myHistory()
    {

        $city = "OdessaTest";
        $application = "PAS2";
        $connectAPI = self::connectApi($city);

        $url = $connectAPI . '/api/clients/ordershistory';
        $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
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
        $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => self::identificationId($application),
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }
        $resp_answer = "";
        $uid = (new MemoryOrderChangeController)->show($uid);

        (new FCMController)->deleteDocumentFromFirestore($uid);
        (new FCMController)->deleteDocumentFromFirestoreOrdersTakingCancel($uid);
        (new FCMController)->deleteDocumentFromSectorFirestore($uid);
        (new FCMController)->writeDocumentToHistoryFirestore($uid, "cancelled");

        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($orderweb) {
            $connectAPI = $orderweb->server;

            $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
            $url = $connectAPI . '/api/weborders/cancel/' . $uid;
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => self::identificationId($application),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
            ])->put($url);

            $json_arrWeb = json_decode($response, true);

            Log::debug("json_arrWeb_bonus", $json_arrWeb);
            if ($json_arrWeb["order_client_cancel_result"] != 1) {
                self::repeatCancel(
                    $url,
                    $authorization,
                    $application,
                    $city,
                    $connectAPI,
                    $uid
                );
            }
            $resp_answer = "Запит на скасування замовлення надіслано. ";

            switch ($json_arrWeb['order_client_cancel_result']) {
                case '0':
                    $resp_answer = $resp_answer . "Замовлення не вдалося скасувати.";
                    break;
                case '1':
                    $resp_answer = $resp_answer . "Замовлення скасоване.";
                    $orderweb->closeReason = "1";
                    $orderweb->save();
                    break;
                case '2':
                    $resp_answer = $resp_answer . "Вимагає підтвердження клієнтом скасування диспетчерської.";
                    break;
                default:
                    $resp_answer = $resp_answer . "Статус поїздки дізнайтесь у диспетчера.";
            }
//        dd($resp_answer);
//

            (new MessageSentController)->sentCancelInfo($orderweb);
        }

        return [
            'response' => $resp_answer,
        ];
    }

    /**
     * @throws \Exception
     */
    public function webordersCancelDouble(
        $uid,
        $uid_Double,
        $payment_type,
        $city,
        $application
    ) {
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }
        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::debug("**********************************************************");
        Log::debug("webordersCancelDouble uid $uid");
        Log::debug("webordersCancelDouble uid_Double  $uid_Double");
        Log::debug("webordersCancelDouble payment_type  $payment_type");
        Log::debug("webordersCancelDouble city  $city");

        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($orderweb) {
            $connectAPI = $orderweb->server;

            $authorizationChoiceArr = self::authorizationChoiceApp($payment_type, $city, $connectAPI, $application);
            $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
            $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];

            $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

            if ($uid_history) {
                $uid = $uid_history->uid_bonusOrder;
                $uid_Double = $uid_history->uid_doubleOrder;
                $uid_history->cancel = true;
                $uid_history->save();

                Log::debug("uid_history webordersCancelDouble :", $uid_history->toArray());
            }

            $url = $connectAPI . '/api/weborders/cancel/' . $uid;
            Log::debug(" webordersCancelDouble bonus $url");
            $response_bonus = Http::withHeaders([
                "Authorization" => $authorizationBonus,
                "X-WO-API-APP-ID" => self::identificationId($application),
//                "X-API-VERSION" => (new UniversalAndroidFunctionController)
//                    ->apiVersionApp($city, $connectAPI, $application)
            ])->put($url);
            $json_arrWeb_bonus = json_decode($response_bonus, true);
            Log::debug("json_arrWeb_bonus", $json_arrWeb_bonus);
            if ($json_arrWeb_bonus["order_client_cancel_result"] != 1) {
                self::repeatCancel(
                    $url,
                    $authorizationBonus,
                    $application,
                    $city,
                    $connectAPI,
                    $uid
                );
            }
            if($uid_Double != " ") {
                  $url = $connectAPI . '/api/weborders/cancel/' . $uid_Double;
                  Log::debug(" webordersCancelDouble double $url");

                  $response_double =Http::withHeaders([
                      "Authorization" => $authorizationDouble,
                      "X-WO-API-APP-ID" => self::identificationId($application),
            //                "X-API-VERSION" => (new UniversalAndroidFunctionController)
            //                    ->apiVersionApp($city, $connectAPI, $application)
                  ])->put($url);


                  $json_arrWeb_double = json_decode($response_double, true);
                  Log::debug("json_arrWeb_double", $json_arrWeb_double);
                  if ($json_arrWeb_double["order_client_cancel_result"] != 1) {
                      self::repeatCancel(
                          $url,
                          $authorizationDouble,
                          $application,
                          $city,
                          $connectAPI,
                          $uid_Double
                      );
                  }
              }

            $resp_answer = "Запит на скасування замовлення надіслано. ";

            $hold = false;
            if ($json_arrWeb_bonus['order_client_cancel_result'] == 1) {
                if (!isset($json_arrWeb_double)) {
                    $hold = true;
                } else  if ($json_arrWeb_double['order_client_cancel_result'] == 1) {
                    $hold = true;
                }
            }


//            $orderweb->closeReason = "1";
            $orderweb->save();
            (new MessageSentController)->sentCancelInfo($orderweb);
            Log::debug("webordersCancelDouble response $resp_answer");
            Log::debug("**********************************************************");



//            if ($hold) {
//                $resp_answer = $resp_answer . "Замовлення скасоване.";
//            } else {
//                $resp_answer = $resp_answer . "Замовлення не вдалося скасувати.";
//            }
        } else {
            $resp_answer = "Замовлення не вдалося скасувати.";
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }
        //Поиск смены номеров заказов
        Log::debug("0 historyUIDStatus uid $uid");
        $uid = (new MemoryOrderChangeController)->show($uid);

        Log::debug("1 historyUIDStatus uid $uid");
        $orderweb_uid = Orderweb::where("dispatching_order_uid", $uid)->first();

        if ($orderweb_uid->closeReason == 101 || $orderweb_uid->closeReason == 102) {
            $storedData = $orderweb_uid->auto;

            $dataDriver = json_decode($storedData, true);
//            $name = $dataDriver["name"];
            $color = $dataDriver["color"];
            $brand = $dataDriver["brand"];
            $model = $dataDriver["model"];
            $number = $dataDriver["number"];
            $phoneNumber = $dataDriver["phoneNumber"];

            $auto = "$number, цвет $color  $brand $model. ";


            // Обновление полей
            $responseData['order_car_info'] = $auto; // Замените на ваш существующий $auto
            $responseData['driver_phone'] = $phoneNumber; // Замените на ваш существующий $phoneNumber
            $responseData['time_to_start_point'] = $orderweb_uid->time_to_start_point; // Замените на ваш существующий $phoneNumber
            switch ($orderweb_uid->closeReason) {
                case "101":
                    $responseData['execution_status'] = 'CarFound'; // Обновление статуса
                    break;
                case "102":
                    $responseData['execution_status'] = 'CarInStartPoint'; // Обновление статуса
                    break;
            }
            return $responseData;
        } else {
            $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

            $connectAPI = $orderweb_uid->server;
            if ($uid_history) {
                $uid_history->bonus_status = null;
                //Запрос статуса по ветке
                if ($uid_history->bonus_status == null) {
                    //Запрос по окончанию вилки статуса безнала

                    $authorization = (new UniversalAndroidFunctionController)
                        ->authorizationApp($city, $connectAPI, $application);
                    $url = $connectAPI . '/api/weborders/' . $uid_history->uid_bonusOrder;
                    try {
                        $response_bonus = Http::withHeaders([
                            "Authorization" => $authorization,
                            "X-WO-API-APP-ID" => self::identificationId($application),
                            "X-API-VERSION" => (new UniversalAndroidFunctionController)
                                ->apiVersionApp($city, $connectAPI, $application)
                        ])->get($url);

                        // Логируем тело ответа
//                        Log::debug(" 2 historyUIDStatus postRequestHTTP: " . json_decode($response_bonus, true));


                        // Проверяем успешность ответа
                        if ($response_bonus->successful()) {
                            // Обрабатываем успешный ответ
                            $response_bonus_arr = json_decode($response_bonus, true);

                            $messageAdmin = "Опрос статуса безналичного заказа $uid_history->uid_bonusOrder Ответ:" . print_r($response_bonus_arr, true);
                            (new MessageSentController)->sentMessageAdmin($messageAdmin);

                            if ($response_bonus_arr["close_reason"] == 0 || $response_bonus_arr["close_reason"] == 8
                                || $response_bonus_arr["close_reason"] == -1) {
                                //Безнал по окончанию вилки выполнен или в работе

                                if ($response_bonus_arr["order_car_info"] != null) {
                                    $orderweb_uid->auto = $response_bonus_arr["order_car_info"];
                                }
                                $orderweb_uid->closeReason = $response_bonus_arr["close_reason"];
                                $orderweb_uid->save();
                                return $response_bonus;
                            } else {
                                //Безнал по окончанию вилки закрыт
                                //Запрос по окончанию вилки статуса нала
                                $url = $connectAPI . '/api/weborders/' . $uid_history->uid_doubleOrder;
                                try {
                                    $response_double = Http::withHeaders([
                                        "Authorization" => $authorization,
                                        "X-WO-API-APP-ID" => self::identificationId($application),
                                        "X-API-VERSION" => (new UniversalAndroidFunctionController)
                                            ->apiVersionApp($city, $connectAPI, $application)
                                    ])->get($url);

                                    // Логируем тело ответа
                                    Log::debug("3 historyUIDStatus postRequestHTTP: " . $response_double->body());

                                    // Проверяем успешность ответа
                                    if ($response_double->successful()) {
                                        // Обрабатываем успешный ответ
                                        $response_arr_double = json_decode($response_double, true);
                                        Log::debug("4 $url: ", $response_arr_double);

                                        $messageAdmin = "Опрос статуса наличного дублирующего заказ $uid_history->uid_doubleOrder Ответ:" . print_r($response_arr_double, true);
                                        (new MessageSentController)->sentMessageAdmin($messageAdmin);

                                        if ($response_arr_double["order_car_info"] != null) {
                                            $orderweb_uid->auto = $response_arr_double["order_car_info"];
                                        }
                                        $orderweb_uid->closeReason = $response_arr_double["close_reason"];
                                        $orderweb_uid->save();
                                        return $response_double;

                                    } else {
                                        // Логируем ошибки в случае неудачного запроса
                                        Log::error("5 historyUIDStatus Request failed with status: "
                                            . $response_double->status());
                                        Log::error("6 historyUIDStatus Response: " . $response_double->body());
                                        $response_arr_double = json_decode($response_double, true);
                                        $messageAdmin = "Ошибка опроса статуса наличного дублирующего заказа $uid_history->uid_doubleOrder Ответ:" . print_r($response_arr_double, true);
                                        (new MessageSentController)->sentMessageAdmin($messageAdmin);

                                        return [
                                            "execution_status" => "failed_status",
                                            "order_car_info" => null,
                                            "driver_phone" => null,
                                        ];
                                    }
                                } catch (\Exception $e) {
                                    // Обработка исключений
                                    Log::error("7 historyUIDStatus Exception caught: " . $e->getMessage());

                                    $messageAdmin = "Ошибка опроса статуса наличного дублирующего заказа $uid_history->uid_doubleOrder Ответ:" . $e->getMessage();
                                    (new MessageSentController)->sentMessageAdmin($messageAdmin);

                                    return [
                                        "execution_status" => "failed_status",
                                        "order_car_info" => null,
                                        "driver_phone" => null,
                                    ];
                                }
                            }
                        } else {
                            // Логируем ошибки в случае неудачного запроса
                            Log::error("11 historyUIDStatus Request failed with status: " . $response_bonus->status());
                            Log::error("12 historyUIDStatus Response: " . $response_bonus->body());
                            $response_arr_bonus = json_decode($response_bonus, true);
                            $messageAdmin = "Ошибка опроса статуса безналичного заказа 11 $uid_history->uid_bonusOrder Ответ:" . print_r($response_arr_bonus, true);
                            (new MessageSentController)->sentMessageAdmin($messageAdmin);


                            return [
                                "execution_status" => "failed_status",
                                "order_car_info" => null,
                                "driver_phone" => null,
                            ];
                        }
                    } catch (\Exception $e) {
                        // Обработка исключений
                        Log::error("13 historyUIDStatus Exception caught: " . print_r($e->getMessage(), true));


                        $messageAdmin = "Ошибка опроса статуса безналичного заказа 13 $uid_history->uid_bonusOrder Ответ: ";

                        // Проверяем тип сообщения
                        if (is_array($e->getMessage())) {
                            $messageAdmin .= print_r($e->getMessage(), true);
                        } elseif (is_object($e->getMessage())) {
                            $messageAdmin .= json_encode($e->getMessage());
                        } else {
                            $messageAdmin .= (string)$e->getMessage();
                        }
                        Log::error("14 historyUIDStatus Exception caught: " . $messageAdmin);
                        (new MessageSentController)->sentMessageAdmin($messageAdmin);

                        return [
                            "execution_status" => "failed_status",
                            "order_car_info" => null,
                            "driver_phone" => null,
                        ];
                    }
                } else {
                    $response_bonus = $uid_history->bonus_status;
                    // Логируем тело ответа
                    Log::debug("15 historyUIDStatus respons bonus: " . $response_bonus);
                    // Обрабатываем успешный ответ
                    $response_bonus_arr = json_decode($response_bonus, true);
                    if ($response_bonus_arr["close_reason"] == 0 || $response_bonus_arr["close_reason"] == 8
                        || $response_bonus_arr["close_reason"] == -1) {


                        if ($response_bonus_arr["order_car_info"] != null) {
                            $orderweb_uid->auto = $response_bonus_arr["order_car_info"];
                        }
                        $orderweb_uid->closeReason = $response_bonus_arr["close_reason"];
                        $orderweb_uid->save();
                        return $response_bonus;
                    } else {
                        $response_double = $uid_history->double_status;

                        // Логируем тело ответа
                        Log::debug("historyUIDStatus response double: " . $response_double);

                        // Обрабатываем успешный ответ
                        $response_double_arr = json_decode($response_double, true);
                        Log::debug("responseArr double: ", $response_double_arr);


                        if ($response_double_arr["order_car_info"] != null) {
                            $orderweb_uid->auto = $response_double_arr["order_car_info"];
                        }
                        $orderweb_uid->closeReason = $response_double_arr["close_reason"];
                        $orderweb_uid->save();
                        return $response_double;
                    }
                }
            } else {
                //Запрос статуса одиночного заказа
                $authorization = (new UniversalAndroidFunctionController)
                    ->authorizationApp($city, $connectAPI, $application);
                $url = $connectAPI . '/api/weborders/' . $uid;
                try {
                    $response = Http::withHeaders([
                        "Authorization" => $authorization,
                        "X-WO-API-APP-ID" => self::identificationId($application),
                        "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
                    ])->get($url);

                    // Логируем тело ответа
                    Log::debug("historyUIDStatus postRequestHTTP: " . $response->body());

                    // Проверяем успешность ответа
                    if ($response->successful()) {
                        // Обрабатываем успешный ответ
                        $response_arr = json_decode($response, true);
                        Log::debug("$url: ", $response_arr);

                        if ($response_arr["order_car_info"] != null) {
                            $orderweb_uid->auto = $response_arr["order_car_info"];
                        }
                        $orderweb_uid->closeReason = $response_arr["close_reason"];
                        $orderweb_uid->save();
                        return $response;

                    } else {
                        // Логируем ошибки в случае неудачного запроса
                        Log::error("historyUIDStatus Request failed with status: " . $response->status());
                        Log::error("historyUIDStatus Response: " . $response->body());

                        return [
                            "execution_status" => "failed_status",
                            "order_car_info" => null,
                            "driver_phone" => null,
                        ];
                    }
                } catch (\Exception $e) {
                    // Обработка исключений
                    Log::error("historyUIDStatus Exception caught: " . $e->getMessage());
                    return [
                        "execution_status" => "failed_status",
                        "order_car_info" => null,
                        "driver_phone" => null,
                    ];
                }

            }
        }
    }

    /**
     * @throws \Exception
     */
    public function driverCarPosition(
        $uid,
        $city,
        $application
    ) {
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
            case "Сhernivtsi":
            case "Lutsk":
            case "foreign countries":
                $city = "OdessaTest";
                break;
        }
        //Поиск смены номеров заказов
        Log::debug("0 drivercarposition uid $uid");
        $uid = (new MemoryOrderChangeController)->show($uid);

        Log::debug("1 drivercarposition uid $uid");
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        $connectAPI = $orderweb->server;
        sleep(5);
        $authorization = (new UniversalAndroidFunctionController)
            ->authorizationApp($city, $connectAPI, $application);
        $url = $connectAPI . '/api/weborders/drivercarposition/' . $orderweb->dispatching_order_uid;
        try {
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => self::identificationId($application),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)
                    ->apiVersionApp($city, $connectAPI, $application)
            ])->get($url);

            // Логируем тело ответа
            Log::debug(" 2 drivercarposition getRequestHTTP: " . $response->body());

            // Проверяем успешность ответа
            if ($response->successful()) {
                // Обрабатываем успешный ответ
                $response_arr = json_decode($response, true);
                if ($response_arr['lat'] !=0) {
                    (new UniversalAndroidFunctionController)->calculateTimeToStart($orderweb, $response_arr);
                }

            }
        } catch (\Exception $e) {
            // Обработка исключений
            Log::error("5 drivercarposition Exception caught: " . $e->getMessage());
        }
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
     * @throws \Exception
     */
    public function onlineAPIApp(string $city, $app): string
    {
        switch ($app) {
            case "PAS1":
                return (new CityPas1Controller)->cityOnline($city);
            case "PAS2":
                return (new CityPas2Controller)->cityOnline($city);
            //case "PAS4":
            default:
                return (new CityPas4Controller)->cityOnline($city);
        }
    }
    /**
     * @throws \Exception
     */
    public function onlineAPIAppOrder(string $city, $app): string
    {
        switch ($app) {
            case "PAS1":
                return (new CityPas1Controller)->cityOnlineOrder($city);
            case "PAS2":
                return (new CityPas2Controller)->cityOnlineOrder($city);
            //case "PAS4":
            default:
                return (new CityPas4Controller)->cityOnlineOrder($city);
        }
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
        $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
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
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);
        $json_arr = json_decode($json_str, true);
//        dd($json_arr);
        $url_ob = $connectAPI . '/api/geodata/objects';
        $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
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

//        $svd = Config::where('id', '1')->first();
//        $svd->odessa_versionDate = $json_arr['version_date'];
//        $svd->save();

        return redirect()->route('home-admin')->with('success', "База $base обновлена.");
    }

    public function authorizationChoiceApp(
        $payment,
        $city,
        $connectAPI,
        $application
    ): array {
        $authorizationChoiceArr = array();

        $authorizationChoiceArr["authorization"] = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
        $authorizationChoiceArr["payment_type"] = 0;

        switch ($payment) {
            case 'fondy_payment':
            case 'mono_payment':
            case 'wfp_payment':
                $authorizationChoiceArr["payment_type"] = 1;

                switch ($city) {
                    case "OdessaTest":
                        $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorizationApp("Test_Card_Pay", $connectAPI, $application);
                        $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorizationApp("Test_Double", $connectAPI, $application);
                        break;
                    case "Kyiv City":
                        switch ($connectAPI) {
                            case "http://167.235.113.231:7307":
                                $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_1_Card_Pay", $connectAPI, $application);
                                $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_1_Double", $connectAPI, $application);
                                break;
                            case "http://167.235.113.231:7306":
                                $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_2_Card_Pay", $connectAPI, $application);
                                $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_2_Double", $connectAPI, $application);
                                break;
                            default:
                                $authorizationChoiceArr["authorizationBonus"] = null;
                                $authorizationChoiceArr["authorizationDouble"] = null;
                        }
                        break;
                    case "Dnipropetrovsk Oblast":
                    case "Odessa":
                    case "Zaporizhzhia":
                    case "Cherkasy Oblast":
                        $authorizationChoiceArr["payment_type"] = 0;

                        break;
                }
                break;
            case 'bonus_payment':
                $authorizationChoiceArr["payment_type"] = 1;

                switch ($city) {
                    case "OdessaTest":
                        $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorizationApp("BonusTest", $connectAPI, $application);
                        $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorizationApp("BonusTestDouble", $connectAPI, $application);
                        break;
                    case "Kyiv City":
                        switch ($connectAPI) {
                            case "http://167.235.113.231:7307":
                                $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_1_Bonus", $connectAPI, $application);
                                $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_1_Bonus_Double", $connectAPI, $application);
                                break;
                            case "http://167.235.113.231:7306":
                                $authorizationChoiceArr["authorizationBonus"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_2_Bonus", $connectAPI, $application);
                                $authorizationChoiceArr["authorizationDouble"] = (new UniversalAndroidFunctionController)->authorizationApp("KyivCity_2_Bonus_Double", $connectAPI, $application);
                                break;
                            default:
                                $authorizationChoiceArr["authorizationBonus"] = null;
                                $authorizationChoiceArr["authorizationDouble"] = null;
                        }
                        break;
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
