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
                    $nameFrom = $response_arr['route_address_from']['name'] . " " . $response_arr['route_address_from']['number'];
                    $nameTo = $response_arr['route_address_to']['name'] . " " . $response_arr['route_address_to']['number'];

                    $order->routefrom = $nameFrom;
                    $order->routeto = $nameTo;

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
        return "-1";
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

        $order = Orderweb:: where("email", $email)

            ->where("closeReason", "!=", null)
            ->where("closeReason", "-1")
            ->where("server", "!=", null)
            ->where("comment", "!=", null)
            ->orderBy("created_at", "desc")
            ->get();
//dd($order);
        $response = null;
        Log::debug("UIDStatusShowEmail order", $order->toArray());
        if (!$order->isEmpty()) {
            self::UIDStatusReview($order);
        }
        $orderHistory = Orderweb::where("email", $email)

            -> where("closeReason", "!=", null)
            -> where("server", "!=", null)
            -> where("startLat", "!=", null)
            -> where("startLan", "!=", null)
            -> where("to_lat", "!=", null)
            -> where("to_lng", "!=", null)
            -> where("comment", "!=", null)
            -> orderBy("created_at", "desc")
            -> get();
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
    public function UIDStatusShowEmailCancel($email): array
    {

        $order = Orderweb:: where("email", $email)

            ->where("closeReason", "!=", null)
            ->where("closeReason", "-1")
            ->where("server", "!=", null)
            ->where("comment", "!=", null)
            ->orderBy("created_at", "desc")
            ->get();
//dd($order);
        $response = null;
        Log::debug("UIDStatusShowEmailCancel order", $order->toArray());
        if (!$order->isEmpty()) {
            self::UIDStatusReview($order);
        }
        $orderHistory = Orderweb::where("email", $email)

            -> where("closeReason", "-1")
            -> where("server", "!=", null)
            -> where("startLat", "!=", null)
            -> where("startLan", "!=", null)
            -> where("to_lat", "!=", null)
            -> where("to_lng", "!=", null)
            -> where("comment", "!=", null)
            -> orderBy("created_at", "desc")
            -> get();
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
                    'auto' => $value["auto"],
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
//        Log::debug("UIDStatusShowEmail response", $response);
        return $response;
    }

    /**
     * @throws \Exception
     */
    public function UIDStatusShowEmailApp(
        $email,
        $city,
        $application
    ) {
        $connectAPI = (new AndroidTestOSMController)->connectAPIAppOrder($city, $application);


        if ($connectAPI == 400) {
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
            return $response;
        }

        $order = Orderweb:: where("email", $email)
            ->where("closeReason", "!=", null)
            ->where("closeReason", "!=", "-1")
            ->where("server", "!=", null)
            ->where("comment", "!=", null)
            ->orderBy("created_at", "desc")
            ->get();
//dd($order);
        $response = null;
        if (!$order->isEmpty()) {
            self::UIDStatusReview($order);
        }
        $orderHistory = Orderweb::where("email", $email)

            -> where("closeReason", "!=", null)
            -> where("server", "!=", null)
            -> where("startLat", "!=", null)
            -> where("startLan", "!=", null)
            -> where("to_lat", "!=", null)
            -> where("to_lng", "!=", null)
            -> where("comment", "!=", null)
            -> orderBy("created_at", "desc")
            -> get();
        if (!$orderHistory->isEmpty()) {
            $i=0;
            $orderUpdate = $orderHistory->toArray();
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
        return $response;
    }

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

    public function UIDStatusReview($order)
    {
        Log::debug("UIDStatusReview", $order->toArray());
        foreach ($order->toArray() as $value) {
            $currentTime = time();
            $uid = $value["dispatching_order_uid"];
            $timeElapsed = $currentTime - strtotime($value["updated_at"]);
            $timeElapsed5 = $currentTime - strtotime($value["updated_at"]) - 5*60;

            $closeReason = $value["closeReason"];
            $closeReasonI = $value["closeReasonI"];

            $connectAPI =  $value["server"];

            switch ($value["comment"]) {
                case "taxi_easy_ua_pas1":
                    $application = "PAS1";
                    break;
                case "taxi_easy_ua_pas2":
                    $application = "PAS2";
                    break;
                    //case "PAS4":
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
                //case "PAS4":
                default:
                    $serverInfo = City_PAS4::where('address', $address)->first();
            }
            Log::debug("UIDStatusReview serverInfo $serverInfo->online");
            if ($serverInfo->online == "true") {
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
                                if ($timeElapsed5 >= 5 * 60) {
                                    if ($timeElapsed >= 60) {
                                        UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                    };
                                }

                                break;
                            case 2:
                                if ($timeElapsed >= 60 * 60) {
                                    UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                };
                                break;
                            case 3:
                                if ($timeElapsed >= 24 * 60 * 60) {
                                    UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                };
                                break;
                            case 4:
                                if ($timeElapsed >= 3 * 24 * 60 * 60) {
                                    UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                };
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
                                };
                                break;
                            case "2":
                                if ($timeElapsed >= 60 * 60) {
                                    UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                };
                                break;
                            case "3":
                                if ($timeElapsed >= 24 * 60 * 60) {
                                    UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                };
                                break;
                            case "4":
                                if ($timeElapsed >= 3 * 24 * 60 * 60) {
                                    UIDController::closeReasonUIDStatus($uid, $connectAPI, self::autorization($connectAPI), $identificationId);
                                };
                                break;
                        }
                        break;
                }
            }
        }
    }

    public function autorization($connectApi)
    {

        $city = City::where('address', str_replace('http://', '', $connectApi))->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        return 'Basic ' . base64_encode($username . ':' . $password);
    }

}
