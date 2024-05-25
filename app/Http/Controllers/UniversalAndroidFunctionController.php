<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Models\BlackList;
use App\Models\Card;
use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\DoubleOrder;
use App\Models\ExecStatusHistory;
use App\Models\ExecutionStatus;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class UniversalAndroidFunctionController extends Controller
{
    public function postRequestHTTP(
        $url,
        $parameter,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
////     dd(  Http::withHeaders([
//            return  Http::dd()->withHeaders([
//            "Authorization" => $authorization,
//            "X-WO-API-APP-ID" => $identificationId,
//            "X-API-VERSION" => $apiVersion
//        ])->post($url, $parameter)->body();

        return Http::withHeaders([
//            return  Http::dd()->withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);
    }

    public function startNewProcessExecutionStatusEmu($doubleOrderId)
    {
        ExecStatusHistory::truncate();
        $doubleOrderRecord = DoubleOrder::find($doubleOrderId);

        $responseBonusStr = $doubleOrderRecord->responseBonusStr;
        $responseDoubleStr = $doubleOrderRecord->responseDoubleStr;
        $authorizationBonus = $doubleOrderRecord->authorizationBonus;
        $authorizationDouble = $doubleOrderRecord->authorizationDouble;
        $connectAPI = $doubleOrderRecord->connectAPI;
        $identificationId = $doubleOrderRecord->identificationId;
        $apiVersion = $doubleOrderRecord->apiVersion;

        $doubleOrderRecord->delete();



        $maxExecutionTime =5*60; // Максимальное время выполнения - 4 часа
//          $maxExecutionTime = 4 * 60 * 60; // Максимальное время выполнения - 4 часа
        $startTime = time();


        $responseBonus = json_decode($responseBonusStr, true);
        $bonusOrder = $responseBonus['dispatching_order_uid'];
        $bonusOrderHold = $bonusOrder;
        //Увеличеваем максимальное время для отстроченного заказа
        $orderwebs = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();
        if ($orderwebs->required_time != null) {
            $maxExecutionTime +=  strtotime($orderwebs->required_time);
        }

        $responseDouble = json_decode($responseDoubleStr, true);
        $doubleOrder = $responseDouble['dispatching_order_uid'];
        ////////////////////////////////////////////////////////////////
//        (new BonusBalanceController)->bonusUnBlockedUid($bonusOrder, $doubleOrder, $bonusOrderHold);
//        return 0;
        //////////////////////////////////////////////////////////////
        $newStatusBonus =  self::newStatus(
            $authorizationBonus,
            $identificationId,
            $apiVersion,
            $responseBonus["url"],
            $bonusOrder,
            "bonus"
        );
        $lastStatusBonusTime = time();
        $lastStatusBonus = $newStatusBonus;
        switch ($newStatusBonus) {
            case "SearchesForCar":
            case "WaitingCarSearch":
                $upDateTimeBonusInterval = 5;
                break;
            default:
                $upDateTimeBonusInterval = 30;
        }
        $newStatusDouble = self::newStatus(
            $authorizationDouble,
            $identificationId,
            $apiVersion,
            $responseDouble["url"],
            $doubleOrder,
            "double"
        );
        $lastStatusDoubleTime = time();
        $lastStatusDouble = $newStatusDouble;
        switch ($newStatusDouble) {
            case "SearchesForCar":
            case "WaitingCarSearch":
                $upDateTimeDoubleInterval = 5;
                break;
            default:
                $upDateTimeDoubleInterval = 30;
        }

        $canceledAll = self::canceledFinish(
            $lastStatusBonus,
            $lastStatusDouble
        );
        Log::debug("canceledFinish" . $canceledAll);
        if ($canceledAll) {
            $doubleOrderRecord->delete();
            return "finish Canceled by User";
        };


        while (time() - $startTime < $maxExecutionTime) {
            $canceledAll = self::canceledFinish(
                $lastStatusBonus,
                $lastStatusDouble
            );

            if ($canceledAll) {
                Log::debug("canceledFinish" . $canceledAll);
                $doubleOrderRecord->delete();
                break;
            }

            if (time() <= strtotime($orderwebs->required_time)) {
                $upDateTimeBonusInterval = 60;
                $upDateTimeDoubleInterval = 60;
            }
            if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                $newStatusBonus = self::newStatus(
                    $authorizationBonus,
                    $identificationId,
                    $apiVersion,
                    $responseBonus["url"],
                    $bonusOrder,
                    "bonus"
                );
                $lastStatusBonusTime = time();
                switch ($newStatusBonus) {
                    case "SearchesForCar":
                    case "WaitingCarSearch":
                        $upDateTimeBonusInterval = 5;
                        break;
                    default:
                        $upDateTimeBonusInterval = 30;
                }
                Log::debug(" Bonus " . " newStatusBonus: " . $newStatusBonus . "newStatusDouble" . $newStatusDouble);
                switch ($newStatusBonus) {
                    case "SearchesForCar":
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                break;
                            case "CostCalculation":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $doubleOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                //Восстановление Double
                                    $doubleOrder = self::orderNewCreat(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble['url'],
                                        $responseDouble['parameter']
                                    );
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        $upDateTimeDoubleInterval = 5;
                                        break;
                                    default:
                                        $upDateTimeDoubleInterval = 30;
                                }
                                break;
                        }
                        break;
                    case "WaitingCarSearch":
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                $bonusCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                //Восстановление Double
                                    $doubleOrder = self::orderNewCreat(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble['url'],
                                        $responseDouble['parameter']
                                    );
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        $upDateTimeDoubleInterval = 5;
                                        break;
                                    default:
                                        $upDateTimeDoubleInterval = 30;
                                }
                                break;
                        }
                        break;
                    case "CarFound":
//                        self::orderCupture($bonusOrder);
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                            case "CarFound":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                //Отмена Double
                                self::orderCanceled(
                                    $doubleOrder,
                                    "double",
                                    $connectAPI,
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion
                                );
                                $doubleCancel = true;
                                break;
                            case "Running":
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                $bonusCancel = true;
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                            case "CostCalculation":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                        }
                        break;
                    case "Running":
//                        self::orderCupture($bonusOrder);
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                            case "CarFound":
                            case "Running":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                //Отмена Double
                                self::orderCanceled(
                                    $doubleOrder,
                                    "double",
                                    $connectAPI,
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion
                                );
                                $doubleCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                        }
                        break;
                    case "Canceled":
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                                switch ($lastStatusBonus) {
                                    case "CarFound":
                                    case "Running":
                                    case "Canceled":
                                    case "Executed":
                                    case "CostCalculation":
                                        //Восстановление Bonus
//                                        if ($bonusCancel) {
                                            $bonusOrder = self::orderNewCreat(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus['url'],
                                                $responseBonus['parameter']
                                            );
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus"
                                            );
                                            $lastStatusBonusTime = time();
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeBonusInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeBonusInterval = 30;
//                                            }
                                            $bonusCancel = false;
                                        }
                                        //Опрос Double
                                        if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double"
                                            );
                                            $lastStatusDoubleTime = time();

                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeDoubleInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeDoubleInterval = 30;
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "WaitingCarSearch":
                                switch ($lastStatusBonus) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Опрос Double
                                        if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double"
                                            );
                                            $lastStatusDoubleTime = time();

                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeDoubleInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeDoubleInterval = 30;
                                            }
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                    case "Canceled":
                                    case "Executed":
                                    case "CostCalculation":
                                        //Восстановление Bonus
                                            $bonusOrder = self::orderNewCreat(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus['url'],
                                                $responseBonus['parameter']
                                            );
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus"
                                            );
                                            $lastStatusBonusTime = time();
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $upDateTimeBonusInterval = 5;
                                                break;
                                            default:
                                                $upDateTimeBonusInterval = 30;
                                        }
                                        //Опрос Double
                                        if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double"
                                            );
                                            $lastStatusDoubleTime = time();

                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeDoubleInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeDoubleInterval = 30;
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                            case "CostCalculation":
                                break;
                        }
                        break;
                    case "Executed":
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                            case "CarFound":
                            case "Running":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $doubleOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }

                                //Отмена Double
                                self::orderCanceled(
                                    $doubleOrder,
                                    "double",
                                    $connectAPI,
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion
                                );
                                $doubleCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $doubleOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                        }
                        break;
                    case "CostCalculation":
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Восстановление Bonus
                                $bonusOrder = self::orderNewCreat(
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion,
                                    $responseBonus['url'],
                                    $responseBonus['parameter']
                                );
                                $newStatusBonus = self::newStatus(
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion,
                                    $responseBonus["url"],
                                    $bonusOrder,
                                    "bonus"
                                );
                                $lastStatusBonusTime = time();
                                switch ($newStatusBonus) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        $upDateTimeBonusInterval = 5;
                                        break;
                                    default:
                                        $upDateTimeBonusInterval = 30;
                                }
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                            case "CarFound":
                            case "Running":
                            case "CostCalculation":
                                switch ($lastStatusBonus) {
                                    case "SearchesForCar":
                                        //Опрос Double
                                        if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double"
                                            );
                                            $lastStatusDoubleTime = time();

                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeDoubleInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeDoubleInterval = 30;
                                            }
                                        }
                                        break;
                                }
                                break;
                        }
                        break;
                }
                $lastStatusBonus = $newStatusBonus;
            }

            if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                $newStatusDouble = self::newStatus(
                    $authorizationDouble,
                    $identificationId,
                    $apiVersion,
                    $responseDouble["url"],
                    $doubleOrder,
                    "double"
                );
                $lastStatusDoubleTime = time();
                switch ($newStatusDouble) {
                    case "SearchesForCar":
                    case "WaitingCarSearch":
                        $upDateTimeDoubleInterval = 5;
                        break;
                    default:
                        $upDateTimeDoubleInterval = 30;
                }
                Log::debug(" Double " . " newStatusBonus: " . $newStatusBonus . "newStatusDouble" . $newStatusDouble);
                switch ($newStatusDouble) {
                    case "SearchesForCar":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Отмена Double
                                self::orderCanceled(
                                    $doubleOrder,
                                    "double",
                                    $connectAPI,
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion
                                );
                                $doubleCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                //Восстановление Bonus
//                                if ($bonusCancel) {
                                    $bonusOrder = self::orderNewCreat(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus['url'],
                                        $responseBonus['parameter']
                                    );
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
//                                    }
                                    $bonusCancel = false;
                                }
                                break;
                        }
                        break;
                    case "WaitingCarSearch":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Отмена Double
                                self::orderCanceled(
                                    $doubleOrder,
                                    "double",
                                    $connectAPI,
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion
                                );
                                $doubleCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                //Восстановление Bonus
