<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
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
use App\Models\Uid_history;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
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
        $startTime = time(); // Начальное время
        $maxExecutionTime = 0.5*60; //время жизни отмены
        do {
//            try {
//                $response = Http::withHeaders([
//                    "Authorization" => $authorization,
//                    "X-WO-API-APP-ID" => $identificationId,
//                    "X-API-VERSION" => $apiVersion
//                ])->post($url, $parameter);
//                // Проверяем успешность ответа
//                if ($response->successful()) {
//                    // Логируем тело ответа
//                    Log::debug("function postRequestHTTP " . $response->body());
//                    // Обрабатываем успешный ответ
//                    // Ваш код для обработки успешного ответа
//                    return $response;
//                } else {
//                    // Логируем ошибки в случае неудачного запроса
//                    Log::error("function postRequestHTTP Request failed with status: " . $response->status());
//                    Log::error("function postRequestHTTP Response: " . $response->body());
//                }
//            } catch (\Exception $e) {
//                // Обработка исключений
//                Log::error("function postRequestHTTP Exception caught: " . $e->getMessage());
//            }
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => $identificationId,
                "X-API-VERSION" => $apiVersion
            ])->post($url, $parameter);
            // Проверяем успешность ответа
            if ($response->successful()) {
                // Логируем тело ответа
                Log::debug("function postRequestHTTP " . $response->body());
                // Обрабатываем успешный ответ
                // Ваш код для обработки успешного ответа
                return $response;
            } else {
                // Логируем ошибки в случае неудачного запроса
                Log::error("function postRequestHTTP Request failed with status: " . $response->status());
                Log::error("function postRequestHTTP Response: " . $response->body());
            }

            sleep(5);
        } while (time() - $startTime < $maxExecutionTime);
        return null;
    }
    public function checkAndRestoreDatabaseConnection()
    {
        do {
            try {
                // Проверяем, что подключение к базе данных установлено
                if (!DB::connection()->getPdo()) {
                    DB::connection()->reconnect(); // Пытаемся восстановить подключение
                }

                Log::info("Database connection established");

            } catch (\Exception $e) {
                Log::error("Error establishing database connection: " . $e->getMessage());
            }
        } while (!DB::connection()->getPdo());
    }

    public function startNewProcessExecutionStatusEmu($doubleOrderId): string
    {
        self::checkAndRestoreDatabaseConnection();
        ExecStatusHistory::truncate();
        Log::info("startNewProcessExecutionStatusEmu");

        $doubleOrderRecord = DoubleOrder::find($doubleOrderId);

        $responseBonusStr = $doubleOrderRecord->responseBonusStr;
        $responseDoubleStr = $doubleOrderRecord->responseDoubleStr;
        $authorizationBonus = $doubleOrderRecord->authorizationBonus;
        $authorizationDouble = $doubleOrderRecord->authorizationDouble;
        $connectAPI = $doubleOrderRecord->connectAPI;
        $identificationId = $doubleOrderRecord->identificationId;
        $apiVersion = $doubleOrderRecord->apiVersion;

//        $doubleOrderRecord->delete();


        $maxExecutionTime = 3*24*60*60; // Максимальное время выполнения - 3 суток
//          $maxExecutionTime = 4 * 60 * 60; // Максимальное время выполнения - 4 часа
//        $maxExecutionTime = 10*60; // Максимальное время выполнения - 3 суток
        $startTime = time();


        $responseBonus = json_decode($responseBonusStr, true);
        $bonusOrder = $responseBonus['dispatching_order_uid'];
        $bonusOrderHold = $bonusOrder;
        //Увеличеваем максимальное время для отстроченного заказа
        $orderwebs = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();
        if ($orderwebs->required_time != null) {
            $maxExecutionTime +=  strtotime($orderwebs->required_time);
        }

        $lastTimeUpdate = time();

        $updateTime = 5;

        $responseDouble = json_decode($responseDoubleStr, true);
        $doubleOrder = $responseDouble['dispatching_order_uid'];

        $uid_history = Uid_history::where("uid_bonusOrderHold", $bonusOrder)->first();

        if ($uid_history == null) {
            $uid_history = new Uid_history();
            $uid_history->uid_bonusOrder = $bonusOrder;
            $uid_history->uid_doubleOrder = $doubleOrder;
            $uid_history->uid_bonusOrderHold = $bonusOrder;
            $uid_history->cancel = false;
            $uid_history->save();
        } else {
            $bonusOrder = $uid_history->uid_bonusOrder;
            $doubleOrder = $uid_history->uid_doubleOrder;
        }

// Безнал

        $newStatusBonus =  self::newStatus(
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

        $lastStatusBonusTime = $lastTimeUpdate;
        $lastStatusBonus = $newStatusBonus;

//Нал
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
        $lastStatusDoubleTime = time();
        $lastStatusDouble = $newStatusDouble;
        switch ($newStatusDouble) {
            case "SearchesForCar":
            case "WaitingCarSearch":
                $updateTime = 5;
                break;
            default:
                switch ($newStatusBonus) {
                    case "SearchesForCar":
                    case "WaitingCarSearch":
                        $updateTime = 5;
                        break;
                    default:
                        $updateTime = 30;
                }
        }

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
        Log::debug("lastStatusBonus0: " . $lastStatusBonus);
        Log::debug("lastStatusDouble0: " . $lastStatusDouble);
        Log::debug("canceledFinish:0 " . $canceledAll);

        if ($canceledAll) {
            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
            Log::info("doubleOrderRecord 0 $doubleOrderRecord");
            $doubleOrderRecord->delete();
            return "finish Canceled by User";
        } else {
            while (time() - $startTime < $maxExecutionTime) {
                if (time() <= strtotime($orderwebs->required_time)) {
                    $updateTime = 60;
                    $no_required_time = false;
                } else {
                    $no_required_time = true;
                }
                $bonusOrder = $uid_history->uid_bonusOrder;

                Log::debug("bonusOrder  1: $bonusOrder");
                Log::debug("lastStatusBonus 1: $lastStatusBonus");

                $doubleOrder = $uid_history->uid_doubleOrder;


                Log::debug("doubleOrder  1: $doubleOrder");
                Log::debug("lastStatusDouble 1: $lastStatusDouble");
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

                if ($canceledAll) {
                    Log::debug("canceled while 1 **********************************************");
                    Log::debug("lastStatusBonus1: " . $lastStatusBonus);
                    Log::debug("lastStatusDouble1: " . $lastStatusDouble);
//                    $uid_history->delete();
                    Log::info("doubleOrderRecord 1 $doubleOrderRecord");
                    $doubleOrderRecord->delete();
                    self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
                    break;
                } else {
                    //Безнал ОБРАБОТКА статуса
                    switch ($newStatusBonus) {
                        case "SearchesForCar":
                        case "WaitingCarSearch":
                            switch ($newStatusDouble) {
                                case "SearchesForCar":
                                case "WaitingCarSearch":
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }

                                    break;
                                case "CarFound":
                                case "Running":
                                    //Отмена безнала
                                    self::orderCanceled(
                                        $bonusOrder,
                                        "bonus",
                                        $connectAPI,
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion
                                    );
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }

                                    break;
                                case "CostCalculation":
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }


                                    //Восстановление нала
                                    $doubleOrder = self::orderNewCreat(
                                        $authorizationDouble,
                                        $identificationId,
                                        $apiVersion,
                                        $responseDouble['url'],
                                        $responseDouble['parameter']
                                    );
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    break;
                            }
                            break;
                        case "CarFound":
                            switch ($newStatusDouble) {
                                case "SearchesForCar":
                                case "WaitingCarSearch":
                                case "CarFound":
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
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
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    break;
                                case "Running":
                                    //Отмена безнала
                                    self::orderCanceled(
                                        $bonusOrder,
                                        "bonus",
                                        $connectAPI,
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion
                                    );
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    break;
                                case "CostCalculation":
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
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
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
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
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    break;
                                case "CostCalculation":
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
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
                                            //Восстановление безнала
                                            $bonusOrder = self::orderNewCreat(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus['url'],
                                                $responseBonus['parameter']
                                            );
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
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
                                            $lastStatusBonusTime = time();
                                            if ($no_required_time) {
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                break;
                                                            default:
                                                                $updateTime = 30;
                                                        }
                                                }
                                            }
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
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
                                            $lastStatusDoubleTime = time();
                                            if ($no_required_time) {
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                break;
                                                            default:
                                                                $updateTime = 30;
                                                        }
                                                }
                                            }
                                    }
                                    break;
                                case "WaitingCarSearch":
                                    switch ($lastStatusBonus) {
                                        case "SearchesForCar":
                                        case "WaitingCarSearch":
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
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
                                            $lastStatusDoubleTime = time();
                                            if ($no_required_time) {
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                break;
                                                            default:
                                                                $updateTime = 30;
                                                        }
                                                }
                                            }
                                            break;
                                        case "CarFound":
                                        case "Running":
                                        case "Canceled":
                                        case "Executed":
                                        case "CostCalculation":
                                            //Восстановление безнала
                                            $bonusOrder = self::orderNewCreat(
                                                $authorizationBonus,
                                                $identificationId,
                                                $apiVersion,
                                                $responseBonus['url'],
                                                $responseBonus['parameter']
                                            );
                                            //Опрос безнала
                                            $lastTimeUpdate = $lastStatusBonusTime;
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
                                            $lastStatusBonusTime = time();
                                            if ($no_required_time) {
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        switch ($newStatusDouble) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                break;
                                                            default:
                                                                $updateTime = 30;
                                                        }
                                                }
                                            }

                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
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
                                            $lastStatusDoubleTime = time();
                                            if ($no_required_time) {
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                break;
                                                            default:
                                                                $updateTime = 30;
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                                case "CarFound":
                                case "Running":
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
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
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
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
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }

                                    break;
                                case "CostCalculation":
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    break;
                            }
                            break;
                        case "CostCalculation":
                            switch ($newStatusDouble) {
                                case "SearchesForCar":
                                case "WaitingCarSearch":
                                    //Восстановление безнала
                                    $bonusOrder = self::orderNewCreat(
                                        $authorizationBonus,
                                        $identificationId,
                                        $apiVersion,
                                        $responseBonus['url'],
                                        $responseBonus['parameter']
                                    );
                                    //Опрос безнала
                                    $lastTimeUpdate = $lastStatusBonusTime;
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
                                    $lastStatusBonusTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusBonus) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }
                                    //Опрос нала
                                    $lastTimeUpdate = $lastStatusDoubleTime;
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
                                    $lastStatusDoubleTime = time();
                                    if ($no_required_time) {
                                        switch ($newStatusDouble) {
                                            case "SearchesForCar":
                                            case "WaitingCarSearch":
                                                $updateTime = 5;
                                                break;
                                            default:
                                                switch ($newStatusBonus) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        $updateTime = 30;
                                                }
                                        }
                                    }

                                    break;
                                case "CarFound":
                                case "Running":
                                case "CostCalculation":
                                    switch ($lastStatusBonus) {
                                        case "SearchesForCar":
                                            //Опрос нала
                                            $lastTimeUpdate = $lastStatusDoubleTime;
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
                                            $lastStatusDoubleTime = time();
                                            if ($no_required_time) {
                                                switch ($newStatusDouble) {
                                                    case "SearchesForCar":
                                                    case "WaitingCarSearch":
                                                        $updateTime = 5;
                                                        break;
                                                    default:
                                                        switch ($newStatusBonus) {
                                                            case "SearchesForCar":
                                                            case "WaitingCarSearch":
                                                                $updateTime = 5;
                                                                break;
                                                            default:
                                                                $updateTime = 30;
                                                        }
                                                }
                                            }
                                            break;
                                    }
                                    break;
                            }
                            break;
                    }
                    $lastStatusBonus = $newStatusBonus;
                    Log::debug(" Безнал после обработки new Status: " . $lastStatusBonus);
                    $bonusOrder = $uid_history->uid_bonusOrder;
