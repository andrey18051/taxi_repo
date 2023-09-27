<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Models\BlackList;
use App\Models\City;
use App\Models\DoubleOrder;
use App\Models\ExecStatusHistory;
use App\Models\ExecutionStatus;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;
use function Symfony\Component\Translation\t;

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
////            "X-API-VERSION" => $apiVersion
//        ])->post($url, $parameter)->body();

        return Http::withHeaders([
//            return  Http::dd()->withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
//            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);
    }

//    public function startNewProcessExecutionStatus(
//        $doubleOrderId
//    ) {
//        $doubleOrder = DoubleOrder::find($doubleOrderId);
//
//        $responseBonusStr = $doubleOrder->responseBonusStr;
//        $responseDoubleStr = $doubleOrder->responseDoubleStr;
//        $authorizationBonus = $doubleOrder->authorizationBonus;
//        $authorizationDouble = $doubleOrder->authorizationDouble;
//        $connectAPI = $doubleOrder->connectAPI;
//        $identificationId = $doubleOrder->identificationId;
//        $apiVersion = $doubleOrder->apiVersion;
//
////        $doubleOrder->delete();
//
//        $responseBonus = json_decode($responseBonusStr, true);
//        $responseDouble = json_decode($responseDoubleStr, true);
//
//        $startTime = time();
//
//        $upDateTimeBonus = $startTime;
//        $upDateTimeDouble = $startTime;
//
//        $maxExecutionTime = 10*60; // Максимальное время выполнения - 4 часа
////          $maxExecutionTime = 4 * 60 * 60; // Максимальное время выполнения - 4 часа
//        $cancelUID = null;
//        $bonusOrder = $responseBonus['dispatching_order_uid'];
//        $doubleOrder = $responseDouble['dispatching_order_uid'];
//        $newStatusBonus = self::getExecutionStatus(
//            $authorizationBonus,
//            $identificationId,
//            $apiVersion,
//            $responseBonus["url"],
//            $bonusOrder
//        )["execution_status"];
//        $newStatusDouble = self::getExecutionStatus(
//            $authorizationDouble,
//            $identificationId,
//            $apiVersion,
//            $responseDouble["url"],
//            $doubleOrder
//        )["execution_status"];
//        $upDateTimeBonusInterval = 5;
//        $upDateTimeDoubleInterval = 5;
//
//        $respString = null;
//        $respStringDouble = null;
//
//        $bonusCancel = false;
//        $bonusCarFound = false;
//        $bonusWaitingCarSearch = true;
//
//        $doubleCancel = false;
//        $doubleCarFound = false;
//        $doubleWaitingCarSearch = true;
//
//        self::ordersExecStatusHistory(
//            $bonusOrder,
//            "bonus",
//            $newStatusBonus,
//            "в работе"
//        );
//
//        self::ordersExecStatusHistory(
//            $doubleOrder,
//            "double",
//            $newStatusDouble,
//            "в работе"
//        );
//        $lastStatusBonus = $newStatusBonus;
//        $lastStatusBonusTime = time();
//
//        $lastStatusDouble = $newStatusDouble;
//        $lastStatusDoubleTime = time();
//
//
//        while (time() - $startTime < $maxExecutionTime) {
//
//            if ($bonusCancel && $doubleCancel) {
//                self::ordersExecStatusHistory(
//                    $bonusOrder,
//                    "bonus",
//                    "Canceled",
//                    "снят окончательно"
//                );
//                self::ordersExecStatusHistory(
//                    $doubleOrder,
//                    "double",
//                    "Canceled",
//                    "снят окончательно"
//                );
//                break;
//            }
//
//
//            if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
//                $newStatusBonusResp = self::getExecutionStatus(
//                    $authorizationBonus,
//                    $identificationId,
//                    $apiVersion,
//                    $responseBonus["url"],
//                    $bonusOrder
//                );
//
//                $newStatusBonus = $newStatusBonusResp["execution_status"];
//
//                if ($newStatusBonusResp["close_reason"] == "1") {
//                    self::ordersExecStatusHistory(
//                        $bonusOrder,
//                        "bonus",
//                        "Canceled",
//                        "снят окончательно"
//                    );
//                    break;
//                }
//
//                $lastStatusBonusTime = time();
//                switch ($newStatusBonus) {
//                    case "CarFound":
//                    case "Running":
//                        $bonusCancel = false;
//                        $bonusCarFound = true;
//                        $bonusWaitingCarSearch = false;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            self::webordersCancel(
//                                $doubleOrder,
//                                $connectAPI,
//                                $authorizationDouble,
//                                $identificationId,
//                                $apiVersion
//                            );
//                            self::ordersExecStatusHistory(
//                                $doubleOrder,
//                                "double",
//                                "Canceled",
//                                "снят"
//                            );
//                            $doubleCancel = true;
//                            $upDateTimeBonusInterval = 30;
//                            $lastStatusBonus = $newStatusBonus;
//
//                            $bonusCarFound = true;
//
//                            if ($bonusCancel) {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationBonus,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseBonus['url'], $responseBonus['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $bonusOrder = $responseArr["dispatching_order_uid"];
//
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    self::updateExecutionStatusEmu("bonus"),
//                                    "восстановлен"
//                                );
//                                $bonusCancel = false;
//                                $lastStatusBonus = $newStatusBonus;
//
//                                $upDateTimeBonusInterval = 5;
//                            }
//                        }
//                        break;
//                    case "WaitingCarSearch":
//                    case "SearchesForCar":
//                        $bonusCancel = false;
//                        $bonusCarFound = false;
//                        $bonusWaitingCarSearch = true;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            if ($doubleCancel) {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationDouble,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseDouble['url'], $responseDouble['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $doubleOrder = $responseArr["dispatching_order_uid"];
//
//
//                                $doubleCancel = false;
//                                $lastStatusDouble = self::getExecutionStatus(
//                                    $authorizationDouble,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseDouble["url"],
//                                    $doubleOrder
//                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                                $lastStatusDoubleTime = time();
//                            }
//                            $upDateTimeBonusInterval = 5;
//                        }
//                        break;
//                    case "Canceled":
//                    case "CostCalculation":
//                        $bonusCancel = true;
//                        $bonusCarFound = false;
//                        $bonusWaitingCarSearch = false;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            if ($doubleWaitingCarSearch) {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationBonus,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseBonus['url'], $responseBonus['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $bonusOrder = $responseArr["dispatching_order_uid"];
//
//                                $bonusCancel = false;
//                                $lastStatusBonus = self::getExecutionStatus(
//                                    $authorizationBonus,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseBonus["url"],
//                                    $bonusOrder
//                                );
//
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    $lastStatusBonus,
//                                    "восстановлен"
//                                );
//
//                            }
//                            $upDateTimeBonusInterval = 5;
//                        }
//
//                        break;
//                    case "Executed":
//                        $bonusCancel = true;
//                        $bonusCarFound = false;
//                        $bonusWaitingCarSearch = false;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            if ($doubleWaitingCarSearch) {
//                                self::webordersCancel(
//                                    $bonusOrder,
//                                    $connectAPI,
//                                    $authorizationBonus,
//                                    $identificationId,
//                                    $apiVersion
//                                );
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    "Canceled",
//                                    "снят"
//                                );
//                                $bonusCancel = true;
//                                $lastStatusBonus = "Executed";
//
//                            } else {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationBonus,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseBonus['url'], $responseBonus['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $bonusOrder = $responseArr["dispatching_order_uid"];
//
//                                $lastStatusBonus = self::getExecutionStatus(
//                                    $authorizationBonus,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseBonus["url"],
//                                    $bonusOrder
//                                );
//
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    $lastStatusBonus,
//                                    "восстановлен"
//                                );
//                                $bonusCancel = false;
//                            }
//                            self::webordersCancel(
//                                $doubleOrder,
//                                $connectAPI,
//                                $authorizationDouble,
//                                $identificationId,
//                                $apiVersion
//                            );
//                            self::ordersExecStatusHistory(
//                                $doubleOrder,
//                                "double",
//                                "Canceled",
//                                "снят"
//                            );
//                            $doubleCancel = true;
//                            $lastStatusDouble = $newStatusBonus;
//                            $lastStatusDoubleTime = time();
//                            $upDateTimeDoubleInterval = 5;
//                        }
//
//                        break;
//                    default:
//                        $upDateTimeBonusInterval = 5;
//                }
//            }
//            if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
//                $newStatusDoubleResp = self::getExecutionStatus(
//                    $authorizationDouble,
//                    $identificationId,
//                    $apiVersion,
//                    $responseDouble["url"],
//                    $doubleOrder
//                );
//
//                $newStatusDouble = $newStatusDoubleResp["execution_status"];
//
//                if ($newStatusDoubleResp["close_reason"] == "1") {
//                    self::ordersExecStatusHistory(
//                        $doubleOrder,
//                        "double",
//                        "Canceled",
//                        "снят окончательно"
//                    );
//                    return;
//                }
//
//
//                $lastStatusDoubleTime = time();
//                switch ($newStatusDouble) {
//                    case "CarFound":
//                    case "Running":
//                        $doubleWaitingCarSearch = false;
//
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusCarFound) {
//                                self::webordersCancel(
//                                    $doubleOrder,
//                                    $connectAPI,
//                                    $authorizationDouble,
//                                    $identificationId,
//                                    $apiVersion
//                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    "bonus status = " . $newStatusBonus,
//                                    "снят"
//                                );
//                                $doubleCancel = true;
//                            } else {
//                                self::webordersCancel(
//                                    $bonusOrder,
//                                    $connectAPI,
//                                    $authorizationBonus,
//                                    $identificationId,
//                                    $apiVersion
//                                );
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    "double status = " . $newStatusDouble,
//                                    "снят"
//                                );
//                                $bonusCancel = true;
//                                $lastStatusBonus = "Canceled";
//
//                            }
//
//                            $upDateTimeDoubleInterval = 30;
//                            $lastStatusDouble = $newStatusDouble;
//
//
//                            if ($doubleCancel && $bonusWaitingCarSearch) {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationDouble,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseDouble['url'], $responseDouble['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $doubleOrder = $responseArr["dispatching_order_uid"];
//                                $doubleCancel = false;
//
//                                $lastStatusDouble = self::getExecutionStatus(
//                                    $authorizationDouble,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseDouble["url"],
//                                    $doubleOrder
//                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                                $upDateTimeDoubleInterval = 5;
//                            }
//                        }
//                        break;
//                    case "WaitingCarSearch":
//                    case "SearchesForCar":
//                        $doubleCancel = false;
//                        $doubleCarFound = false;
//                        $doubleWaitingCarSearch = true;
//
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusCancel) {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationBonus,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseBonus['url'], $responseBonus['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $bonusOrder = $responseArr["dispatching_order_uid"];
//                                $bonusCancel = false;
//                                $lastStatusBonus = self::getExecutionStatus(
//                                    $authorizationBonus,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseBonus["url"],
//                                    $bonusOrder
//                                );
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    $lastStatusBonus,
//                                    "восстановлен"
//                                );
//
//                            }
//                            $upDateTimeDoubleInterval = 5;
//                        }
//                        break;
//                    case "Canceled":
//                    case "CostCalculation":
//                        $doubleCancel = true;
//                        $doubleCarFound = false;
//                        $doubleWaitingCarSearch = false;
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusWaitingCarSearch) {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationDouble,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseDouble['url'], $responseDouble['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $doubleOrder = $responseArr["dispatching_order_uid"];
//
//
//                                $doubleCancel = false;
//                                $lastStatusDouble = self::getExecutionStatus(
//                                    $authorizationDouble,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseDouble["url"],
//                                    $doubleOrder
//                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                            }
//                            $upDateTimeDoubleInterval = 5;
//                        }
//
//                        break;
//                    case "Executed":
//                        $doubleCancel = true;
//                        $doubleCarFound = false;
//                        $doubleWaitingCarSearch = false;
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusWaitingCarSearch) {
//                                self::webordersCancel(
//                                    $doubleOrder,
//                                    $connectAPI,
//                                    $authorizationDouble,
//                                    $identificationId,
//                                    $apiVersion
//                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    "Executed",
//                                    "снят"
//                                );
//                                $doubleCancel = true;
//                                $lastStatusDouble = "Executed";
//
//                            } else {
//                                $response =  Http::withHeaders([
//                                    "Authorization" => $authorizationDouble,
//                                    "X-WO-API-APP-ID" => $identificationId,
//                                    //            "X-API-VERSION" => $apiVersion
//                                ])->post($responseDouble['url'], $responseDouble['parameter']);
//
//                                $responseArr = json_decode($response, true);
//                                $doubleOrder = $responseArr["dispatching_order_uid"];
//
//
//                                $doubleCancel = false;
//                                $lastStatusDouble = self::getExecutionStatus(
//                                    $authorizationDouble,
//                                    $identificationId,
//                                    $apiVersion,
//                                    $responseDouble["url"],
//                                    $doubleOrder
//                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                            }
//                            self::ordersExecStatusHistory(
//                                $bonusOrder,
//                                "bonus",
//                                "Executed",
//                                "снят"
//                            );
//                            $bonusCancel = true;
//                            $lastStatusBonus = "Executed";
//
//                            $upDateTimeDoubleInterval = 5;
//                        }
//
//                        break;
//                    default:
//                        $upDateTimeDoubleInterval = 5;
//                }
//            }
//
//
//        }
//        self::ordersExecStatusHistory(
//            $bonusOrder,
//            "bonus",
//            "Canceled",
//            "снят окончательно"
//        );
//        self::ordersExecStatusHistory(
//            $doubleOrder,
//            "double",
//            "Canceled",
//            "снят окончательно"
//        );
//
//        self::webordersCancel(
//            $bonusOrder,
//            $connectAPI,
//            $authorizationBonus,
//            $identificationId,
//            $apiVersion
//        );
//        self::webordersCancel(
//            $doubleOrder,
//            $connectAPI,
//            $authorizationDouble,
//            $identificationId,
//            $apiVersion
//        );
//        return "respString:" . $respString . " - respStringDouble:" . $respStringDouble;
//}
//    public function startNewProcessExecutionStatusEmuOld(
//        $doubleOrderId
//    ) {
//        $doubleOrder = DoubleOrder::find($doubleOrderId);
//
//        $responseBonusStr = $doubleOrder->responseBonusStr;
//        $responseDoubleStr = $doubleOrder->responseDoubleStr;
//        $authorizationBonus = $doubleOrder->authorizationBonus;
//        $authorizationDouble = $doubleOrder->authorizationDouble;
//        $connectAPI = $doubleOrder->connectAPI;
//        $identificationId = $doubleOrder->identificationId;
//        $apiVersion = $doubleOrder->apiVersion;
//
////        $doubleOrder->delete();
//
//        $responseBonus = json_decode($responseBonusStr, true);
//        $responseDouble = json_decode($responseDoubleStr, true);
//
//        $startTime = time();
//
//        $upDateTimeBonus = $startTime;
//        $upDateTimeDouble = $startTime;
//
//        $maxExecutionTime = 10*60; // Максимальное время выполнения - 4 часа
////          $maxExecutionTime = 4 * 60 * 60; // Максимальное время выполнения - 4 часа
//        $cancelUID = null;
//        $bonusOrder = $responseBonus['dispatching_order_uid'];
//        $doubleOrder = $responseDouble['dispatching_order_uid'];
//        $newStatusBonus = self::getExecutionStatusEmu("bonus");
////        $newStatusBonus = self::getExecutionStatus(
////            $authorizationBonus,
////            $identificationId,
////            $apiVersion,
////            $responseBonus["url"],
////            $bonusOrder
////        )["execution_status"];
//        $newStatusDouble = self::getExecutionStatusEmu("double");
////        $newStatusDouble = self::getExecutionStatus(
////            $authorizationDouble,
////            $identificationId,
////            $apiVersion,
////            $responseDouble["url"],
////            $doubleOrder
////        )["execution_status"];
//        $upDateTimeBonusInterval = 5;
//        $upDateTimeDoubleInterval = 5;
//
//        $respString = null;
//        $respStringDouble = null;
//
//        $bonusCancel = false;
//        $bonusCarFound = false;
//        $bonusWaitingCarSearch = true;
//
//        $doubleCancel = false;
//        $doubleCarFound = false;
//        $doubleWaitingCarSearch = true;
//
//        self::ordersExecStatusHistory(
//            $bonusOrder,
//            "bonus",
//            $newStatusBonus,
//            "в работе"
//        );
//
//        self::ordersExecStatusHistory(
//            $doubleOrder,
//            "double",
//            $newStatusDouble,
//            "в работе"
//        );
//        $lastStatusBonus = $newStatusBonus;
//        $lastStatusBonusTime = time();
//
//        $lastStatusDouble = $newStatusDouble;
//        $lastStatusDoubleTime = time();
//
//
//        while (time() - $startTime < $maxExecutionTime) {
//            if ($bonusCancel && $doubleCancel) {
//                self::ordersExecStatusHistory(
//                    $bonusOrder,
//                    "bonus",
//                    "Canceled",
//                    "снят окончательно"
//                );
//                self::ordersExecStatusHistory(
//                    $doubleOrder,
//                    "double",
//                    "Canceled",
//                    "снят окончательно"
//                );
//                break;
//            }
//
//
//            if (time() - $lastStatusBonusTime >= $upDateTimeBonusInterval) {
//                $newStatusBonus = self::getExecutionStatusEmu("bonus");
////                $newStatusBonusResp = self::getExecutionStatus(
////                    $authorizationBonus,
////                    $identificationId,
////                    $apiVersion,
////                    $responseBonus["url"],
////                    $bonusOrder
////                );
//
////                $newStatusBonus = $newStatusBonusResp["execution_status"];
//
////                if ($newStatusBonusResp["close_reason"] == "1") {
////                    self::ordersExecStatusHistory(
////                        $bonusOrder,
////                        "bonus",
////                        "Canceled",
////                        "снят окончательно"
////                    );
////                    break;
////                }
//
//                $lastStatusBonusTime = time();
//                switch ($newStatusBonus) {
//                    case "CarFound":
//                    case "Running":
//                        $bonusCancel = false;
//                        $bonusCarFound = true;
//                        $bonusWaitingCarSearch = false;
//                        if ($lastStatusBonus != $newStatusBonus) {
////                            self::webordersCancel(
////                                $doubleOrder,
////                                $connectAPI,
////                                $authorizationDouble,
////                                $identificationId,
////                                $apiVersion
////                            );
//                            self::ordersExecStatusHistory(
//                                $doubleOrder,
//                                "double",
//                                "Canceled",
//                                "снят"
//                            );
//                            $doubleCancel = true;
//                            $upDateTimeBonusInterval = 30;
//                            $lastStatusBonus = $newStatusBonus;
//
//                            $bonusCarFound = true;
//
//                            if ($bonusCancel) {
//                                $bonusOrder = "New bonusOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationBonus,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseBonus['url'], $responseBonus['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $bonusOrder = $responseArr["dispatching_order_uid"];
//
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    self::updateExecutionStatusEmu("bonus"),
//                                    "восстановлен"
//                                );
//                                $bonusCancel = false;
//                                $lastStatusBonus = $newStatusBonus;
//
//                                $upDateTimeBonusInterval = 5;
//                            }
//                        }
//                        break;
//                    case "WaitingCarSearch":
//                    case "SearchesForCar":
//                        $bonusCancel = false;
//                        $bonusCarFound = false;
//                        $bonusWaitingCarSearch = true;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            if ($doubleCancel) {
//                                $doubleOrder = "New doubleOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationDouble,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseDouble['url'], $responseDouble['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $doubleOrder = $responseArr["dispatching_order_uid"];
//
//
//                                $doubleCancel = false;
//                                $lastStatusDouble = self::updateExecutionStatusEmu("double");
////                                $lastStatusDouble = self::getExecutionStatus(
////                                    $authorizationDouble,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseDouble["url"],
////                                    $doubleOrder
////                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                                $lastStatusDoubleTime = time();
//                            }
//                            $upDateTimeBonusInterval = 5;
//                        }
//                        break;
//                    case "Canceled":
//                    case "CostCalculation":
//                        $bonusCancel = true;
//                        $bonusCarFound = false;
//                        $bonusWaitingCarSearch = false;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            if ($doubleWaitingCarSearch) {
//                                $bonusOrder = "New bonusOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationBonus,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseBonus['url'], $responseBonus['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $bonusOrder = $responseArr["dispatching_order_uid"];
//
//                                $bonusCancel = false;
//                                $lastStatusBonus = self::updateExecutionStatusEmu("bonus");
////                                $lastStatusBonus = self::getExecutionStatus(
////                                    $authorizationBonus,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseBonus["url"],
////                                    $bonusOrder
////                                );
//
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    $lastStatusBonus,
//                                    "восстановлен"
//                                );
//
//                            }
//                            $upDateTimeBonusInterval = 5;
//                        }
//
//                        break;
//                    case "Executed":
//                        $bonusCancel = true;
//                        $bonusCarFound = false;
//                        $bonusWaitingCarSearch = false;
//                        if ($lastStatusBonus != $newStatusBonus) {
//                            if ($doubleWaitingCarSearch) {
////                                self::webordersCancel(
////                                    $bonusOrder,
////                                    $connectAPI,
////                                    $authorizationBonus,
////                                    $identificationId,
////                                    $apiVersion
////                                );
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    "Canceled",
//                                    "снят"
//                                );
//                                $bonusCancel = true;
//                                $lastStatusBonus = "Executed";
//
//                            } else {
//                                $bonusOrder = "New bonusOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationBonus,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseBonus['url'], $responseBonus['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $bonusOrder = $responseArr["dispatching_order_uid"];
//                                  $lastStatusBonus = self::updateExecutionStatusEmu("bonus");
////                                $lastStatusBonus = self::getExecutionStatus(
////                                    $authorizationBonus,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseBonus["url"],
////                                    $bonusOrder
////                                );
//
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    $lastStatusBonus,
//                                    "восстановлен"
//                                );
//                                $bonusCancel = false;
//                            }
////                            self::webordersCancel(
////                                $doubleOrder,
////                                $connectAPI,
////                                $authorizationDouble,
////                                $identificationId,
////                                $apiVersion
////                            );
//                            self::ordersExecStatusHistory(
//                                $doubleOrder,
//                                "double",
//                                "Canceled",
//                                "снят"
//                            );
//                            $doubleCancel = true;
//                            $lastStatusDouble = $newStatusBonus;
//                            $lastStatusDoubleTime = time();
//                            $upDateTimeDoubleInterval = 5;
//                        }
//
//                        break;
//                    default:
//                        $upDateTimeBonusInterval = 5;
//                }
//            }
//            if (time() - $lastStatusDoubleTime >= $upDateTimeDoubleInterval) {
//                $newStatusDouble = self::getExecutionStatusEmu("double");
////                $newStatusDoubleResp = self::getExecutionStatus(
////                    $authorizationDouble,
////                    $identificationId,
////                    $apiVersion,
////                    $responseDouble["url"],
////                    $doubleOrder
////                );
////
////                $newStatusDouble = $newStatusDoubleResp["execution_status"];
////
////                if ($newStatusDoubleResp["close_reason"] == "1") {
////                    self::ordersExecStatusHistory(
////                        $doubleOrder,
////                        "double",
////                        "Canceled",
////                        "снят окончательно"
////                    );
////                    return;
////                }
//
//
//                $lastStatusDoubleTime = time();
//                switch ($newStatusDouble) {
//                    case "CarFound":
//                    case "Running":
//                        $doubleWaitingCarSearch = false;
//
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusCarFound) {
////                                self::webordersCancel(
////                                    $doubleOrder,
////                                    $connectAPI,
////                                    $authorizationDouble,
////                                    $identificationId,
////                                    $apiVersion
////                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    "bonus status = " . $newStatusBonus,
//                                    "снят"
//                                );
//                                $doubleCancel = true;
//                            } else {
////                                self::webordersCancel(
////                                    $bonusOrder,
////                                    $connectAPI,
////                                    $authorizationBonus,
////                                    $identificationId,
////                                    $apiVersion
////                                );
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    "double status = " . $newStatusDouble,
//                                    "снят"
//                                );
//                                $bonusCancel = true;
//                                $lastStatusBonus = "Canceled";
//
//                            }
//
//                            $upDateTimeDoubleInterval = 30;
//                            $lastStatusDouble = $newStatusDouble;
//
//
//                            if ($doubleCancel && $bonusWaitingCarSearch) {
//                                $doubleOrder = "New doubleOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationDouble,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseDouble['url'], $responseDouble['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $doubleOrder = $responseArr["dispatching_order_uid"];
//                                $doubleCancel = false;
//                                $lastStatusDouble = self::updateExecutionStatusEmu("double");
////                                $lastStatusDouble = self::getExecutionStatus(
////                                    $authorizationDouble,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseDouble["url"],
////                                    $doubleOrder
////                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                                $upDateTimeDoubleInterval = 5;
//                            }
//                        }
//                        break;
//                    case "WaitingCarSearch":
//                    case "SearchesForCar":
//                        $doubleCancel = false;
//                        $doubleCarFound = false;
//                        $doubleWaitingCarSearch = true;
//
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusCancel) {
//                                $bonusOrder = "New bonusOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationBonus,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseBonus['url'], $responseBonus['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $bonusOrder = $responseArr["dispatching_order_uid"];
//
//                                $bonusCancel = false;
//                                $lastStatusBonus = self::updateExecutionStatusEmu("bonus");
////                                $lastStatusBonus = self::getExecutionStatus(
////                                    $authorizationBonus,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseBonus["url"],
////                                    $bonusOrder
////                                );
//                                self::ordersExecStatusHistory(
//                                    $bonusOrder,
//                                    "bonus",
//                                    $lastStatusBonus,
//                                    "восстановлен"
//                                );
//
//                            }
//                            $upDateTimeDoubleInterval = 5;
//                        }
//                        break;
//                    case "Canceled":
//                    case "CostCalculation":
//                        $doubleCancel = true;
//                        $doubleCarFound = false;
//                        $doubleWaitingCarSearch = false;
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusWaitingCarSearch) {
//                                $doubleOrder = "New doubleOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationDouble,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseDouble['url'], $responseDouble['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $doubleOrder = $responseArr["dispatching_order_uid"];
//
//
//                                $doubleCancel = false;
//
//                                $lastStatusDouble = self::updateExecutionStatusEmu("double");
////                                $lastStatusDouble = self::getExecutionStatus(
////                                    $authorizationDouble,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseDouble["url"],
////                                    $doubleOrder
////                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                            }
//                            $upDateTimeDoubleInterval = 5;
//                        }
//
//                        break;
//                    case "Executed":
//                        $doubleCancel = true;
//                        $doubleCarFound = false;
//                        $doubleWaitingCarSearch = false;
//                        if ($lastStatusDouble != $newStatusDouble) {
//                            if ($bonusWaitingCarSearch) {
////                                self::webordersCancel(
////                                    $doubleOrder,
////                                    $connectAPI,
////                                    $authorizationDouble,
////                                    $identificationId,
////                                    $apiVersion
////                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    "Executed",
//                                    "снят"
//                                );
//                                $doubleCancel = true;
//                                $lastStatusDouble = "Executed";
//
//                            } else {
//                                $doubleOrder = "New doubleOrder UID";
////                                $response =  Http::withHeaders([
////                                    "Authorization" => $authorizationDouble,
////                                    "X-WO-API-APP-ID" => $identificationId,
////                                    //            "X-API-VERSION" => $apiVersion
////                                ])->post($responseDouble['url'], $responseDouble['parameter']);
////
////                                $responseArr = json_decode($response, true);
////                                $doubleOrder = $responseArr["dispatching_order_uid"];
//
//
//                                $doubleCancel = false;
//                                $lastStatusDouble = self::updateExecutionStatusEmu("double");
////                                $lastStatusDouble = self::getExecutionStatus(
////                                    $authorizationDouble,
////                                    $identificationId,
////                                    $apiVersion,
////                                    $responseDouble["url"],
////                                    $doubleOrder
////                                );
//                                self::ordersExecStatusHistory(
//                                    $doubleOrder,
//                                    "double",
//                                    $lastStatusDouble,
//                                    "восстановлен"
//                                );
//
//                            }
//                            self::ordersExecStatusHistory(
//                                $bonusOrder,
//                                "bonus",
//                                "Executed",
//                                "снят"
//                            );
//                            $bonusCancel = true;
//                            $lastStatusBonus = "Executed";
//
//                            $upDateTimeDoubleInterval = 5;
//                        }
//
//                        break;
//                    default:
//                        $upDateTimeDoubleInterval = 5;
//                }
//            }
//
//
//        }
//        self::ordersExecStatusHistory(
//            $bonusOrder,
//            "bonus",
//            "Canceled",
//            "снят окончательно"
//        );
//        self::ordersExecStatusHistory(
//            $doubleOrder,
//            "double",
//            "Canceled",
//            "снят окончательно"
//        );
//
//        self::webordersCancel(
//            $bonusOrder,
//            $connectAPI,
//            $authorizationBonus,
//            $identificationId,
//            $apiVersion
//        );
//        self::webordersCancel(
//            $doubleOrder,
//            $connectAPI,
//            $authorizationDouble,
//            $identificationId,
//            $apiVersion
//        );
//        return "respString:" . $respString . " - respStringDouble:" . $respStringDouble;
//}
    public function startNewProcessExecutionStatusEmu($doubleOrderId)
    {
        $doubleOrder = DoubleOrder::find($doubleOrderId);

        $responseBonusStr = $doubleOrder->responseBonusStr;
        $responseDoubleStr = $doubleOrder->responseDoubleStr;
        $authorizationBonus = $doubleOrder->authorizationBonus;
        $authorizationDouble = $doubleOrder->authorizationDouble;
        $connectAPI = $doubleOrder->connectAPI;
        $identificationId = $doubleOrder->identificationId;
        $apiVersion = $doubleOrder->apiVersion;

//        $doubleOrder->delete();



        $maxExecutionTime = 10*60; // Максимальное время выполнения - 4 часа
//          $maxExecutionTime = 4 * 60 * 60; // Максимальное время выполнения - 4 часа
        $startTime = time();


        $responseBonus = json_decode($responseBonusStr, true);
        $bonusOrder = $responseBonus['dispatching_order_uid'];

        $responseDouble = json_decode($responseDoubleStr, true);
        $doubleOrder = $responseDouble['dispatching_order_uid'];

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


        $respString = null;
        $respStringDouble = null;

        $bonusCancel = false;
        $bonusCarFound = false;
        $bonusWaitingCarSearch = true;

        $doubleCancel = false;
        $doubleCarFound = false;
        $doubleWaitingCarSearch = true;

        while (time() - $startTime < $maxExecutionTime) {
            if (self::canceledFinish(
                $bonusCancel,
                $bonusOrder,
                $doubleCancel,
                $doubleOrder
            )) {
                return "finish Canceled by User";
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
                                if ($doubleCancel) {
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
                                    $doubleCancel = false;
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
                                if ($doubleCancel) {
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
                                    $doubleCancel = false;
                                }
                                break;
                        }
                        break;
                    case "CarFound":
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
                                        if ($bonusCancel) {
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
                                        if ($bonusCancel) {
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
                                break;
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
                                if ($bonusCancel) {
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
                                if ($bonusCancel) {
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
                                if ($bonusCancel) {
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
                                        if ($doubleCancel) {
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
                                        if ($doubleCancel) {
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
                                if ($doubleCancel) {
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
        $newStatus = self::getExecutionStatusEmu("bonus");
//        $newStatus = self::getExecutionStatus(
//            $authorization,
//            $identificationId,
//            $apiVersion,
//            $url,
//            $order
//        )["execution_status"];

        self::ordersExecStatusHistory(
            $order,
            $orderType,
            $newStatus,
            "в работе"
        );
        return $newStatus;
    }

    public function canceledFinish(
        $bonusCancel,
        $bonusOrder,
        $doubleCancel,
        $doubleOrder
    ): bool {
        if ($bonusCancel && $doubleCancel) {
            self::ordersExecStatusHistory(
                $bonusOrder,
                "bonus",
                "Canceled",
                "снят окончательно"
            );
            self::ordersExecStatusHistory(
                $doubleOrder,
                "double",
                "Canceled",
                "снят окончательно"
            );
            return true;
        } else {
            return false;
        }
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
    }

    public function orderNewCreat(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $parameter
    ): string {
        $order = "New UID ";
//        $response = Http::withHeaders([
//            "Authorization" => $authorization,
//            "X-WO-API-APP-ID" => $identificationId,
//            //            "X-API-VERSION" => $apiVersion
//        ])->post($url, $parameter);
//
//        $responseArr = json_decode($response, true);
//        $order = $responseArr["dispatching_order_uid"];
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
            //            "X-API-VERSION" => $apiVersion
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
     * Запрос отмены заказа клиентом
     * @return string
     */
    public function webordersCancel(
        $uid,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            //            "X-API-VERSION" => $apiVersion
        ])->put($url);
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
        $order->routeto = $params["to"]; //Обязательный. Улица куда.
        $order->routetonumber = $params["to_number"]; //Обязательный. Дом куда.
        $order->taxiColumnId = $params["taxiColumnId"]; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->web_cost = $params['order_cost'];
        $order->dispatching_order_uid = $params['dispatching_order_uid'];
        $order->closeReason = $params['closeReason'];
        $order->closeReasonI = 1;
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
            $newUser->save();

            $user = User::where('email', $email)->first();
            (new BonusBalanceController)->recordsAdd(0, $user->id, 1, 1);
        }
    }

    public function verifyBlackListUser($email, $androidDom)
    {
        IPController::getIP("/android/$androidDom/startPage");
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
            //            "X-API-VERSION" => $apiVersion
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
            //            "X-API-VERSION" => $apiVersion
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
            //            "X-API-VERSION" => $apiVersion
        ])->get($url);
    }

    public function authorization($cityString): string
    {
        $city = City::where('name', $cityString)->first();
        $username = $city->login;
        $password = hash('SHA512', $city->password);
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
}