//                                if ($bonusCancel) {
                                    $bonusOrder = self::orderNewCreat(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus['url'],
                                        $responseBonus['parameter']
                                    );
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
//                                    }
                                    $bonusCancel = false;
                                }
                                break;
                        }
                        break;
                    case "CarFound":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                $bonusCancel = true;
                                break;
                            case "CarFound":
                            case "Running":
                            //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                $bonusCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                        }
                        break;
                    case "Running":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                            case "CarFound":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                $bonusCancel = true;
                                break;

                            case "Running":
                                //Отмена Double
                                self::orderCanceled(
                                    $doubleOrder,
                                    "double",
                                    $connectAPI,
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion
                                );
                                $doubleCancel = true;
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $doubleOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                            case "CostCalculation":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                        }
                        break;
                    case "Canceled":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                                switch ($lastStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Отмена Bonus
                                        self::orderCanceled(
                                            $bonusOrder,
                                            "bonus",
                                            $connectAPI,
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        $bonusCancel = true;
                                        break;
                                    case "CarFound":
                                    case "Running":
                                    case "Canceled":
                                    case "Executed":
                                    case "CostCalculation":
                                        //Восстановление Double
//                                        if ($doubleCancel) {
                                            $doubleOrder = self::orderNewCreat(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble['url'],
                                                $responseDouble['parameter']
                                            );
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double"
                                            );
                                            $lastStatusDoubleTime = time();
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeDoubleInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeDoubleInterval = 30;
//                                            }
                                            $doubleCancel = false;
                                        }
                                        //Опрос Bonus
                                        if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus"
                                            );
                                            $lastStatusBonusTime = time();

                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeBonusInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeBonusInterval = 30;
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "WaitingCarSearch":
                                switch ($lastStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Отмена Bonus
                                        self::orderCanceled(
                                            $bonusOrder,
                                            "bonus",
                                            $connectAPI,
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        $bonusCancel = true;
                                        break;
                                    case "CarFound":
                                    case "Running":
                                    case "Canceled":
                                    case "Executed":
                                    case "CostCalculation":
                                        //Восстановление Double
//                                        if ($doubleCancel) {
                                            $doubleOrder = self::orderNewCreat(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble['url'],
                                                $responseDouble['parameter']
                                            );
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double"
                                            );
                                            $lastStatusDoubleTime = time();
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeDoubleInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeDoubleInterval = 30;
//                                            }
                                            $doubleCancel = false;
                                        }
                                        //Опрос Bonus
                                        if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus"
                                            );
                                            $lastStatusBonusTime = time();
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $upDateTimeBonusInterval = 5;
                                                    break;
                                                default:
                                                    $upDateTimeBonusInterval = 30;
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                            case "CostCalculation":
                                break;
                        }
                        break;
                    case "Executed":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                            case "CarFound":
                            case "Running":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                //Отмена Bonus
                                self::orderCanceled(
                                    $bonusOrder,
                                    "bonus",
                                    $connectAPI,
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion
                                );
                                $bonusCancel = true;
                                break;
                            case "CostCalculation":
                                //Опрос Double
                                if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();

                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
                                    }
                                }
                                break;
                        }
                        break;
                    case "CostCalculation":
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                //Восстановление Double
//                                if ($doubleCancel) {
                                    $doubleOrder = self::orderNewCreat(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble['url'],
                                        $responseDouble['parameter']
                                    );
                                    $newStatusDouble = self::newStatus(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble["url"],
                                        $doubleOrder,
                                        "double"
                                    );
                                    $lastStatusDoubleTime = time();
                                    switch ($newStatusDouble) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeDoubleInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeDoubleInterval = 30;