//                    $lastStatusBonus = self::newStatus(
//                        $authorizationBonus,
//                        $identificationId,
//                        $apiVersion,
//                        $responseBonus["url"],
//                        $bonusOrder,
//                        "bonus",
//                        $lastTimeUpdate,
//                        $updateTime,
//                        $uid_history
//                    );
                    Log::debug("bonusOrder  2: $bonusOrder");
                    Log::debug("lastStatusBonus 2: $lastStatusBonus");

                    $doubleOrder = $uid_history->uid_doubleOrder;
//                    $lastStatusDouble = self::newStatus(
//                        $authorizationDouble,
//                        $identificationId,
//                        $apiVersion,
//                        $responseDouble["url"],
//                        $doubleOrder,
//                        "double",
//                        $lastTimeUpdate,
//                        $updateTime,
//                        $uid_history
//                    );

                    Log::debug("doubleOrder  2: $doubleOrder");
                    Log::debug("lastStatusDouble 2: $lastStatusDouble");
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

                    if ($canceledAll) {
                        Log::debug("canceled while ");
                        Log::debug("lastStatusBonus2: " . $lastStatusBonus);
                        Log::debug("lastStatusDouble2: " . $lastStatusDouble);
                        Log::info("doubleOrderRecord 2 $doubleOrderRecord");
                        $doubleOrderRecord->delete();
                        self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
                        break;
                    } else {
                        //Нал ОБРАБОТКА статуса
                        switch ($newStatusDouble) {
                            case "SearchesForCar":
                                switch ($newStatusBonus) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                        //Отмена нла
                                        self::orderCanceled(
                                            $doubleOrder,
                                            "double",
                                            $connectAPI,
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }

                                        //Восстановление безнала
                                        $bonusOrder = self::orderNewCreat(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus['url'],
                                            $responseBonus['parameter']
                                        );
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "WaitingCarSearch":
                                switch ($newStatusBonus) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                        //Отмена нала
                                        self::orderCanceled(
                                            $doubleOrder,
                                            "double",
                                            $connectAPI,
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        //Восстановление безнала
                                        $bonusOrder = self::orderNewCreat(
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion,
                                            $responseBonus['url'],
                                            $responseBonus['parameter']
                                        );
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "CarFound":
                                switch ($newStatusBonus) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
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
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                        //Отмена безнала
                                        self::orderCanceled(
                                            $bonusOrder,
                                            "bonus",
                                            $connectAPI,
                                            $authorizationBonus,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
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
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
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
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "Running":
                                        //Отмена нала
                                        self::orderCanceled(
                                            $doubleOrder,
                                            "double",
                                            $connectAPI,
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion
                                        );
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
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
                                                //Опрос безнала
                                                $lastTimeUpdate = $lastStatusBonusTime;
//                                                $updateTime = 5;
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
                                                $lastStatusBonusTime = time();
                                                if ($no_required_time) {
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            switch ($newStatusDouble) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    break;
                                                                default:
                                                                    $updateTime = 30;
                                                            }
                                                    }
                                                }
                                                break;
                                            case "CarFound":
                                            case "Running":
                                            case "Canceled":
                                            case "Executed":
                                            case "CostCalculation":
                                                //Восстановление нала
//                                        if ($doubleCancel) {
                                                $doubleOrder = self::orderNewCreat(
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseDouble['url'],
                                                    $responseDouble['parameter']
                                                );
                                                //Опрос нала
                                                $lastTimeUpdate = $lastStatusDoubleTime;
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
                                                $lastStatusDoubleTime = time();
                                                if ($no_required_time) {
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            switch ($newStatusBonus) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    break;
                                                                default:
                                                                    $updateTime = 30;
                                                            }
                                                    }
                                                }
                                                //Опрос безнала
                                                $lastTimeUpdate = $lastStatusBonusTime;
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
                                                $lastStatusBonusTime = time();
                                                if ($no_required_time) {
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            switch ($newStatusDouble) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    break;
                                                                default:
                                                                    $updateTime = 30;
                                                            }
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
                                                //Опрос безнала
                                                $lastTimeUpdate = $lastStatusBonusTime;
