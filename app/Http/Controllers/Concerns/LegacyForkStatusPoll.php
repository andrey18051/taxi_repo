<?php

namespace App\Http\Controllers\Concerns;

use App\City\PaymentFlow;
use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\OrderStatusController;
use App\Services\ForkLegUidChain;
use App\Support\ForkOrderCancelLegResolver;
use App\Models\DoubleOrder;
use App\Models\ExecStatusHistory;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Support\Facades\Log;

/**
 * Опрос статусов пары безнала до рефакторинга 12.06.2026 (вложенный switch).
 * Включается payment_flow = 3 в таблице города и снимается в payment_flow_mode заказа.
 */
trait LegacyForkStatusPoll
{
    public function startNewProcessExecutionStatusJobLegacy($doubleOrderId, $jobId): ?string
    {
        try {
            Log::info("Starting legacy fork status poll (pre-2026-06-12 switch) doubleOrderId: {$doubleOrderId}, jobId: {$jobId}");

            $messageAdmin = "!!! 17032025 !!! startNewProcessExecutionStatusJob задача $doubleOrderId / $jobId";
            Log::info($messageAdmin);

            ExecStatusHistory::truncate();
            Log::info("Truncated ExecStatusHistory table");

            $doubleOrderRecord = DoubleOrder::find($doubleOrderId);
            if (!$doubleOrderRecord) {
                Log::warning("DoubleOrder record not found for ID: {$doubleOrderId}");
                return "exit";
            }
            Log::info("Fetched DoubleOrder record: " . print_r($doubleOrderRecord->toArray(), true));

            $responseBonusStr = $doubleOrderRecord->responseBonusStr;
            $responseDoubleStr = $doubleOrderRecord->responseDoubleStr;
            $authorizationBonus = $doubleOrderRecord->authorizationBonus;
            $authorizationDouble = $doubleOrderRecord->authorizationDouble;
            $connectAPI = $doubleOrderRecord->connectAPI;
            $identificationId = $doubleOrderRecord->identificationId;
            $apiVersion = $doubleOrderRecord->apiVersion;

            Log::info("Extracted variables: responseBonusStr={$responseBonusStr}, responseDoubleStr={$responseDoubleStr}, authorizationBonus={$authorizationBonus}, authorizationDouble={$authorizationDouble}, connectAPI={$connectAPI}, identificationId={$identificationId}, apiVersion={$apiVersion}");

            $maxExecutionTime = config("app.exec_time");; // Максимальное время выполнения - 3 суток
            Log::info("Set maxExecutionTime to: {$maxExecutionTime}");

            $responseBonus = json_decode($responseBonusStr, true);
            $bonusOrder = $responseBonus['dispatching_order_uid'];
            $bonusOrderHold = $bonusOrder;
            Log::info("Decoded responseBonus: bonusOrder={$bonusOrder}, bonusOrderHold={$bonusOrderHold}");

            //Увеличеваем максимальное время для отстроченного заказа
            $bonusOrderHold = (new MemoryOrderChangeController)->show($bonusOrderHold);
            Log::info("Updated bonusOrderHold after MemoryOrderChangeController: {$bonusOrderHold}");

            $orderwebs = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();
            if ($orderwebs->required_time != null) {
                $maxExecutionTime += strtotime($orderwebs->required_time);
                Log::info("Increased maxExecutionTime due to required_time: new value={$maxExecutionTime}");
            } else {
                Log::info("No required_time found, maxExecutionTime remains: {$maxExecutionTime}");
            }

            $lastTimeUpdate = time();
            Log::info("Set lastTimeUpdate to: {$lastTimeUpdate}");

            $updateTime = 5;
            Log::info("Set updateTime to: {$updateTime}");

            $responseDouble = json_decode($responseDoubleStr, true);
            $doubleOrder = $responseDouble['dispatching_order_uid'];
            Log::info("Decoded responseDouble: doubleOrder={$doubleOrder}");

            try {
                $uid_history = Uid_history::where("uid_bonusOrderHold", $bonusOrder)->first();
                Log::info("Queried Uid_history for uid_bonusOrderHold={$bonusOrder}");

                if ($uid_history == null) {
                    $messageAdmin = "uid_history: не найдено для bonusOrder: $bonusOrder";
                    Log::info($messageAdmin);
                    Log::warning($messageAdmin);

                    $uid_history = new Uid_history();
                    $uid_history->uid_bonusOrder = $bonusOrder;
                    $uid_history->uid_doubleOrder = $doubleOrder;
                    $uid_history->uid_bonusOrderHold = $bonusOrder;
                    $uid_history->cancel = false;
                    $uid_history->save();
                    Log::info("Created new Uid_history: " . print_r($uid_history->toArray(), true));
                } else {
                    $messageAdmin = "uid_history: " . print_r($uid_history->toArray(), true);
                    Log::info($messageAdmin);
                    Log::info($messageAdmin);
                }
            } catch (\Exception $e) {
                $errorMsg = "Ошибка при работе с uid_history для bonusOrder: $bonusOrder: " . $e->getMessage();
                Log::error($errorMsg);
                throw $e; // Повторно выбросить исключение для обработки очередью
            }

            $messageAdmin = "maxExecutionTime $doubleOrderId / $jobId $maxExecutionTime";
            Log::info($messageAdmin);
            Log::info($messageAdmin);

// Безнал

            Log::info("Calling newStatus for bonus order");
            $newStatusBonus = self::newStatus(
                $authorizationBonus,
                $identificationId,
                $apiVersion,
                $responseBonus["url"],
                $bonusOrder,
                "bonus",
                $lastTimeUpdate,
                $updateTime,
                $uid_history
            );
            Log::info("newStatusBonus result: {$newStatusBonus}");

            $lastStatusBonusTime = $lastTimeUpdate;
            Log::info("Set lastStatusBonusTime to: {$lastStatusBonusTime}");

            if ($newStatusBonus != null) {
                $lastStatusBonus = $newStatusBonus;
                Log::info("Updated lastStatusBonus to: {$lastStatusBonus}");
            }


//Нал
            Log::info("Calling newStatus for double order");
            $newStatusDouble = self::newStatus(
                $authorizationDouble,
                $identificationId,
                $apiVersion,
                $responseDouble["url"],
                $doubleOrder,
                "double",
                $lastTimeUpdate,
                $updateTime,
                $uid_history
            );
            Log::info("newStatusDouble result: {$newStatusDouble}");


            $lastStatusDoubleTime = time();
            Log::info("Set lastStatusDoubleTime to: {$lastStatusDoubleTime}");
            if ($newStatusDouble != null) {
                $lastStatusDouble = $newStatusDouble;
                Log::info("Updated lastStatusDouble to: {$lastStatusDouble}");
            }

            Log::info("Entering switch for newStatusDouble: {$newStatusDouble}");
            switch ($newStatusDouble) {
                case "SearchesForCar":
                case "WaitingCarSearch":
                    $updateTime = 5;
                    Log::info("Updated updateTime to 5 due to newStatusDouble");
                    break;
                default:
                    Log::info("Entering nested switch for newStatusBonus: {$newStatusBonus}");
                    switch ($newStatusBonus) {
                        case "SearchesForCar":
                        case "WaitingCarSearch":
                            $updateTime = 5;
                            Log::info("Updated updateTime to 5 due to newStatusBonus");
                            break;
                        default:
                            $updateTime = 5;
                            Log::info("Default updateTime set to 5");
                    }
            }

            Log::info("Calling canceledFinish with lastStatusBonus={$lastStatusBonus}, lastStatusDouble={$lastStatusDouble}");
            $canceledAll = self::canceledFinish(
                $lastStatusBonus,
                $lastStatusDouble,
                $bonusOrderHold,
                $bonusOrder,
                $connectAPI,
                $authorizationBonus,
                $identificationId,
                $apiVersion,
                $doubleOrder,
                $authorizationDouble
            );
            Log::info("canceledFinish result: {$canceledAll}");

            $messageAdmin = "lastStatusBonus0: $lastStatusBonus " .
                "lastStatusDouble0:  $lastStatusDouble " .
                "canceledFinish:0 $canceledAll";

            Log::info($messageAdmin);
            Log::info($messageAdmin);

            if ($canceledAll) {
                Log::info("canceledAll is true, calling newStatus for bonus and double");

                self::newStatus(
                    $authorizationBonus,
                    $identificationId,
                    $apiVersion,
                    $responseBonus["url"],
                    $bonusOrder,
                    "bonus",
                    $lastTimeUpdate,
                    $updateTime,
                    $uid_history
                );

                self::newStatus(
                    $authorizationDouble,
                    $identificationId,
                    $apiVersion,
                    $responseDouble["url"],
                    $doubleOrder,
                    "double",
                    $lastTimeUpdate,
                    $updateTime,
                    $uid_history
                );
                $doubleOrderRecord->delete();
                Log::info("Deleted doubleOrderRecord and exiting");
                return "exit";
            } else {
                //                while (time() - $startTime < $maxExecutionTime) {
                Log::info("Entering main while loop");
                while (true) {

                    Log::info("Calling writeAutoInfo");
                    self::writeAutoInfo($uid_history);


                    $doubleOrderRecord = DoubleOrder::find($doubleOrderId);
                    if (!$doubleOrderRecord) {
                        Log::warning("DoubleOrder record no longer found, exiting");
                        return "exit";
                    }
                    Log::info("Refetched doubleOrderRecord in loop");

                    if (time() <= strtotime($orderwebs->required_time)) {
                        $updateTime = 60;
                        $no_required_time = false;
                        Log::info("Time <= required_time, set updateTime=60, no_required_time=false");
                    } else {
                        $no_required_time = true;
                        Log::info("Time > required_time, no_required_time=true");
                    }
                    $bonusOrder = $uid_history->uid_bonusOrder;

                    $doubleOrder = $uid_history->uid_doubleOrder;

                    $messageAdmin =
                        "bonusOrder  1: $bonusOrder  lastStatusBonus: $lastStatusBonus " .
                        "doubleOrder 1: $doubleOrder lastStatusDouble: $lastStatusDouble";
                    Log::info($messageAdmin);
                    Log::info($messageAdmin);

                    Log::info("Calling canceledFinish in loop");
                    $canceledAll = self::canceledFinish(
                        $lastStatusBonus,
                        $lastStatusDouble,
                        $bonusOrderHold,
                        $bonusOrder,
                        $connectAPI,
                        $authorizationBonus,
                        $identificationId,
                        $apiVersion,
                        $doubleOrder,
                        $authorizationDouble
                    );
                    Log::info("canceledFinish in loop result: {$canceledAll}");

                    if ($canceledAll) {
                        Log::info("canceledAll true in loop, calling newStatus for bonus and double");

                        self::newStatus(
                            $authorizationBonus,
                            $identificationId,
                            $apiVersion,
                            $responseBonus["url"],
                            $bonusOrder,
                            "bonus",
                            $lastTimeUpdate,
                            $updateTime,
                            $uid_history
                        );

                        self::newStatus(
                            $authorizationDouble,
                            $identificationId,
                            $apiVersion,
                            $responseDouble["url"],
                            $doubleOrder,
                            "double",
                            $lastTimeUpdate,
                            $updateTime,
                            $uid_history
                        );
                        $messageAdmin = "canceled while 1 **********************************************
                        lastStatusBonus1:  $lastStatusBonus
                        lastStatusDouble1:  $lastStatusDouble
                        doubleOrderRecord 1 $doubleOrderRecord";

                        Log::info($messageAdmin);
                        Log::info($messageAdmin);

                        $doubleOrderRecord->delete();
                        Log::info("Deleted doubleOrderRecord in loop and exiting");
                        //                    self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                        return "exit";
                    } else {
                        //Безнал ОБРАБОТКА статуса
                        $messageAdmin =
                            " Безнал ОБРАБОТКА статуса " .
                            "bonusOrder  *: $bonusOrder   newStatusBonus: $newStatusBonus " .
                            "doubleOrder *: $doubleOrder  newStatusDouble: $newStatusDouble";
                        Log::info($messageAdmin);
                        Log::info($messageAdmin);


                        Log::info("Entering switch for newStatusBonus: {$newStatusBonus}");
                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                Log::info("Case SearchesForCar or WaitingCarSearch for bonus");
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        Log::info("Nested case SearchesForCar or WaitingCarSearch for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");

                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");

                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }

                                        break;
                                    case "CarFound":
                                    case "Running":
                                        Log::info("Nested case CarFound or Running for double");
                                        //Отмена безнала
                                        self::orderCanceled(
                                            $bonusOrder,
                                            "bonus",
                                            $connectAPI,
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        Log::info("Called orderCanceled for bonus");

                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }

                                        break;
                                    case "CostCalculation":
                                        Log::info("Nested case CostCalculation for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }


                                        //Восстановление нала
                                        Log::info("Calling orderNewCreat for double");
                                        $doubleOrder = $this->restoreLegacyForkLeg(
                                            $uid_history,
                                            'double',
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble['url'],
                                            $responseDouble['parameter']
                                        );
                                        Log::info("Updated doubleOrder: {$doubleOrder}");
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "CarFound":
                                Log::info("Case CarFound for bonus");
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                    case "CarFound":
                                        Log::info("Nested case SearchesForCar, WaitingCarSearch or CarFound for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        //Отмена нала
                                        self::orderCanceled(
                                            $doubleOrder,
                                            "double",
                                            $connectAPI,
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        Log::info("Called orderCanceled for double");
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                    case "Running":
                                        Log::info("Nested case Running for double");
                                        //Отмена безнала
                                        self::orderCanceled(
                                            $bonusOrder,
                                            "bonus",
                                            $connectAPI,
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        Log::info("Called orderCanceled for bonus");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        Log::info("Nested case CostCalculation for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "Running":
                                Log::info("Case Running for bonus");
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                    case "CarFound":
                                    case "Running":
                                        Log::info("Nested case SearchesForCar, WaitingCarSearch, CarFound or Running for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        //Отмена нала
                                        self::orderCanceled(
                                            $doubleOrder,
                                            "double",
                                            $connectAPI,
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        Log::info("Called orderCanceled for double");
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        Log::info("Nested case CostCalculation for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "Canceled":
                                Log::info("Case Canceled for bonus");
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                        Log::info("Nested case SearchesForCar for double");
                                        switch ($lastStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                Log::info("Nested lastStatusBonus case: active search cancel");
                                                if ($this->tryCloseActiveForkSearch(
                                                    $uid_history,
                                                    'bonus',
                                                    $doubleOrder,
                                                    'double',
                                                    $connectAPI,
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion
                                                )) {
                                                    break;
                                                }
                                                // Отказ водителя: ногу безнала создать заново, нал не снимать.
                                            case "Canceled":
                                                if ($this->tryCloseActiveForkSearch(
                                                    $uid_history,
                                                    'bonus',
                                                    $doubleOrder,
                                                    'double',
                                                    $connectAPI,
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion
                                                )) {
                                                    Log::info("Active fork search closed instead of restoring bonus, uid={$bonusOrder}");
                                                    break;
                                                }
                                            case "CarFound":
                                            case "Running":
                                            case "Executed":
                                            case "CostCalculation":
                                                Log::info("Nested lastStatusBonus case: CarFound, Running, Canceled, Executed or CostCalculation");
                                                //Восстановление безнала
                                                $bonusOrder = $this->restoreLegacyForkLeg(
                                                    $uid_history,
                                                    'bonus',
                                                    $authorizationBonus,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseBonus['url'],
                                                    $responseBonus['parameter']
                                                );
                                                Log::info("Called orderNewCreat for bonus, updated bonusOrder: {$bonusOrder}");
                                                //Опрос безнала
                                                $lastTimeUpdate = $lastStatusBonusTime;
                                                Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                                //                                            $updateTime = 5;
                                                $newStatusBonus = self::newStatus(
                                                    $authorizationBonus,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseBonus["url"],
                                                    $bonusOrder,
                                                    "bonus",
                                                    $lastTimeUpdate,
                                                    $updateTime,
                                                    $uid_history
                                                );
                                                Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                                $lastStatusBonusTime = time();
                                                Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                                if ($no_required_time) {
                                                    Log::info("no_required_time true, entering switch for updateTime");
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            switch ($newStatusDouble) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    Log::info("Updated updateTime to 5");
                                                                    break;
                                                                default:
                                                                    $updateTime = 5;
                                                                    Log::info("Default updateTime to 5");
                                                            }
                                                    }
                                                }
                                                //Опрос нала
                                                $lastTimeUpdate = $lastStatusDoubleTime;
                                                Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                                //                                            $updateTime = $updateTime;
                                                $newStatusDouble = self::newStatus(
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseDouble["url"],
                                                    $doubleOrder,
                                                    "double",
                                                    $lastTimeUpdate,
                                                    $updateTime,
                                                    $uid_history
                                                );
                                                Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                                $lastStatusDoubleTime = time();
                                                Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                                if ($no_required_time) {
                                                    Log::info("no_required_time true, entering switch for updateTime");
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            switch ($newStatusBonus) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    Log::info("Updated updateTime to 5");
                                                                    break;
                                                                default:
                                                                    $updateTime = 5;
                                                                    Log::info("Default updateTime to 5");
                                                            }
                                                    }
                                                }
                                        }
                                        break;
                                    case "WaitingCarSearch":
                                        Log::info("Nested case WaitingCarSearch for double");
                                        switch ($lastStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                Log::info("Nested lastStatusBonus case: SearchesForCar or WaitingCarSearch");
                                                //Опрос нала
                                                $lastTimeUpdate = $lastStatusDoubleTime;
                                                Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                                //                                            $updateTime = $updateTime;
                                                $newStatusDouble = self::newStatus(
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseDouble["url"],
                                                    $doubleOrder,
                                                    "double",
                                                    $lastTimeUpdate,
                                                    $updateTime,
                                                    $uid_history
                                                );
                                                Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                                $lastStatusDoubleTime = time();
                                                Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                                if ($no_required_time) {
                                                    Log::info("no_required_time true, entering switch for updateTime");
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            switch ($newStatusBonus) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    Log::info("Updated updateTime to 5");
                                                                    break;
                                                                default:
                                                                    $updateTime = 5;
                                                                    Log::info("Default updateTime to 5");
                                                            }
                                                    }
                                                }
                                                break;
                                            case "Canceled":
                                                if ($this->tryCloseActiveForkSearch(
                                                    $uid_history,
                                                    'bonus',
                                                    $doubleOrder,
                                                    'double',
                                                    $connectAPI,
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion
                                                )) {
                                                    Log::info("Active fork search closed instead of restoring bonus, uid={$bonusOrder}");
                                                    break;
                                                }
                                            case "CarFound":
                                            case "Running":
                                            case "Executed":
                                            case "CostCalculation":
                                                Log::info("Nested lastStatusBonus case: CarFound, Running, Canceled, Executed or CostCalculation");
                                                //Восстановление безнала
                                                $bonusOrder = $this->restoreLegacyForkLeg(
                                                    $uid_history,
                                                    'bonus',
                                                    $authorizationBonus,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseBonus['url'],
                                                    $responseBonus['parameter']
                                                );
                                                Log::info("Called orderNewCreat for bonus, updated bonusOrder: {$bonusOrder}");
                                                //Опрос безнала
                                                $lastTimeUpdate = $lastStatusBonusTime;
                                                Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                                //                                            $updateTime = 5;
                                                $newStatusBonus = self::newStatus(
                                                    $authorizationBonus,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseBonus["url"],
                                                    $bonusOrder,
                                                    "bonus",
                                                    $lastTimeUpdate,
                                                    $updateTime,
                                                    $uid_history
                                                );
                                                Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                                $lastStatusBonusTime = time();
                                                Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                                if ($no_required_time) {
                                                    Log::info("no_required_time true, entering switch for updateTime");
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            switch ($newStatusDouble) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    Log::info("Updated updateTime to 5");
                                                                    break;
                                                                default:
                                                                    $updateTime = 5;
                                                                    Log::info("Default updateTime to 5");
                                                            }
                                                    }
                                                }

                                                //Опрос нала
                                                $lastTimeUpdate = $lastStatusDoubleTime;
                                                Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                                //                                            $updateTime = $updateTime;
                                                $newStatusDouble = self::newStatus(
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseDouble["url"],
                                                    $doubleOrder,
                                                    "double",
                                                    $lastTimeUpdate,
                                                    $updateTime,
                                                    $uid_history
                                                );
                                                Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                                $lastStatusDoubleTime = time();
                                                Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                                if ($no_required_time) {
                                                    Log::info("no_required_time true, entering switch for updateTime");
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            switch ($newStatusBonus) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    Log::info("Updated updateTime to 5");
                                                                    break;
                                                                default:
                                                                    $updateTime = 5;
                                                                    Log::info("Default updateTime to 5");
                                                            }
                                                    }
                                                }
                                                break;
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                        Log::info("Nested case CarFound or Running for double");
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        $messageAdmin =
                                            " Опрос нала вход " .
                                            "bonusOrder  *111: $bonusOrder   newStatusBonus: $newStatusBonus " .
                                            "doubleOrder *111: $doubleOrder  newStatusDouble: $newStatusDouble " .
                                            "updateTime $updateTime ";
                                        Log::info($messageAdmin);
                                        Log::info($messageAdmin);

                                        //                                    $updateTime = $updateTime;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        $messageAdmin =
                                            " Опрос нала выход " .
                                            "bonusOrder  *222: $bonusOrder   newStatusBonus: $newStatusBonus " .
                                            "doubleOrder *222: $doubleOrder  newStatusDouble: $newStatusDouble";
                                        Log::info($messageAdmin);
                                        Log::info($messageAdmin);

                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        Log::info("Nested case CostCalculation for double");
                                        break;
                                }
                                break;
                            case "Executed":
                                Log::info("Case Executed for bonus");
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                    case "CarFound":
                                    case "Running":
                                        Log::info("Nested case SearchesForCar, WaitingCarSearch, CarFound or Running for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        //Отмена нала
                                        self::orderCanceled(
                                            $doubleOrder,
                                            "double",
                                            $connectAPI,
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        Log::info("Called orderCanceled for double");
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }

                                        break;
                                    case "CostCalculation":
                                        Log::info("Nested case CostCalculation for double");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "CostCalculation":
                                Log::info("Case CostCalculation for bonus");
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        Log::info("Nested case SearchesForCar or WaitingCarSearch for double");
                                        //Восстановление безнала
                                        $bonusOrder = $this->restoreLegacyForkLeg(
                                            $uid_history,
                                            'bonus',
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus['url'],
                                            $responseBonus['parameter']
                                        );
                                        Log::info("Called orderNewCreat for bonus, updated bonusOrder: {$bonusOrder}");
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
                                        Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = 5;
                                        $newStatusBonus = self::newStatus(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus["url"],
                                            $bonusOrder,
                                            "bonus",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                        $lastStatusBonusTime = time();
                                        Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
                                        Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                        //                                    $updateTime = $updateTime;
                                        $newStatusDouble = self::newStatus(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble["url"],
                                            $doubleOrder,
                                            "double",
                                            $lastTimeUpdate,
                                            $updateTime,
                                            $uid_history
                                        );
                                        Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                        $lastStatusDoubleTime = time();
                                        Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                        if ($no_required_time) {
                                            Log::info("no_required_time true, entering switch for updateTime");
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    Log::info("Updated updateTime to 5");
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            $updateTime = 5;
                                                            Log::info("Default updateTime to 5");
                                                    }
                                            }
                                        }

                                        break;
                                    case "CarFound":
                                    case "Running":
                                    case "CostCalculation":
                                        Log::info("Nested case CarFound, Running or CostCalculation for double");
                                        switch ($lastStatusBonus) {
                                            case "SearchesForCar":
                                                Log::info("Nested lastStatusBonus case: SearchesForCar");
                                                //Опрос нала
                                                $lastTimeUpdate = $lastStatusDoubleTime;
                                                Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                                //                                            $updateTime = $updateTime;
                                                $newStatusDouble = self::newStatus(
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseDouble["url"],
                                                    $doubleOrder,
                                                    "double",
                                                    $lastTimeUpdate,
                                                    $updateTime,
                                                    $uid_history
                                                );
                                                Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                                $lastStatusDoubleTime = time();
                                                Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                                if ($no_required_time) {
                                                    Log::info("no_required_time true, entering switch for updateTime");
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            Log::info("Updated updateTime to 5");
                                                            break;
                                                        default:
                                                            switch ($newStatusBonus) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    Log::info("Updated updateTime to 5");
                                                                    break;
                                                                default:
                                                                    $updateTime = 5;
                                                                    Log::info("Default updateTime to 5");
                                                            }
                                                    }
                                                }
                                                break;
                                        }
                                        break;
                                }
                                break;
                        }
                        if ($newStatusBonus != null) {
                            $lastStatusBonus = $newStatusBonus;
                            Log::info("Updated lastStatusBonus to: {$lastStatusBonus}");
                        }

                        $bonusOrder = $uid_history->uid_bonusOrder;

                        $doubleOrder = $uid_history->uid_doubleOrder;

                        $messageAdmin =
                            "bonusOrder  2: $bonusOrder   lastStatusBonus: $lastStatusBonus " .
                            "doubleOrder 2: $doubleOrder  lastStatusDouble: $lastStatusDouble";

                        Log::info($messageAdmin);
                        Log::info($messageAdmin);

                        Log::info("Calling canceledFinish after bonus processing");
                        $canceledAll = self::canceledFinish(
                            $lastStatusBonus,
                            $lastStatusDouble,
                            $bonusOrderHold,
                            $bonusOrder,
                            $connectAPI,
                            $authorizationBonus,
                            $identificationId,
                            $apiVersion,
                            $doubleOrder,
                            $authorizationDouble
                        );
                        Log::info("canceledAll after bonus processing: {$canceledAll}");

                        if ($canceledAll) {
                            Log::info("canceledAll true after bonus, calling newStatus for bonus and double");

                            self::newStatus(
                                $authorizationBonus,
                                $identificationId,
                                $apiVersion,
                                $responseBonus["url"],
                                $bonusOrder,
                                "bonus",
                                $lastTimeUpdate,
                                $updateTime,
                                $uid_history
                            );

                            self::newStatus(
                                $authorizationDouble,
                                $identificationId,
                                $apiVersion,
                                $responseDouble["url"],
                                $doubleOrder,
                                "double",
                                $lastTimeUpdate,
                                $updateTime,
                                $uid_history
                            );
                            $messageAdmin = "canceled while " .
                                "lastStatusBonus2:  $lastStatusBonus" .
                                "lastStatusDouble2:  $lastStatusDouble" .
                                "doubleOrderRecord 2 $doubleOrderRecord";

                            Log::info($messageAdmin);
                            Log::info($messageAdmin);

                            $doubleOrderRecord->delete();
                            Log::info("Deleted doubleOrderRecord after bonus canceledAll true");
                            //                        self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                            return "exit";
                        } else {
                            //Нал ОБРАБОТКА статуса

                            $messageAdmin =
                                " Нал ОБРАБОТКА статуса " .
                                "bonusOrder  *: $bonusOrder   newStatusBonus: $newStatusBonus " .
                                "doubleOrder *: $doubleOrder  newStatusDouble: $newStatusDouble";
                            Log::info($messageAdmin);
                            Log::info($messageAdmin);


                            Log::info("Entering switch for newStatusDouble: {$newStatusDouble}");
                            switch ($newStatusDouble) {
                                case "SearchesForCar":
                                    Log::info("Case SearchesForCar for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            Log::info("Nested case SearchesForCar or WaitingCarSearch for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CarFound":
                                        case "Running":
                                            Log::info("Nested case CarFound or Running for bonus");
                                            //Отмена нла
                                            self::orderCanceled(
                                                $doubleOrder,
                                                "double",
                                                $connectAPI,
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion
                                            );
                                            Log::info("Called orderCanceled for double");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }

                                            //Восстановление безнала
                                            $bonusOrder = $this->restoreLegacyForkLeg(
                                                $uid_history,
                                                'bonus',
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus['url'],
                                                $responseBonus['parameter']
                                            );
                                            Log::info("Called orderNewCreat for bonus, updated bonusOrder: {$bonusOrder}");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                                case "WaitingCarSearch":
                                    Log::info("Case WaitingCarSearch for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            Log::info("Nested case SearchesForCar or WaitingCarSearch for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CarFound":
                                        case "Running":
                                            Log::info("Nested case CarFound or Running for bonus");
                                            //Отмена нала
                                            self::orderCanceled(
                                                $doubleOrder,
                                                "double",
                                                $connectAPI,
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion
                                            );
                                            Log::info("Called orderCanceled for double");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            //Восстановление безнала
                                            $bonusOrder = $this->restoreLegacyForkLeg(
                                                $uid_history,
                                                'bonus',
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus['url'],
                                                $responseBonus['parameter']
                                            );
                                            Log::info("Called orderNewCreat for bonus, updated bonusOrder: {$bonusOrder}");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                                case "CarFound":
                                    Log::info("Case CarFound for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            Log::info("Nested case SearchesForCar or WaitingCarSearch for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            //Отмена безнала
                                            self::orderCanceled(
                                                $bonusOrder,
                                                "bonus",
                                                $connectAPI,
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion
                                            );
                                            Log::info("Called orderCanceled for bonus");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CarFound":
                                        case "Running":
                                            Log::info("Nested case CarFound or Running for bonus");
                                            //Отмена безнала
                                            self::orderCanceled(
                                                $bonusOrder,
                                                "bonus",
                                                $connectAPI,
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion
                                            );
                                            Log::info("Called orderCanceled for bonus");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                                case "Running":
                                    Log::info("Case Running for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                        case "CarFound":
                                            Log::info("Nested case SearchesForCar, WaitingCarSearch or CarFound for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
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
                                            Log::info("Called orderCanceled for bonus");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "Running":
                                            Log::info("Nested case Running for bonus");
                                            //Отмена нала
                                            self::orderCanceled(
                                                $doubleOrder,
                                                "double",
                                                $connectAPI,
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion
                                            );
                                            Log::info("Called orderCanceled for double");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                                case "Canceled":
                                    Log::info("Case Canceled for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                            Log::info("Nested case SearchesForCar for bonus");
                                            switch ($lastStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    Log::info("Nested lastStatusDouble case: SearchesForCar or WaitingCarSearch");
                                                    if ($this->tryCloseActiveForkSearch(
                                                        $uid_history,
                                                        'double',
                                                        $bonusOrder,
                                                        'bonus',
                                                        $connectAPI,
                                                        $authorizationBonus,
                                                        $identificationId,
                                                        $apiVersion
                                                    )) {
                                                        break;
                                                    }
                                                    // Отказ водителя: ногу нала создать заново, безнал не снимать.
                                                case "Canceled":
                                                    if ($this->tryCloseActiveForkSearch(
                                                        $uid_history,
                                                        'double',
                                                        $bonusOrder,
                                                        'bonus',
                                                        $connectAPI,
                                                        $authorizationBonus,
                                                        $identificationId,
                                                        $apiVersion
                                                    )) {
                                                        Log::info("Active fork search closed instead of restoring double, uid={$doubleOrder}");
                                                        break;
                                                    }
                                                case "CarFound":
                                                case "Running":
                                                case "Executed":
                                                case "CostCalculation":
                                                    Log::info("Nested lastStatusDouble case: CarFound, Running, Canceled, Executed or CostCalculation");
                                                    //Восстановление нала
                                                    //                                        if ($doubleCancel) {
                                                    $doubleOrder = $this->restoreLegacyForkLeg(
                                                        $uid_history,
                                                        'double',
                                                        $authorizationDouble,
                                                        $identificationId,
                                                        $apiVersion,
                                                        $responseDouble['url'],
                                                        $responseDouble['parameter']
                                                    );
                                                    Log::info("Called orderNewCreat for double, updated doubleOrder: {$doubleOrder}");
                                                    //Опрос нала
                                                    $lastTimeUpdate = $lastStatusDoubleTime;
                                                    Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                                    //                                                $updateTime = $updateTime;
                                                    $newStatusDouble = self::newStatus(
                                                        $authorizationDouble,
                                                        $identificationId,
                                                        $apiVersion,
                                                        $responseDouble["url"],
                                                        $doubleOrder,
                                                        "double",
                                                        $lastTimeUpdate,
                                                        $updateTime,
                                                        $uid_history
                                                    );
                                                    Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                                    $lastStatusDoubleTime = time();
                                                    Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                                    if ($no_required_time) {
                                                        Log::info("no_required_time true, entering switch for updateTime");
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                switch ($newStatusBonus) {
                                                                    case "SearchesForCar":
                                                                    case "WaitingCarSearch":
                                                                        $updateTime = 5;
                                                                        Log::info("Updated updateTime to 5");
                                                                        break;
                                                                    default:
                                                                        $updateTime = 5;
                                                                        Log::info("Default updateTime to 5");
                                                                }
                                                        }
                                                    }
                                                    //Опрос безнала
                                                    $lastTimeUpdate = $lastStatusBonusTime;
                                                    Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                                    //                                                $updateTime = $updateTime;
                                                    $newStatusBonus = self::newStatus(
                                                        $authorizationBonus,
                                                        $identificationId,
                                                        $apiVersion,
                                                        $responseBonus["url"],
                                                        $bonusOrder,
                                                        "bonus",
                                                        $lastTimeUpdate,
                                                        $updateTime,
                                                        $uid_history
                                                    );
                                                    Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                                    $lastStatusBonusTime = time();
                                                    Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                                    if ($no_required_time) {
                                                        Log::info("no_required_time true, entering switch for updateTime");
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                switch ($newStatusDouble) {
                                                                    case "SearchesForCar":
                                                                    case "WaitingCarSearch":
                                                                        $updateTime = 5;
                                                                        Log::info("Updated updateTime to 5");
                                                                        break;
                                                                    default:
                                                                        $updateTime = 5;
                                                                        Log::info("Default updateTime to 5");
                                                                }
                                                        }
                                                    }
                                                    break;
                                            }
                                            break;
                                        case "WaitingCarSearch":
                                            Log::info("Nested case WaitingCarSearch for bonus");
                                            switch ($lastStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    Log::info("Nested lastStatusDouble case: SearchesForCar or WaitingCarSearch");
                                                    if ($this->tryCloseActiveForkSearch(
                                                        $uid_history,
                                                        'double',
                                                        $bonusOrder,
                                                        'bonus',
                                                        $connectAPI,
                                                        $authorizationBonus,
                                                        $identificationId,
                                                        $apiVersion
                                                    )) {
                                                        break;
                                                    }
                                                    // Отказ водителя: ногу нала создать заново, безнал не снимать.
                                                case "Canceled":
                                                    if ($this->tryCloseActiveForkSearch(
                                                        $uid_history,
                                                        'double',
                                                        $bonusOrder,
                                                        'bonus',
                                                        $connectAPI,
                                                        $authorizationBonus,
                                                        $identificationId,
                                                        $apiVersion
                                                    )) {
                                                        Log::info("Active fork search closed instead of restoring double, uid={$doubleOrder}");
                                                        break;
                                                    }
                                                case "CarFound":
                                                case "Running":
                                                case "Executed":
                                                case "CostCalculation":
                                                    Log::info("Nested lastStatusDouble case: CarFound, Running, Canceled, Executed or CostCalculation");
                                                    //Восстановление нала
                                                    $doubleOrder = $this->restoreLegacyForkLeg(
                                                        $uid_history,
                                                        'double',
                                                        $authorizationDouble,
                                                        $identificationId,
                                                        $apiVersion,
                                                        $responseDouble['url'],
                                                        $responseDouble['parameter']
                                                    );
                                                    Log::info("Called orderNewCreat for double, updated doubleOrder: {$doubleOrder}");
                                                    //Опрос нала
                                                    $lastTimeUpdate = $lastStatusDoubleTime;
                                                    Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                                    //                                                $updateTime = $updateTime;
                                                    $newStatusDouble = self::newStatus(
                                                        $authorizationDouble,
                                                        $identificationId,
                                                        $apiVersion,
                                                        $responseDouble["url"],
                                                        $doubleOrder,
                                                        "double",
                                                        $lastTimeUpdate,
                                                        $updateTime,
                                                        $uid_history
                                                    );
                                                    Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                                    $lastStatusDoubleTime = time();
                                                    Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                                    if ($no_required_time) {
                                                        Log::info("no_required_time true, entering switch for updateTime");
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                switch ($newStatusBonus) {
                                                                    case "SearchesForCar":
                                                                    case "WaitingCarSearch":
                                                                        $updateTime = 5;
                                                                        Log::info("Updated updateTime to 5");
                                                                        break;
                                                                    default:
                                                                        $updateTime = 5;
                                                                        Log::info("Default updateTime to 5");
                                                                }
                                                        }
                                                    }
                                                    //Опрос безнала
                                                    $lastTimeUpdate = $lastStatusBonusTime;
                                                    Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                                    //                                                $updateTime = $updateTime;
                                                    $newStatusBonus = self::newStatus(
                                                        $authorizationBonus,
                                                        $identificationId,
                                                        $apiVersion,
                                                        $responseBonus["url"],
                                                        $bonusOrder,
                                                        "bonus",
                                                        $lastTimeUpdate,
                                                        $updateTime,
                                                        $uid_history
                                                    );
                                                    Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                                    $lastStatusBonusTime = time();
                                                    Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                                    if ($no_required_time) {
                                                        Log::info("no_required_time true, entering switch for updateTime");
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                switch ($newStatusDouble) {
                                                                    case "SearchesForCar":
                                                                    case "WaitingCarSearch":
                                                                        $updateTime = 5;
                                                                        Log::info("Updated updateTime to 5");
                                                                        break;
                                                                    default:
                                                                        $updateTime = 5;
                                                                        Log::info("Default updateTime to 5");
                                                                }
                                                        }
                                                    }
                                                    break;
                                            }
                                            break;
                                        case "CarFound":
                                        case "Running":
                                            Log::info("Nested case CarFound or Running for bonus");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            break;
                                    }
                                    break;
                                case "Executed":
                                    Log::info("Case Executed for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                        case "CarFound":
                                        case "Running":
                                            Log::info("Nested case SearchesForCar, WaitingCarSearch, CarFound or Running for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            //Отмена безнала
                                            self::orderCanceled(
                                                $bonusOrder,
                                                "bonus",
                                                $connectAPI,
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion
                                            );
                                            Log::info("Called orderCanceled for bonus");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = 5;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                                case "CostCalculation":
                                    Log::info("Case CostCalculation for double");
                                    switch ($newStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            Log::info("Nested case SearchesForCar or WaitingCarSearch for bonus");
                                            //Восстановление нала
                                            //                                if ($doubleCancel) {
                                            $doubleOrder = $this->restoreLegacyForkLeg(
                                                $uid_history,
                                                'double',
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble['url'],
                                                $responseDouble['parameter']
                                            );
                                            Log::info("Called orderNewCreat for double, updated doubleOrder: {$doubleOrder}");
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
                                            Log::info("Set lastTimeUpdate to lastStatusDoubleTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusDouble = self::newStatus(
                                                $authorizationDouble,
                                                $identificationId,
                                                $apiVersion,
                                                $responseDouble["url"],
                                                $doubleOrder,
                                                "double",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusDouble: {$newStatusDouble}");
                                            $lastStatusDoubleTime = time();
                                            Log::info("Updated lastStatusDoubleTime: {$lastStatusDoubleTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CarFound":
                                        case "Running":
                                            Log::info("Nested case CarFound or Running for bonus");
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
                                            Log::info("Set lastTimeUpdate to lastStatusBonusTime: {$lastTimeUpdate}");
                                            //                                        $updateTime = $updateTime;
                                            $newStatusBonus = self::newStatus(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus["url"],
                                                $bonusOrder,
                                                "bonus",
                                                $lastTimeUpdate,
                                                $updateTime,
                                                $uid_history
                                            );
                                            Log::info("Updated newStatusBonus: {$newStatusBonus}");
                                            $lastStatusBonusTime = time();
                                            Log::info("Updated lastStatusBonusTime: {$lastStatusBonusTime}");
                                            if ($no_required_time) {
                                                Log::info("no_required_time true, entering switch for updateTime");
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        Log::info("Updated updateTime to 5");
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                Log::info("Updated updateTime to 5");
                                                                break;
                                                            default:
                                                                $updateTime = 5;
                                                                Log::info("Default updateTime to 5");
                                                        }
                                                }
                                            }
                                            break;
                                        case "CostCalculation":
                                            Log::info("Nested case CostCalculation for bonus");
                                            break;
                                    }
                                    break;
                            }
                            if ($newStatusDouble != null) {
                                $lastStatusDouble = $newStatusDouble;
                                Log::info("Updated lastStatusDouble to: {$lastStatusDouble}");
                            }

                            $bonusOrder = $uid_history->uid_bonusOrder;

                            $messageAdmin =
                                "bonusOrder  3: $bonusOrder  lastStatusBonus: $lastStatusBonus " .
                                "doubleOrder 3: $doubleOrder lastStatusDouble: $lastStatusDouble";

                            Log::info($messageAdmin);
                            Log::info($messageAdmin);

                            Log::info("Calling canceledFinish after double processing");
                            $canceledAll = self::canceledFinish(
                                $lastStatusBonus,
                                $lastStatusDouble,
                                $bonusOrderHold,
                                $bonusOrder,
                                $connectAPI,
                                $authorizationBonus,
                                $identificationId,
                                $apiVersion,
                                $doubleOrder,
                                $authorizationDouble
                            );
                            Log::info("canceledAll after double processing: {$canceledAll}");

                            if ($canceledAll) {
                                Log::info("canceledAll true after double, calling newStatus for bonus and double");

                                self::newStatus(
                                    $authorizationBonus,
                                    $identificationId,
                                    $apiVersion,
                                    $responseBonus["url"],
                                    $bonusOrder,
                                    "bonus",
                                    $lastTimeUpdate,
                                    $updateTime,
                                    $uid_history
                                );

                                self::newStatus(
                                    $authorizationDouble,
                                    $identificationId,
                                    $apiVersion,
                                    $responseDouble["url"],
                                    $doubleOrder,
                                    "double",
                                    $lastTimeUpdate,
                                    $updateTime,
                                    $uid_history
                                );
                                $messageAdmin = "canceled while
                                 lastStatusBonus3:  $lastStatusBonus
                                 lastStatusDouble3:  $lastStatusDouble
                                 doubleOrderRecord 3 $doubleOrderRecord";

                                Log::info($messageAdmin);
                                Log::info($messageAdmin);


                                $doubleOrderRecord->delete();
                                Log::info("Deleted doubleOrderRecord after double canceledAll true");
                                //                            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                                return "exit";
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $errorMsg = "Критическая ошибка в startNewProcessExecutionStatusJobLegacy для doubleOrderId: $doubleOrderId: " . $e->getMessage();
            Log::error($errorMsg);
            throw $e;
        }
    }

    private function legacyForkStatusPollRequested($doubleOrderId): bool
    {
        try {
            $record = DoubleOrder::find($doubleOrderId);
            if ($record === null || $record->responseBonusStr === null || $record->responseBonusStr === '') {
                return false;
            }

            $responseBonus = json_decode($record->responseBonusStr, true);
            if (!is_array($responseBonus)) {
                return false;
            }

            $bonusOrder = $responseBonus['dispatching_order_uid'] ?? null;
            if ($bonusOrder === null || $bonusOrder === '') {
                return false;
            }

            $bonusOrderHold = (new MemoryOrderChangeController())->show((string) $bonusOrder);
            $order = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();
            if ($order === null) {
                return false;
            }

            return PaymentFlow::usesLegacyForkStatusPoll($order->payment_flow_mode ?? 0);
        } catch (\Throwable $e) {
            Log::warning('legacy fork poll lookup failed: ' . $e->getMessage(), [
                'doubleOrderId' => $doubleOrderId,
            ]);

            return false;
        }
    }

    /**
     * Клиент или оператор снял ногу, пока вторая ещё в поиске и машина не назначена.
     * Закрыть вторую ногу и заказ клиента, не создавая новый поиск.
     * Отказ водителя (2, 3, 5) ногу не закрывает: её создают заново.
     *
     * @param Uid_history|null $uidHistory
     * @param string $canceledLeg bonus|double
     * @param string $siblingLeg bonus|double
     */
    private function tryCloseActiveForkSearch(
        $uidHistory,
        string $canceledLeg,
        $siblingUid,
        string $siblingLeg,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ): bool {
        if (!$uidHistory instanceof Uid_history) {
            return false;
        }

        $uidHistory->refresh();
        $bonus = json_decode((string) $uidHistory->bonus_status, true);
        $double = json_decode((string) $uidHistory->double_status, true);
        $canceledSnapshot = $canceledLeg === 'bonus'
            ? (is_array($bonus) ? $bonus : null)
            : (is_array($double) ? $double : null);
        $otherSnapshot = $canceledLeg === 'bonus'
            ? (is_array($double) ? $double : null)
            : (is_array($bonus) ? $bonus : null);

        $hold = (string) $uidHistory->uid_bonusOrderHold;
        $orderweb = $hold !== ''
            ? Orderweb::where('dispatching_order_uid', $hold)->first()
            : null;
        $armed = OrderStatusController::isForkLegRecreationArmed($uidHistory, $canceledLeg);
        if (!OrderStatusController::shouldCloseActiveForkSearchInsteadOfRestore(
            $canceledSnapshot,
            $otherSnapshot,
            $orderweb,
            $armed
        )) {
            return false;
        }

        $siblingUid = ForkOrderCancelLegResolver::liveSiblingUid($uidHistory, $siblingLeg, $siblingUid);
        if ($siblingUid !== null && $siblingUid !== '') {
            self::orderCanceled(
                $siblingUid,
                $siblingLeg,
                $connectAPI,
                $authorization,
                $identificationId,
                $apiVersion
            );
            Log::info("Called orderCanceled for {$siblingLeg} instead of restoring {$canceledLeg}", [
                'sibling_uid' => $siblingUid,
                'hold' => $hold,
            ]);
        }

        OrderStatusController::markForkLegDispatcherCanceled($uidHistory, $canceledLeg);
        OrderStatusController::markExplicitForkCancelRequested($uidHistory);

        if ($orderweb !== null) {
            OrderStatusController::applyCanceledOrderweb($orderweb);
            $orderweb->save();
            (new AndroidTestOSMController())->cleanupFirestoreOnClientCancel($hold);
            OrderStatusController::notifyForkOrderCanceledPush($orderweb, $hold);
        }

        Log::info('Active fork search closed instead of leg restore', [
            'canceled_leg' => $canceledLeg,
            'hold' => $hold,
        ]);

        return true;
    }

    /**
     * Новая нога вилки заменяет текущий UID и остаётся в цепочке, чтобы холд ждал все заказы.
     *
     * @param Uid_history|null $uidHistory
     * @param string $leg bonus|double
     */
    private function restoreLegacyForkLeg(
        $uidHistory,
        string $leg,
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $parameter
    ) {
        if ($uidHistory instanceof Uid_history
            && (OrderStatusController::isExplicitForkCancelRequested($uidHistory)
                || OrderStatusController::isForkLegDispatcherCanceled($uidHistory, $leg))
        ) {
            $current = $leg === 'bonus'
                ? (string) $uidHistory->uid_bonusOrder
                : (string) $uidHistory->uid_doubleOrder;
            Log::info('Skip legacy fork restore: cancel already requested', [
                'leg' => $leg,
                'uid' => $current,
                'hold' => $uidHistory->uid_bonusOrderHold,
            ]);

            return $current;
        }

        $newUid = self::orderNewCreat(
            $authorization,
            $identificationId,
            $apiVersion,
            $url,
            $parameter
        );

        if ($uidHistory instanceof \App\Models\Uid_history) {
            $published = (new ForkLegUidChain())->publishLeg(
                $uidHistory,
                $leg,
                is_string($newUid) ? $newUid : null
            );
            if ($published !== null) {
                Log::info('Legacy fork leg published', [
                    'leg' => $leg,
                    'uid' => $published,
                    'hold' => $uidHistory->uid_bonusOrderHold,
                ]);

                return $published;
            }
        }

        return $newUid;
    }

}