//                                    }
                                    $doubleCancel = false;
                                }
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                            case "CarFound":
                            case "Running":
                                //Опрос Bonus
                                if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
                                    $newStatusBonus = self::newStatus(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus["url"],
                                        $bonusOrder,
                                        "bonus"
                                    );
                                    $lastStatusBonusTime = time();

                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            $upDateTimeBonusInterval = 5;
                                            break;
                                        default:
                                            $upDateTimeBonusInterval = 30;
                                    }
                                }
                                break;
                            case "CostCalculation":
                                break;
                        }
                        break;
                }
                $lastStatusDouble = $newStatusDouble;
            }
        }

        if (self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold) == 0) {
            self::orderCanceled(
                $bonusOrder,
                'bonus',
                $connectAPI,
                $authorizationBonus,
                $identificationId,
                $apiVersion
            );

            self::orderCanceled(
                $doubleOrder,
                "double",
                $connectAPI,
                $authorizationDouble,
                $identificationId,
                $apiVersion
            );

//            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
        }

        $doubleOrderRecord->delete();
        return "finish by time is out";
    }

    public function newStatus(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $order,
        $orderType
    ) {
//        $newStatus = self::getExecutionStatusEmu("bonus");
        $newStatus = self::getExecutionStatus(
            $authorization,
            $identificationId,
            $apiVersion,
            $url,
            $order
        )["execution_status"];

        $message = "в работе";
        if ($newStatus == "Canceled") {
            $message = "закрыт";
        }

        self::ordersExecStatusHistory(
            $order,
            $orderType,
            $newStatus,
            $message
        );
        Log::debug("function newStatus: " . $newStatus);
        return $newStatus;
    }

    /**
     * @param $lastStatusBonus
     * @param $lastStatusDouble
     * @return bool
     * Выход из вилки не по времени
     */
    public function canceledFinish(
        $lastStatusBonus,
        $lastStatusDouble
    ): bool {
        $canceledAll = false;
        switch ($lastStatusBonus) {
            case "Executed":
                $canceledAll = true;
                break;
        }
        switch ($lastStatusDouble) {
            case "Executed":
                $canceledAll = true;
                break;
            case "Canceled":
                switch ($lastStatusBonus) {
                    case "SearchesForCar":
                    case "WaitingCarSearch":
                    case "Canceled":
                        $canceledAll = true;
                        break;
                }
                break;
        }

        return $canceledAll;
    }

    public function orderCanceled(
        $order,
        $orderType,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        self::webordersCancel(
            $order,
            $orderType,
            $connectAPI,
            $authorization,
            $identificationId,
            $apiVersion
        );

        self::ordersExecStatusHistory(
            $order,
            $orderType,
            "Canceled",
            "снят"
        );
        Log::debug("function orderCanceled: ". $order);
    }

    public function orderNewCreat(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $parameter
    ): string {
        $order = "New UID ";
        $response = Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);

        $responseArr = json_decode($response, true);
        $order = $responseArr["dispatching_order_uid"];
        Log::debug(" orderNewCreat: " . $url . $order);
        return $order;
    }

    public function getExecutionStatus(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $dispatching_order_uid
    ) {
        // Здесь реализуйте код для получения статуса execution_status по UID
        // Верните фактический статус для последующей проверки

        $url = $url . "/" . $dispatching_order_uid;

        $response = Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->get($url);
        $responseArr = json_decode($response, true);
        Log::debug("$url" . "execution_status: " . $responseArr["execution_status"] . " close_reason: " . $responseArr["close_reason"]);

        return $responseArr;
    }
    public function getExecutionStatusEmu($orderType)
    {
        $array = ExecutionStatus::all()->toArray();

        return $array[0][$orderType];
    }
    public function updateExecutionStatusEmu($orderType)
    {
        $status = ExecutionStatus::find(1);
        $statusText = "WaitingCarSearch";

        switch ($orderType) {
            case "bonus":
                $status->bonus = $statusText;
                $status->save();
                break;
            case "double":
                $status->double = $statusText;
                $status->save();
                break;
        }

        return $statusText;
    }
    /**
     * Запрос отмены заказа
     * @return string
     */
    public function webordersCancel(
        $uid,
        $orderType,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->put($url);
        Log::debug("function webordersCancel: " . $url);
        Log::debug("function webordersCancel orderType: " . $orderType);
    }

    public function orderReview($bonusOrder, $doubleOrder, $bonusOrderHold)
    {
        $order = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();

        Log::debug("function webordersCancel: order->fondy_order_id " . $order->fondy_order_id);

        if ($order->fondy_order_id != null) {
            //Возврат денег по Фонди
            return (new FondyController)->fondyStatusReview($bonusOrder, $doubleOrder, $bonusOrderHold);
        } else {
            if ($order->wfp_order_id != null) {
                return  (new WfpController)->wfpStatus($bonusOrder, $doubleOrder, $bonusOrderHold);
            } else {
                return   (new BonusBalanceController)->bonusUnBlockedUid($bonusOrder, $doubleOrder, $bonusOrderHold);

            }
        }
    }

    public function ordersExecStatusHistory(
        $order,
        $orderType,
        $execution_status,
        $cancel
    ) {
        $execStatusHistory = new ExecStatusHistory();
        $execStatusHistory->order = $order;
        $execStatusHistory->order_type = $orderType;
        $execStatusHistory->execution_status = $execution_status;
        $execStatusHistory->cancel = $cancel;
        $execStatusHistory->save();
    }

    public function saveCost($params)
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
        $order->routetonumber = " "; //Обязательный. Дом куда.
        $order->taxiColumnId = $params['taxiColumnId']; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->save();
    }

    public function saveOrder($params, $identificationId)
    {

        /**
         * Сохранние расчетов в базе
         */
        $order = new Orderweb();

        $order->user_full_name = $params["user_full_name"];//Полное имя пользователя
        $order->user_phone = $params["user_phone"];//Телефон пользователя
        $order->email = $params['email'];//Телефон пользователя
        $order->client_sub_card = null;
        $order->required_time = $params["required_time"]; //Время подачи предварительного заказа
        $order->reservation = $params["reservation"]; //Обязательный. Признак предварительного заказа: True, False
        $order->route_address_entrance_from = null;
        $order->comment = $identificationId;  //Комментарий к заказу
        $order->add_cost = $params["add_cost"]; //Добавленная стоимость
        $order->wagon = $params["wagon"]; //Универсал: True, False
        $order->minibus = $params["minibus"]; //Микроавтобус: True, False
        $order->premium = $params["premium"]; //Машина премиум-класса: True, False
        $order->flexible_tariff_name = $params["flexible_tariff_name"]; //Гибкий тариф
        $order->route_undefined = $params["route_undefined"]; //По городу: True, False
        $order->routefrom = $params["from"]; //Обязательный. Улица откуда.
        $order->routefromnumber = $params["from_number"]; //Обязательный. Дом откуда.
        $order->startLat = $params["startLat"]; //
        $order->startLan = $params["startLan"]; //
        $order->routeto = $params["to"]; //Обязательный. Улица куда.
        $order->routetonumber = $params["to_number"]; //Обязательный. Дом куда.
        $order->to_lat = $params["to_lat"]; //
        $order->to_lng = $params["to_lng"]; //
        $order->taxiColumnId = $params["taxiColumnId"]; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->web_cost = $params['order_cost'];
        $order->dispatching_order_uid = $params['dispatching_order_uid'];
        $order->closeReason = $params['closeReason'];
        $order->closeReasonI = 1;
        $order->server = $params['server'];

        $order->save();

        $user = User::where("email", $params["email"])->first();
        $user->user_phone = $params["user_phone"];
        $user->save();

        switch ($order->comment) {
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

        /**
         * Сообщение о заказе
         */
//        dd($params);

        $user_phone  = $params["user_phone"];//Телефон пользователя

        $email = $params['email'];//Телефон пользователя
        if (!$params["route_undefined"]) {
            $order = "Нове замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " до "  . $params['to'] . " " . $params['to_number'] .
                ". Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'] .
                ", сервер " . $params['server'];
            ;
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " по місту. Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'] .
                ", сервер " . $params['server'];
        }

        $subject = 'Інформація про нову поїздку:';
        $paramsCheck = [
            'subject' => $subject,
            'message' => $order . ". Приложение $pas",
        ];

        $message = new TelegramController();
        try {
            $message->sendMeMessage($order . ". Приложение $pas");
            $message->sendAlarmMessage($order . ". Приложение $pas");
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };

        Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        Mail::to('cartaxi4@gmail.com')->send(new Check($paramsCheck));
    }
    public function addUser($name, $email)
    {
        $newUser = User::whereRaw('BINARY email = ?', [$email])->first();
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
            $newUser->bonus = 0;
            $newUser->bonus_pay = 1;
            $newUser->card_pay = 1;

            $newUser->save();

            $user = User::where('email', $email)->first();
            (new BonusBalanceController)->recordsAdd(0, $user->id, 1, 1);
        }
    }
    public function addUserNoName($email)
    {
        $newUser = User::whereRaw('BINARY email = ?', [$email])->first();
        if ($newUser == null) {
            $newUser = new User();

            $newUser->name = "user_";
            $newUser->email = $email;
            $newUser->password = "123245687";

            $newUser->facebook_id = null;
            $newUser->google_id = null;
            $newUser->linkedin_id = null;
            $newUser->github_id = null;
            $newUser->twitter_id = null;
            $newUser->telegram_id = null;
            $newUser->viber_id = null;
            $newUser->bonus = 0;
            $newUser->bonus_pay = 1;
            $newUser->card_pay = 1;
            $newUser->save();

            $user = User::where('email', $email)->first();
            $username = "user_" . $newUser->id;
            $user->name = $username;
            $user->save();

            (new BonusBalanceController)->recordsAdd(0, $user->id, 1, 1);
        }
        return ["user_name" => $username];
    }

    public function verifyBlackListUser($email, $androidDom)
    {
        IPController::getIP("/android/$androidDom/startPage");
        $user =  User::where('email', $email)->first();

        $response_error["order_cost"] = 0;
        if ($user == null) {
            switch ($androidDom) {
                case "PAS1":
                    $response_error["Message"] = config("app.version-PAS1");
                    break;
                case "PAS2":
                    $response_error["Message"] = config("app.version-PAS2");
                    break;
                case "PAS3":
                    $response_error["Message"] = config("app.version-PAS3");
                    break;
                case "PAS4":
                    $response_error["Message"] = config("app.version-PAS4");
                    break;
                case "PAS5":
                    $response_error["Message"] = config("app.version-PAS5");
                    break;
            }

        } else {
            if ($user->black_list == "1") {
                $response_error["Message"] = "В черном списке";
            } else {
                $response_error["Message"] = "Не черном списке";
            }
        }
        return response($response_error, 200)
            ->header('Content-Type', 'json');
    }

    public function geoDataSearch(
        $to,
        $to_number,
        $autorization,
        $identificationId,
        $apiVersion,
        $connectAPI
    ): array {
        if ($to_number != " ") {
            $LatLng = self::geoDataSearchStreet(
                $to,
                $to_number,
                $autorization,
                $identificationId,
                $apiVersion,
                $connectAPI
            );
        } else {
            $LatLng = self::geoDataSearchObject(
                $to,
                $autorization,
                $identificationId,
                $apiVersion,
                $connectAPI
            );
        }

        return $LatLng;
    }
    public function geoDataSearchStreet(
        $to,
        $to_number,
        $autorization,
        $identificationId,
        $apiVersion,
        $connectAPI
    ): array {
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/search';

        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
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

    public function geoDataSearchObject(
        $to,
        $autorization,
        $identificationId,
        $apiVersion,
        $connectAPI
    ): array {
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/objects/search';

        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
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
        return $LatLng;
    }

    public function historyUIDStatus(
        $uid,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        $url = $connectAPI . '/api/weborders/' . $uid;

        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->get($url);
    }

    public function authorization($cityString, $connectAPI): string
    {
        $city = City::where('name', $cityString)
            ->where("address", str_replace("http://", "", $connectAPI))
            ->first();
        $username = $city->login;
        $password = hash('SHA512', $city->password);
        Log::debug("connectAPI $connectAPI");
        Log::debug("username $username");
        Log::debug("password $city->password");

        return 'Basic ' . base64_encode($username . ':' . $password);
    }
    public function authorizationApp($cityString, $connectAPI, $app): string
    {


        switch ($app) {
            case "PAS1":
                $city = City_PAS1::where('name', $cityString)
                    ->where("address", str_replace("http://", "", $connectAPI))
                    ->first();
                break;
            case "PAS2":
                $city = City_PAS2::where('name', $cityString)
                    ->where("address", str_replace("http://", "", $connectAPI))
                    ->first();
                break;
            //case "PAS4":
            default:
                $city = City_PAS4::where('name', $cityString)
                    ->where("address", str_replace("http://", "", $connectAPI))
                    ->first();
        }


        $username = $city->login;
        $password = hash('SHA512', $city->password);
        Log::debug("connectAPI $connectAPI");
        Log::debug("username $username");
        Log::debug("password $city->password");

        return 'Basic ' . base64_encode($username . ':' . $password);
    }
    public function apiVersion($name, $address)
    {

        $url = $address;
        if (strpos($url, "http://") !== false) {
            $cleanedUrl = str_replace("http://", "", $url);
        } else {
            // Если "http://" не найдено, сохраняем исходный URL
            $cleanedUrl = $url;
        }
        $city = City::where('name', $name)->where('address', $cleanedUrl)->first();
//dd($city);
        return $city->toArray()['versionApi'];
    }
    public function apiVersionApp($name, $address, $app)
    {

        $url = $address;
        if (strpos($url, "http://") !== false) {
            $cleanedUrl = str_replace("http://", "", $url);
        } else {
            // Если "http://" не найдено, сохраняем исходный URL
            $cleanedUrl = $url;
        }
        switch ($app) {
            case "PAS1":
                $city = City_PAS1::where('name', $name)->where('address', $cleanedUrl)->first();
                break;
            case "PAS2":
                $city = City_PAS2::where('name', $name)->where('address', $cleanedUrl)->first();
                break;
           //case "PAS4":
            default:
                $city = City_PAS4::where('name', $name)->where('address', $cleanedUrl)->first();
        }

//dd($city);
        return $city->toArray()['versionApi'];
    }

    public function orderIdMemory($order_id, $uid, $pay_system)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        switch ($pay_system) {
            case "wfp_payment":
                $orderweb->wfp_order_id = $order_id;
                break;
            case "fondy_payment":
                $orderweb->fondy_order_id = $order_id;
                break;
            case "mono_payment":
                $orderweb->mono_order_id = $order_id;
                break;
        }
        $orderweb->pay_system = $pay_system;
        $orderweb->save();
    }

    public function getCardToken($email, $pay_system, $merchantId): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $email)->first();
        $cards = Card::where('pay_system', $pay_system)
            ->where('user_id', $user->id)
            ->where('merchant', $merchantId)
            ->get();

        $response = [];

        foreach ($cards as $card) {
            $rectokenLifetimeString = $card->rectoken_lifetime;
            $rectokenLifetimeDateTime = DateTime::createFromFormat('d.m.Y H:i:s', $rectokenLifetimeString);

            $cardData = [
                'masked_card' => $card->masked_card,
                'card_type' => $card->card_type,
                'bank_name' => $card->bank_name,
                'merchant' => $card->merchant,
                'rectoken' => $card->rectoken
            ];

            if ($rectokenLifetimeDateTime instanceof DateTime) {
                $currentTime = new DateTime();

                if ($rectokenLifetimeDateTime < $currentTime) {
                    $card->delete();
                }
            }
            $response[] = $cardData;
        }

        return response()->json(['cards' => $response]);
    }

    public function getCardTokenApp(
        $application,
        $city,
        $email,
        $pay_system
    ): \Illuminate\Http\JsonResponse {
        $user = User::where('email', $email)->first();
//        dd($application);
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
        }