//                                                $updateTime = 5;
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
                                                $lastStatusBonusTime = time();
                                                if ($no_required_time) {
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            switch ($newStatusDouble) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    break;
                                                                default:
                                                                    $updateTime = 30;
                                                            }
                                                    }
                                                }
                                                break;
                                            case "CarFound":
                                            case "Running":
                                            case "Canceled":
                                            case "Executed":
                                            case "CostCalculation":
                                                //Восстановление нала
                                                $doubleOrder = self::orderNewCreat(
                                                    $authorizationDouble,
                                                    $identificationId,
                                                    $apiVersion,
                                                    $responseDouble['url'],
                                                    $responseDouble['parameter']
                                                );
                                                //Опрос нала
                                                $lastTimeUpdate = $lastStatusDoubleTime;
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
                                                $lastStatusDoubleTime = time();
                                                if ($no_required_time) {
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            switch ($newStatusBonus) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    break;
                                                                default:
                                                                    $updateTime = 30;
                                                            }
                                                    }
                                                }
                                                //Опрос безнала
                                                $lastTimeUpdate = $lastStatusBonusTime;
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
                                                $lastStatusBonusTime = time();
                                                if ($no_required_time) {
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            switch ($newStatusDouble) {
                                                                case "SearchesForCar":
                                                                case "WaitingCarSearch":
                                                                    $updateTime = 5;
                                                                    break;
                                                                default:
                                                                    $updateTime = 30;
                                                            }
                                                    }
                                                }
                                                break;
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
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
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
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
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "CostCalculation":
                                switch ($newStatusBonus) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Восстановление нала