//dd( $merchantAccount);

        $cards = Card::where('pay_system', $pay_system)
            ->where('user_id', $user->id)
            ->where('merchant', $merchantAccount)
            ->get();

        $response = [];

        foreach ($cards as $card) {
            $rectokenLifetimeString = $card->rectoken_lifetime;
            $rectokenLifetimeDateTime = DateTime::createFromFormat('d.m.Y H:i:s', $rectokenLifetimeString);

            $cardData = [
                'masked_card' => $card->masked_card,
                'card_type' => $card->card_type,
                'bank_name' => $card->bank_name,
                'merchant' => $card->merchant,
                'rectoken' => $card->rectoken
            ];

            if ($rectokenLifetimeDateTime instanceof DateTime) {
                $currentTime = new DateTime();

                if ($rectokenLifetimeDateTime < $currentTime) {
                    $card->delete();
                }
            }
            $response[] = $cardData;
        }

        return response()->json(['cards' => $response]);
    }

    public function deleteCardToken($rectoken)
    {

        $card = Card::where('rectoken', $rectoken)
            ->first();

        $card->delete();
    }

    public function UIDStatusReview($order)
    {
        foreach ($order as $value) {
            $currentTime = time();
            $uid = $value["dispatching_order_uid"];
            $timeElapsed = $currentTime - strtotime($value["updated_at"]);
            $timeElapsed5 = $currentTime - strtotime($value["updated_at"]) - 5*60;

            $closeReason = $value["closeReason"];
            $closeReasonI = $value["closeReasonI"];

            $connectAPI =  $value["server"];
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

    function encryptData($data, $key) {
        $method = 'AES-256-CBC';
        $key = hash('sha256', $key, true);
        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    function userPhone()
    {
        $users = User::all()->toArray();

        foreach ($users as $value) {
            // Check if order exists for the user's email
            $order = Orderweb::where("email", $value["email"])->first();

            if ($order) {
                $user = User::where("email", $value["email"])->first();

                // Check if user exists before trying to update user_phone
                if ($user) {
                    $user->user_phone = $order->user_phone;
                    $user->save();
                } else {
                    // Handle the case where the user doesn't exist
                    // You may want to log an error or take appropriate action
                }
            } else {
                // Handle the case where the order doesn't exist
                // You may want to log an error or take appropriate action
            }
        }
    }

    public function userPhoneReturn(string $email): array
    {
        $user = User::where("email", $email)->first();
        return ["phone"=>$user->user_phone];
    }
    public function userPermissions(string $email): array
    {
        $user = User::where("email", $email)->first();
        if ($user->card_pay == null) {
            $user->card_pay = 0;
        }
        if ($user->bonus_pay == null) {
            $user->bonus_pay = 0;
        }
        return ["card_pay"=>$user->card_pay, "bonus_pay"=>$user->bonus_pay,];
    }

    public function testConnection(): \Illuminate\Http\JsonResponse
    {
        return response()->json(["test"=>"ok"], 200);
    }
}