//                                if ($doubleCancel) {
                                        $doubleOrder = self::orderNewCreat(
                                            $authorizationDouble,
                                            $identificationId,
                                            $apiVersion,
                                            $responseDouble['url'],
                                            $responseDouble['parameter']
                                        );
                                        //Опрос нала
                                        $lastTimeUpdate = $lastStatusDoubleTime;
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
                                        $lastStatusDoubleTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusDouble) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusBonus) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CarFound":
                                    case "Running":
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;
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
                                        $lastStatusBonusTime = time();
                                        if ($no_required_time) {
                                            switch ($newStatusBonus) {
                                                case "SearchesForCar":
                                                case "WaitingCarSearch":
                                                    $updateTime = 5;
                                                    break;
                                                default:
                                                    switch ($newStatusDouble) {
                                                        case "SearchesForCar":
                                                        case "WaitingCarSearch":
                                                            $updateTime = 5;
                                                            break;
                                                        default:
                                                            $updateTime = 30;
                                                    }
                                            }
                                        }
                                        break;
                                    case "CostCalculation":
                                        break;
                                }
                                break;
                        }
                        $lastStatusDouble = $newStatusDouble;
                        Log::debug(" Нал после обработки new Status: " . $lastStatusDouble);
                        $bonusOrder = $uid_history->uid_bonusOrder;
//                        $lastStatusBonus = self::newStatus(
//                            $authorizationBonus,
//                            $identificationId,
//                            $apiVersion,
//                            $responseBonus["url"],
//                            $bonusOrder,
//                            "bonus",
//                            $lastTimeUpdate,
//                            $updateTime,
//                            $uid_history
//                        );
                        Log::debug("bonusOrder  3: $bonusOrder");
                        Log::debug("lastStatusBonus 3: $lastStatusBonus");

                        $doubleOrder = $uid_history->uid_doubleOrder;
//                        $lastStatusDouble = self::newStatus(
//                            $authorizationDouble,
//                            $identificationId,
//                            $apiVersion,
//                            $responseDouble["url"],
//                            $doubleOrder,
//                            "double",
//                            $lastTimeUpdate,
//                            $updateTime,
//                            $uid_history
//                        );

                        Log::debug("doubleOrder  3: $doubleOrder");
                        Log::debug("lastStatusDouble 3: $lastStatusDouble");
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

                        if ($canceledAll) {
                            Log::debug("canceled while ");
                            Log::debug("lastStatusBonus3: " . $lastStatusBonus);
                            Log::debug("lastStatusDouble3: " . $lastStatusDouble);
                            Log::info("doubleOrderRecord 3 $doubleOrderRecord");
                            $doubleOrderRecord->delete();
                            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
                            break;
                        }
                    }
                }
            }
            if (time() - $startTime >= $maxExecutionTime) {
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
//                $uid_history->delete();
                Log::info("doubleOrderRecord orderCanceled $doubleOrderRecord");
                $doubleOrderRecord->delete();
                self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
            }
            return "finish by time is out";
        }
    }

    public function newStatus(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $order,
        $orderType,
        $lastTimeUpdate,
        $updateTime,
        $uid_history
    ) {

        Log::debug("lastTimeUpdate" . $lastTimeUpdate);
//        Log::debug("strtotime lastTimeUpdate", strtotime($lastTimeUpdate));
        Log::debug("Опрос $orderType $order");

        $time_sleep = time() -  $lastTimeUpdate;
        Log::debug("time_sleep" . $time_sleep);

        if ($time_sleep < $updateTime) {
            sleep($updateTime - $time_sleep);
        }
        $newStatusArr = self::getExecutionStatus(
            $authorization,
            $identificationId,
            $apiVersion,
            $url,
            $order
        );
        $newStatus = $newStatusArr["execution_status"];
        Log::debug("function newStatus $orderType: " . $newStatus);
        Log::debug("function newStatus $orderType close_reason: " . $newStatusArr["close_reason"]);
        if ($newStatus == "Canceled") {
            if ($newStatusArr["close_reason"] == "-1") {
                $newStatus = "CarFound";
            }
        }
        Log::debug("function newStatus  после $orderType: " . $newStatus);
        switch ($orderType) {
            case "bonus":
                $uid_history->uid_bonusOrder = $order;
                $uid_history->bonus_status = json_encode($newStatusArr);
                break;
            case "double":
                $uid_history->uid_doubleOrder = $order;
                $uid_history->double_status = json_encode($newStatusArr);
                break;
        }
        $uid_history->save();
        Log::debug("uid_history: $uid_history");

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
        Log::debug("function newStatus $orderType: " . $newStatus);
        return $newStatus;
    }

    /**
     * @param $lastStatusBonus
     * @param $lastStatusDouble
     * @return bool
     * Выход из вилки не по времени маскимальной жизни
     */
    public function canceledFinish(
        $lastStatusBonus,
        $lastStatusDouble,
        $uid_bonusOrderHold,
        $bonusOrder,
        $connectAPI,
        $authorizationBonus,
        $identificationId,
        $apiVersion,
        $doubleOrder,
        $authorizationDouble
    ): bool {
        $uid_history = Uid_history::where("uid_bonusOrderHold", $uid_bonusOrderHold)->first();
        Log::debug("uid_history canceledFinish : $uid_history");

        // Пример вызова функции
        $canceledOneMinute = $this->canceledOneMinute($uid_bonusOrderHold);
        Log::debug("uid_history canceledOneMinute : " . ($canceledOneMinute ? 'true' : 'false'));

        $order = Orderweb::where("dispatching_order_uid", $uid_bonusOrderHold)->first();
        $wfp_order_id = $order->wfp_order_id;

        if ($canceledOneMinute|| $uid_history->cancel || $wfp_order_id == null) { //Выход по 1 минуте или нажатию отмены
            $responseBonusLast =  $uid_history->bonus_status;
            $orderCanceledBonus = false;
            if ($responseBonusLast) {
                $responseBonusLastArr = json_decode($responseBonusLast, true);

                Log::debug("canceledFinish responseBonusLastArr['close_reason']" .
                    $responseBonusLastArr['close_reason']);

                if ($responseBonusLastArr['close_reason'] == -1) {
                    $orderCanceledBonus = self::orderCanceled(
                        $bonusOrder,
                        'bonus',
                        $connectAPI,
                        $authorizationBonus,
                        $identificationId,
                        $apiVersion
                    );
                } else {
                    Log::debug("canceledFinish $orderCanceledBonus");
                    $orderCanceledBonus = true;
                }
                (new DailyTaskController)->sentTaskMessage("Отмена по 1 минуте $bonusOrder");
            }

            $responseDoubleLast =  $uid_history->double_status;
            $orderCanceledDouble = false;
            if ($responseDoubleLast) {
                $responseDoubleLastArr = json_decode($responseDoubleLast, true);

                Log::debug("canceledFinish responseDoubleLastArr['close_reason']" .
                    $responseDoubleLastArr['close_reason']);
                if ($responseDoubleLastArr['close_reason'] == -1) {
                    $orderCanceledDouble = self::orderCanceled(
                        $doubleOrder,
                        "double",
                        $connectAPI,
                        $authorizationDouble,
                        $identificationId,
                        $apiVersion
                    );
                } else {
                    $orderCanceledDouble = true;
                }
            }

            if ($orderCanceledBonus && $orderCanceledDouble || $canceledOneMinute  || $uid_history->cancel) {
                $uid_history->bonus_status = null;
                $uid_history->double_status = null;
                $uid_history->save();

                return true;
            } else {
                return false;
            }
        } else { //Выход по стаутсам опроса
            // проверка нала
            switch ($lastStatusDouble) {
                case "Canceled":
                case "Executed":
                case "CostCalculation":
                    switch ($lastStatusBonus) {
                        case "Canceled":
                        case "Executed":
                        case "CostCalculation":
                            Log::debug("отмена по налу");
                            $uid_history->bonus_status = null;
                            $uid_history->double_status = null;
                            $uid_history->save();
                            return true;
                    }
                    break;
            }
            // проверка безнала
            switch ($lastStatusBonus) {
                case "Canceled":
                case "Executed":
                case "CostCalculation":
                    switch ($lastStatusDouble) {
                        case "Canceled":
                        case "Executed":
                        case "CostCalculation":
                            Log::debug("отмена по безналу");
                            $uid_history->bonus_status = null;
                            $uid_history->double_status = null;
                            $uid_history->save();
                            return true;
                    }
                    break;
            }
        }
        return false;
    }

    private function canceledOneMinute($uid)
    {
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        if (is_null($order)) {
            Log::error("Order not found with UID: $uid");
            return false;
        }

        // Заданное время
        $created_at = $order->created_at;
        Log::debug("canceledOneMinute created_at $created_at");

        // Текущие дата и время
        $current_time = date('Y-m-d H:i:s');

        // Преобразование строковых дат во временные метки
        $created_at_timestamp = strtotime($created_at);
        $current_time_timestamp = strtotime($current_time);

        // Проверка, прошла ли одна минута
        if (($current_time_timestamp - $created_at_timestamp) <= 60) {
            Log::debug("Less than one minute has passed since order creation.");
            return false;
        } else {
            $orderReference = $order->wfp_order_id;
            Log::debug("canceledOneMinute orderReference $orderReference");

            if ($orderReference) {
                switch ($order->comment) {
                    case "taxi_easy_ua_pas1":
                        $application = "PAS1";
                        break;
                    case "taxi_easy_ua_pas2":
                        $application = "PAS2";
                        break;
                    default:
                        $application = "PAS4";
                }

                switch ($order->server) {
                    case "http://167.235.113.231:7307":
                    case "http://167.235.113.231:7306":
                    case "http://134.249.181.173:7208":
                    case "http://91.205.17.153:7208":
                        $city = "Kyiv City";
                        break;
                    case "http://142.132.213.111:8071":
                    case "http://167.235.113.231:7308":
                        $city = "Dnipropetrovsk Oblast";
                        break;
                    case "http://142.132.213.111:8072":
                        $city = "Odessa";
                        break;
                    case "http://3142.132.213.111:8073":
                        $city = "Zaporizhzhia";
                        break;
                    case "http://134.249.181.173:7201":
                    case "http://91.205.17.153:7201":
                        $city = "Cherkasy Oblast";
                        break;
                    default:
                        $city = "OdessaTest";
                }

                Log::debug("canceledOneMinute application: $application, city: $city, orderReference: $orderReference");

                $response = (new WfpController)->checkStatus($application, $city, $orderReference);
                $data = json_decode($response, true);
                Log::debug("canceledOneMinute response data", $data);

                if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
                    if ($data['transactionStatus'] == "Approved"
                        || $data['transactionStatus'] == "WaitingAuthComplete") {
                        Log::debug("Transaction status is Approved or WaitingAuthComplete.");
                        return false;
                    } elseif ($data['transactionStatus'] == "Declined") {
                        Log::debug("Transaction status is Declined");
                        return true;
                    } else {
                        Log::debug("Transaction status is not Approved or WaitingAuthComplete.");
                        return true;
                    }
                } else {
                    Log::debug("Transaction status not found in response.");
                    return false;
                }
            } else {
                Log::error("OrderReference not found for UID: $uid");
                return false;
            }
        }
    }



    public function orderCanceled(
        $order,
        $orderType,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ): bool {
        return self::webordersCancel(
            $order,
            $orderType,
            $connectAPI,
            $authorization,
            $identificationId,
            $apiVersion
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
        $maxExecutionTime = 60; // Максимальное время выполнения - 3 часа

        $startTime = time();
        $result = false;

        do {
            try {
                $response = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId,
                    "X-API-VERSION" => $apiVersion
                ])->post($url, $parameter);


                // Проверяем успешность ответа
                if ($response->successful() && $response->status() == 200) {
                    //проверка статуса после отмены
                    $responseArr = json_decode($response, true);
                    Log::debug(" orderNewCreat responseArr: ", $responseArr);
                    $order = $responseArr["dispatching_order_uid"];
                    Log::debug(" orderNewCreat: " . $url . $order);
                    return $order;
                } else {
                    // Логируем ошибки в случае неудачного запроса
                    Log::error("Request failed with status: " . $response->status());
                    Log::error("Response: " . $response->body());
                    $result = null;
                }
            } catch (\Exception $e) {
                // Обработка исключений
                Log::error("Exception caught: " . $e->getMessage());
                $result = null;
            }
            sleep(5);
        } while (!$result && time() - $startTime < $maxExecutionTime);
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
     */
    public function webordersCancel(
        $uid,
        $orderType,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ): bool {

        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        $startTime = time(); // Начальное время

        $maxExecutionTime = 3 * 60; // Время жизни отмены

        try {
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => $identificationId,
                "X-API-VERSION" => $apiVersion
            ])->put($url);

            // Логируем тело ответа
            Log::debug("webordersCancel postRequestHTTP: " . $response->body());

            // Проверяем успешность ответа
            if ($response->successful()) {
                $responseArr = json_decode($response->body(), true);

                // Обрабатываем успешный ответ
                Log::debug("webordersCancel Request successful.");
                if ($responseArr['order_client_cancel_result'] == 1) {
                    Log::debug("webordersCancel: order_client_cancel_result is 1, exiting.");
                    self::messageAboutTrueCanceled($uid);
                    return true;
                } else {
                    do {
                        Log::debug("webordersCancel: order_client_cancel_result is not 1, checking further.");
                        // Проверка статуса после отмены
                        sleep(5);
                        $urlCheck = $connectAPI . '/api/weborders/' . $uid;
                        try {
                            $response_uid = Http::withHeaders([
                                "Authorization" => $authorization,
                                "X-WO-API-APP-ID" => $identificationId,
                            ])->get($urlCheck);

                            if ($response_uid->successful() && $response_uid->status() == 200) {
                                $response_arr = json_decode($response_uid->body(), true);
                                if ($response_arr['close_reason'] == 1) {
                                    Log::debug("webordersCancel: close_reason is 1, exiting.");
                                    return true;
                                } else {
                                    Log::debug("webordersCancel: close_reason is not 1, continuing.");
                                }
                            } else {
                                // Логируем ошибки в случае неудачного запроса
                                Log::error("webordersCancel Request failed with status: " . $response_uid->status());
                                Log::error("webordersCancel Response: " . $response_uid->body());
                            }
                        } catch (\Exception $e) {
                            // Обработка исключений
                            Log::error("webordersCancel Exception caught: " . $e->getMessage());
                        }
                        sleep(5);
                    } while (time() - $startTime < $maxExecutionTime);
                    // Если мы вышли из цикла из-за превышения времени
                    self::messageAboutFalseCanceled($uid);
                    return false;
                }
            } else {
                // Логируем ошибки в случае неудачного запроса
                Log::error("webordersCancel Request failed with status: " . $response->status());
                Log::error("webordersCancel Response: " . $response->body());
                do {
                    Log::debug("webordersCancel: order_client_cancel_result is not 1, checking further.");
                    // Проверка статуса после отмены
                    sleep(5);
                    $urlCheck = $connectAPI . '/api/weborders/' . $uid;
                    try {
                        $response_uid = Http::withHeaders([
                            "Authorization" => $authorization,
                            "X-WO-API-APP-ID" => $identificationId,
                        ])->get($urlCheck);

                        if ($response_uid->successful() && $response_uid->status() == 200) {
                            $response_arr = json_decode($response_uid->body(), true);
                            if ($response_arr['close_reason'] == 1) {
                                Log::debug("webordersCancel: close_reason is 1, exiting.");
                                return true;
                            } else {
                                Log::debug("webordersCancel: close_reason is not 1, continuing.");
                            }
                        } else {
                            // Логируем ошибки в случае неудачного запроса
                            Log::error("webordersCancel Request failed with status: " . $response_uid->status());
                            Log::error("webordersCancel Response: " . $response_uid->body());
                        }
                    } catch (\Exception $e) {
                        // Обработка исключений
                        Log::error("webordersCancel Exception caught: " . $e->getMessage());
                    }
                    sleep(5);
                } while (time() - $startTime < $maxExecutionTime);
                // Если мы вышли из цикла из-за превышения времени
                self::messageAboutFalseCanceled($uid);
                return false;
            }
        } catch (\Exception $e) {
            // Обработка исключений
            Log::error("webordersCancel Exception caught: " . $e->getMessage());
            do {
                Log::debug("webordersCancel: order_client_cancel_result is not 1, checking further.");
                // Проверка статуса после отмены
                sleep(5);
                $urlCheck = $connectAPI . '/api/weborders/' . $uid;
                try {
                    $response_uid = Http::withHeaders([
                        "Authorization" => $authorization,
                        "X-WO-API-APP-ID" => $identificationId,
                    ])->get($urlCheck);

                    if ($response_uid->successful() && $response_uid->status() == 200) {
                        $response_arr = json_decode($response_uid->body(), true);
                        if ($response_arr['close_reason'] == 1) {
                            Log::debug("webordersCancel: close_reason is 1, exiting.");
                            return true;
                        } else {
                            Log::debug("webordersCancel: close_reason is not 1, continuing.");
                        }
                    } else {
                        // Логируем ошибки в случае неудачного запроса
                        Log::error("webordersCancel Request failed with status: " . $response_uid->status());
                        Log::error("webordersCancel Response: " . $response_uid->body());
                    }
                } catch (\Exception $e) {
                    // Обработка исключений
                    Log::error("webordersCancel Exception caught: " . $e->getMessage());
                }
                sleep(5);
            } while (time() - $startTime < $maxExecutionTime);
            // Если мы вышли из цикла из-за превышения времени
            self::messageAboutFalseCanceled($uid);
            return false;
        }
    }



    public static function messageAboutFalseCanceled($uid)
    {
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($order) {
            $wfp_order_id = "Номер заказа WFP: "  . $order->wfp_order_id;
            $amount = $order->web_cost;
            $connectAPI = "Сервер " . $order->server;
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
            $messageAdmin = "Заказ $uid. Время $localCreatedAt. Ошибка отмены заказа $uid.
             $connectAPI.Маршрут $order->routefrom - $order->routeto. Телефон клиента:
               $order->user_phone. $wfp_order_id. Сумма  $amount грн.";

            $alarmMessage = new TelegramController();

            try {
                $alarmMessage->sendAlarmMessage($messageAdmin);
                $alarmMessage->sendMeMessage($messageAdmin);
            } catch (Exception $e) {
                Log::error("messageAboutFalseCanceled Exception caught: " . $e->getMessage());
            }
            Log::debug("messageAboutFalseCanceled Ошибка проверки статуса заказа: " . $messageAdmin);
        }
    }

    public static function messageAboutTrueCanceled($uid)
    {
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($order) {
            $wfp_order_id = "Номер заказа WFP: "  . $order->wfp_order_id;
            $amount = "Сумма $order->web_cost грн";
            $connectAPI = $order->server;
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
            $messageAdmin = "Отмена заказ $uid. Время $localCreatedAt. Сервер $connectAPI. Маршрут $order->routefrom - $order->routeto. Телефон клиента:  $order->user_phone. $wfp_order_id. $amount";

            $alarmMessage = new TelegramController();

            try {
                $alarmMessage->sendAlarmMessage($messageAdmin);
                $alarmMessage->sendMeMessage($messageAdmin);
            } catch (Exception $e) {
                Log::error("messageAboutFalseCanceled Exception caught: " . $e->getMessage());
            }
            Log::debug("messageAboutFalseCanceled Отмена заказа: " . $messageAdmin);
        }
    }
    public function orderReview($bonusOrder, $doubleOrder, $bonusOrderHold)
    {
        Log::info("orderReview $bonusOrder, $doubleOrder, $bonusOrderHold");
        self::checkAndRestoreDatabaseConnection();

        $order = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();

        Log::info("orderReview");
        if ($order) {
            if ($order->fondy_order_id != null) {
                //Возврат денег по Фонди
                return (new FondyController)->fondyStatusReview($bonusOrder, $doubleOrder, $bonusOrderHold);
            } else {
                if ($order->wfp_order_id != null) {
                    return  (new WfpController)->wfpStatus($bonusOrder, $doubleOrder, $bonusOrderHold);
                } elseif ($order->pay_system == 'bonus_payment') {
                    return   (new BonusBalanceController)->bonusUnBlockedUid($bonusOrder, $doubleOrder, $bonusOrderHold);
                }
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
        $order->payment_type = $params["payment_type"]; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->bonus_status = $params['bonus_status'];
        $order->pay_system = $params["pay_system"]; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
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

//            $user = User::where('email', $email)->first();
//            (new BonusBalanceController)->recordsAdd(0, $user->id, 1, 1);
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

//            (new BonusBalanceController)->recordsAdd(0, $user->id, 1, 1);
            return ["user_name" => $username];
        } else {
            return ["user_name" => "no_name"];
        }
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
                $merchantInfo = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchantInfo = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchantInfo = City_PAS4::where("name", $city)->first();
        }
//dd( $merchantAccount);
        $response = [];
        if ($merchantInfo) {
            $merchant = $merchantInfo->toArray();
            $merchantAccount = $merchant["wfp_merchantAccount"];
            $cards = Card::where('pay_system', $pay_system)
                ->where('user_id', $user->id)
                ->where('merchant', $merchantAccount)
                ->get();
            if ($cards != null) {
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
            }
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
    public function cityNoOnlineMessage($id, $application)
    {
        switch ($application) {
            case "PAS1":
                $serverFalse = City_PAS1::find($id);
                break;
            case "PAS2":
                $serverFalse = City_PAS2::find($id);
                break;
            //case "PAS4":
            default:
                $serverFalse = City_PAS4::find($id);
                break;
        }

        $alarmMessage = new TelegramController();
        $messageAdmin = "Нет подключения к серверу города $serverFalse->name http://" . $serverFalse->address . ".";
        Log::debug("cityNoOnlineMessage $messageAdmin");
        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            $paramsCheck = [
                'subject' => 'Ошибка в телеграмм',
                'message' => $e,
            ];
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };
    }

}
