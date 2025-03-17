<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;

use App\Helpers\OrderHelper;
use App\Helpers\TimeHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\StartAddCostCardCreat;

use App\Jobs\StartNewProcessExecution;

use App\Mail\Check;

use App\Models\BlackList;
use App\Models\Card;
use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\DoubleOrder;
use App\Models\DriverMemoryOrder;
use App\Models\DriverPosition;
use App\Models\ExecStatusHistory;
use App\Models\ExecutionStatus;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Models\User;
use App\Models\WfpInvoice;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Factory;
use phpDocumentor\Reflection\Types\True_;
use Pusher\ApiErrorException;
use Pusher\PusherException;
use SebastianBergmann\Diff\Exception;
use function Symfony\Component\Translation\t;

class UniversalAndroidFunctionController extends Controller
{
    public function sentErrorMessage($message)
    {
        $alarmMessage = new TelegramController();
        Log::debug(" $message");
        try {
            $alarmMessage->sendAlarmMessageLog($message);
            $alarmMessage->sendMeMessageLog($message);
        } catch (Exception $e) {
            Log::error("sentErrorMessage: Ошибка отправки в телеграмм");
        };
    }

    public function postRequestHTTP(
        $url,
        $parameter,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        if (self::containsApiWebordersCost($url)) {
            $secondsToNextHour = TimeHelper::isFifteenSecondsToNextHour();
            if($secondsToNextHour <= 15) {
                sleep($secondsToNextHour + 1);
            };
        }
        $response = null;
        try {
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

            } else {
                // Логируем ошибки в случае неудачного запроса
                Log::error("function postRequestHTTP Request failed with status: " . $response->status());
                Log::error("function postRequestHTTP Response: " . $response->body());

// Check if $parameter is an array and convert it to a JSON string if it is
                if (is_array($parameter)) {
                    $parameter = json_encode($parameter, JSON_UNESCAPED_UNICODE);
                }

                // Check if $response->body() is an array and convert it to a JSON string if it is

                self::sendCatchMessage("(response) Параметр запроса: $parameter / Ответ сервера: " . $response->body());
            }
        } catch (\Exception $e) {
        // Check if $parameter is an array and convert it to a JSON string if it is
            if (is_array($parameter)) {
                $parameter = json_encode($parameter, JSON_UNESCAPED_UNICODE);
            }

            self::sendCatchMessage("(catch) Параметр запроса: $parameter / Ответ сервера: " . $e->getMessage());

            Log::error("параметр запроса $parameter / ответ сервера. " . $e->getMessage());
        }

        return $response;
    }

    function containsApiWebordersCost($url): bool
    {
        // Проверяем, содержит ли $url подстроку '/api/weborders/cost'
        return str_contains($url, '/api/weborders/cost');
    }

    private function sendCatchMessage($message)
    {
        $alarmMessage = new TelegramController();
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $messageAdmin = "Ошибка подключения к серверу $message. IP $client_ip";
        Log::debug("sendCatchMessage $messageAdmin");
        try {
            $alarmMessage->sendAlarmMessageLog($messageAdmin);
            $alarmMessage->sendMeMessageLog($messageAdmin);
        } catch (Exception $e) {
            $paramsCheck = [
                'subject' => 'Ошибка в телеграмм',
                'message' => $e,
            ];
            try {
                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));

            } catch (\Exception $e) {
                Log::error('Mail send failed: ' . $e->getMessage());
                // Дополнительные действия для предотвращения сбоя
            }

        };
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



    public function deleteJobById($orderId)
    {
        try {
            $uid_history = Uid_history::where("orderId", $orderId)->first();
            if (!$uid_history) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись с указанным ID не найдена',
                ], 404);
            }
            $doubleOrderRecord = DoubleOrder::find($orderId);
            if ($doubleOrderRecord) {
                $doubleOrderRecord->delete();
                $messageAdmin = "Запись  $orderId удалена из DoubleOrder";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            }


            $id = $uid_history->jobId;
            // Проверяем, существует ли запись с указанным ID
            $job = DB::table('jobs')->where('id', $id)->first();
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись с указанным ID не найдена',
                ], 404);
            }

            // Удаляем запись
            DB::table('jobs')->where('id', $id)->delete();
            $messageAdmin = "Запись задачи $id удалена из Jobs";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            return response()->json([
                'success' => true,
                'message' => 'Запись успешно удалена',
            ], 200);
        } catch (\Exception $e) {
            // Логируем ошибку

            $messageAdmin = "Ошибка при удалении записи: " . $e->getMessage();
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при удалении записи',
            ], 500);
        }
    }

    /**
     * @throws \Exception
     */
    public function startNewProcessExecutionStatusJob($doubleOrderId, $jobId): ?string
    {
        try {
            $messageAdmin = "!!! 17032025 !!! startNewProcessExecutionStatusJob задача $doubleOrderId / $jobId";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

//            (new PusherController)->sendOrderStartExecution(
//                $doubleOrderId
//            );
            ExecStatusHistory::truncate();
            $doubleOrderRecord = DoubleOrder::find($doubleOrderId);
            if (!$doubleOrderRecord) {
                return "exit";
            }

            $responseBonusStr = $doubleOrderRecord->responseBonusStr;
            $responseDoubleStr = $doubleOrderRecord->responseDoubleStr;
            $authorizationBonus = $doubleOrderRecord->authorizationBonus;
            $authorizationDouble = $doubleOrderRecord->authorizationDouble;
            $connectAPI = $doubleOrderRecord->connectAPI;
            $identificationId = $doubleOrderRecord->identificationId;
            $apiVersion = $doubleOrderRecord->apiVersion;

//        $maxExecutionTime = 3*24*60*60; // Максимальное время выполнения - 3 суток
            $maxExecutionTime = 60*60; // Максимальное время выполнения - 3 суток

            $startTime = time();

            $responseBonus = json_decode($responseBonusStr, true);
            $bonusOrder = $responseBonus['dispatching_order_uid'];
            $bonusOrderHold = $bonusOrder;
            //Увеличеваем максимальное время для отстроченного заказа
            $bonusOrderHold = (new MemoryOrderChangeController)->show($bonusOrderHold);
            $orderwebs = Orderweb::where('dispatching_order_uid', $bonusOrderHold)->first();
            if ($orderwebs->required_time != null) {
                $maxExecutionTime +=  strtotime($orderwebs->required_time);
            }

            $lastTimeUpdate = time();

            $updateTime = 5;

            $responseDouble = json_decode($responseDoubleStr, true);
            $doubleOrder = $responseDouble['dispatching_order_uid'];

            try {
                $uid_history = Uid_history::where("uid_bonusOrderHold", $bonusOrder)->first();

                if ($uid_history == null) {
                    $messageAdmin = "uid_history: не найдено для bonusOrder: $bonusOrder";
                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                    $uid_history = new Uid_history();
                    $uid_history->uid_bonusOrder = $bonusOrder;
                    $uid_history->uid_doubleOrder = $doubleOrder;
                    $uid_history->uid_bonusOrderHold = $bonusOrder;
                    $uid_history->cancel = false;
                    $uid_history->save();
                } else {
                    $messageAdmin = "uid_history: " . print_r($uid_history->toArray(), true);
                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при работе с uid_history для bonusOrder: $bonusOrder: " . $e->getMessage());
                throw $e; // Повторно выбросить исключение для обработки очередью
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

            if($newStatusBonus != null) {
                $lastStatusBonus = $newStatusBonus;
            }


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
            if($newStatusDouble != null) {
                $lastStatusDouble = $newStatusDouble;
            }

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
            $messageAdmin = "lastStatusBonus0: $lastStatusBonus " .
                "lastStatusDouble0:  $lastStatusDouble " .
                "canceledFinish:0 $canceledAll";

            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            if ($canceledAll) {
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
                return "exit";
            } else {
                while (time() - $startTime < $maxExecutionTime) {
                    if (time() <= strtotime($orderwebs->required_time)) {
                        $updateTime = 60;
                        $no_required_time = false;
                    } else {
                        $no_required_time = true;
                    }
                    $bonusOrder = $uid_history->uid_bonusOrder;

                    $doubleOrder = $uid_history->uid_doubleOrder;

                    $messageAdmin =
                        "bonusOrder  1: $bonusOrder  lastStatusBonus: $lastStatusBonus " .
                         "doubleOrder 1: $doubleOrder lastStatusDouble: $lastStatusDouble";
                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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

                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                        $doubleOrderRecord->delete();
    //                    self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                        return "exit";
                    } else {
                        //Безнал ОБРАБОТКА статуса
                        $messageAdmin =
                            " Безнал ОБРАБОТКА статуса " .
                             "bonusOrder  *: $bonusOrder   newStatusBonus: $newStatusBonus " .
                             "doubleOrder *: $doubleOrder  newStatusDouble: $newStatusDouble";
                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);



                        switch ($newStatusBonus) {
                            case "SearchesForCar":
                            case "WaitingCarSearch":
                                switch ($newStatusDouble) {
                                    case "SearchesForCar":
                                    case "WaitingCarSearch":
                                        //Опрос безнала
                                        $lastTimeUpdate = $lastStatusBonusTime;

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
                                    $messageAdmin =
                                        " Опрос нала вход " .
                                             "bonusOrder  *111: $bonusOrder   newStatusBonus: $newStatusBonus " .
                                             "doubleOrder *111: $doubleOrder  newStatusDouble: $newStatusDouble " .
                                             "updateTime $updateTime " ;
                                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                        if($newStatusBonus != null) {
                            $lastStatusBonus = $newStatusBonus;
                        }

                        $bonusOrder = $uid_history->uid_bonusOrder;

                        $doubleOrder = $uid_history->uid_doubleOrder;

                        $messageAdmin =
                            "bonusOrder  2: $bonusOrder   lastStatusBonus: $lastStatusBonus " .
                             "doubleOrder 2: $doubleOrder  lastStatusDouble: $lastStatusDouble";

                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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

                            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                            $doubleOrderRecord->delete();
    //                        self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                            return "exit";
                        } else {
                            //Нал ОБРАБОТКА статуса

                            $messageAdmin =
                                " Нал ОБРАБОТКА статуса " .
                             "bonusOrder  *: $bonusOrder   newStatusBonus: $newStatusBonus " .
                             "doubleOrder *: $doubleOrder  newStatusDouble: $newStatusDouble";
                            (new MessageSentController)->sentMessageAdminLog($messageAdmin);


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
                            if($newStatusDouble != null) {
                                $lastStatusDouble = $newStatusDouble;
                            }

                            $bonusOrder = $uid_history->uid_bonusOrder;

                            $messageAdmin =
                                "bonusOrder  3: $bonusOrder  lastStatusBonus: $lastStatusBonus " .
                                 "doubleOrder 3: $doubleOrder lastStatusDouble: $lastStatusDouble";

                            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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

                                (new MessageSentController)->sentMessageAdminLog($messageAdmin);


                                $doubleOrderRecord->delete();
    //                            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                                return "exit";
                            }
                        }
                    }
                }
                if (time() - $startTime >= $maxExecutionTime) {
                    try {
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

                            (new MessageSentController)->sentMessageAdminLog($messageAdmin);


                            $doubleOrderRecord->delete();
        //                            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);

                            return "exit";
                        }

                        $messageAdmin = "doubleOrderRecord orderCanceled $doubleOrderRecord";

                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                        Log::info("Превышено время выполнения для doubleOrderId: $doubleOrderId, перенос задания");
                        StartNewProcessExecution::dispatch($doubleOrderId)->delay(now()->addMinutes(5));
                        $doubleOrderRecord->delete();
                        return "exit";
                    } catch (\Exception $e) {
                        Log::error("Ошибка в цикле для doubleOrderId: $doubleOrderId: " . $e->getMessage());
                        throw $e; // Повторно выбросить для отметки задания как неудачного
                    }
                    sleep(5);

                }
                return "exit";
            }
        } catch (\Exception $e) {
            Log::error("Критическая ошибка в startNewProcessExecutionStatusJob для doubleOrderId: $doubleOrderId: " . $e->getMessage());
            throw $e;
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


        $messageAdmin = "function newStatus вход updateTime: $updateTime";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $newStatusArr = self::getExecutionStatus(
            $authorization,
            $identificationId,
            $apiVersion,
            $url,
            $order,
            $updateTime
        );
         if ($newStatusArr["execution_status"] != null) {
             $newStatus = $newStatusArr["execution_status"];
             $messageAdmin = "function newStatus выход $orderType: " . $newStatus;

             (new MessageSentController)->sentMessageAdminLog($messageAdmin);

             if ($newStatus == "Canceled") {
                 if ($newStatusArr["close_reason"] == "-1") {
                     $newStatus = "CarFound";
                 }
             }


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
             (new OrderStatusController)->getOrderStatusMessageResultPush($uid_history->uid_bonusOrderHold);

             return $newStatus;
         } else {

             $messageAdmin = "function newStatus выход $orderType newStatus null: " ;
             (new MessageSentController)->sentMessageAdminLog($messageAdmin);
             return null;
         }


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


        // Пример вызова функции

        $canceledOneMinute = $this->canceledOneMinute($uid_bonusOrderHold);


        $messageAdmin = "Canceled uid_history->cancel $uid_history->cancel";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        if ($canceledOneMinute|| $uid_history->cancel == "1") { //Выход по 1 минуте или нажатию отмены
            if ($lastStatusBonus !== "Canceled") {
                $orderCanceledBonus = self::orderCanceledReturn(
                    $bonusOrder,
                    'bonus',
                    $connectAPI,
                    $authorizationBonus,
                    $identificationId,
                    $apiVersion
                );
            } else {
                $orderCanceledBonus = true;
            }
            if ($lastStatusDouble !== "Canceled") {

                $orderCanceledDouble = self::orderCanceledReturn(
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

            if ($orderCanceledBonus && $orderCanceledDouble) {


                $messageAdmin = "Отмена вилки
                     безнал: $bonusOrder
                     дубль $doubleOrder";

                (new MessageSentController)->sentMessageAdmin($messageAdmin);


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



                            $messageAdmin = "Выход из вилки Отмена по налу
                                безнал: $bonusOrder
                                статус б/н: $lastStatusBonus
                                нал $doubleOrder
                                статус нал: $lastStatusDouble";
                                (new MessageSentController)->sentMessageAdmin($messageAdmin);

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



                            $messageAdmin = "Выход из вилки Отмена по безналу
                                безнал: $bonusOrder
                                статус б/н: $lastStatusBonus
                                нал $doubleOrder
                                статус нал: $lastStatusDouble";
                                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                        return true;
                    }
                    break;
            }
        }
        return false;
    }

    private function canceledOneMinute($uid)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        if (is_null($order)) {
            Log::error("Order not found with UID: $uid");
            return false;
        }

        // Заданное время
        $created_at = $order->created_at;
//        $messageAdmin = "canceledOneMinute created_at $created_at";
//        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        // Текущие дата и время
        $current_time = date('Y-m-d H:i:s');

        // Преобразование строковых дат во временные метки
        $created_at_timestamp = strtotime($created_at);
        $current_time_timestamp = strtotime($current_time);

        // Проверка, прошла ли одна минута
        if (($current_time_timestamp - $created_at_timestamp) <= 50) {

//            $messageAdmin = "Less than one minute has passed since order creation.";
//            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $merchantInfo = (new WfpController)->checkMerchantInfo($order);
            if($merchantInfo["merchantAccount"] == "errorMerchantAccount") {
                $order->transactionStatus = "errorMerchantAccount";


                $messageAdmin = "Мерчанта нет";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                return true;
            } else {
                return false;
            }

        } else {
            $orderReference = $order->wfp_order_id;
            if($orderReference != null) {


//                $messageAdmin = "canceledOneMinute orderReference $orderReference";
//                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                $invoice = WfpInvoice::where("orderReference", $orderReference) ->first();
                if ($invoice->transactionStatus == null || $invoice->transactionStatus != "WaitingAuthComplete") {
                    return true;
                } else {
                     return false;
                }
            } else {
                return true;
            }

        }
    }

    /**
     * @throws \Exception
     */
    public function cancelOnlyCardPayUid($uid)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        $messageAdmin = "Запущено ожидание оплаты для заказа $uid (cancelOnlyCardPayUid)";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        if (is_null($order)) {
            Log::error("cancelOnlyDoubleUid Order not found with UID: $uid");
            return false;
        }

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
            case "http://142.132.213.111:8073":
                $city = "Zaporizhzhia";
                break;
            case "http://134.249.181.173:7201":
            case "http://91.205.17.153:7201":
                $city = "Cherkasy Oblast";
                break;
            default:
                $city = "OdessaTest";
        }

        $merchantInfo = (new WfpController)->checkMerchantInfo($order);


        if($merchantInfo["merchantAccount"] == "errorMerchantAccount") {
            Log::debug("Мерчанта нет");


            (new AndroidTestOSMController)->webordersCancel($uid, $city, $application);
            return "exit cancelOnlyCardPayUid Мерчанта нет";
        } else {
            sleep(60);
            $cancelNeed60 =self::canceledOneMinute($uid);

            if ($cancelNeed60) {
                (new AndroidTestOSMController)->webordersCancel($uid, $city, $application);
                return "exit cancelOnlyCardPayUid нет оплаты";
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
    )
    {
        self::webordersCancel(
            $order,
            $orderType,
            $connectAPI,
            $authorization,
            $identificationId,
            $apiVersion
        );
    }


    public function orderCanceledReturn(
        $order,
        $orderType,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ): bool {
        return self::webordersCancelReturn(
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

//    public function getExecutionStatus(
//        $authorization,
//        $identificationId,
//        $apiVersion,
//        $url,
//        $dispatching_order_uid
//    ) {
//        // Здесь реализуйте код для получения статуса execution_status по UID
//        // Верните фактический статус для последующей проверки
//
//        $url = $url . "/" . $dispatching_order_uid;
//
//        $response = Http::withHeaders([
//            "Authorization" => $authorization,
//            "X-WO-API-APP-ID" => $identificationId,
//            "X-API-VERSION" => $apiVersion
//        ])->get($url);
//        $responseArr = json_decode($response, true);
////        Log::debug("$url" . "execution_status: " . $responseArr["execution_status"] . " close_reason: " . $responseArr["close_reason"]);
//
//        return $responseArr;
//    }

    public function getExecutionStatus(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $dispatching_order_uid,
        $updateTime
    ) {
        $messageAdmin = "getExecutionStatus";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        $url = $url . "/" . $dispatching_order_uid;

        $messageAdmin = "getExecutionStatus dispatching_order_uid" . $dispatching_order_uid . " url " . $url;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        sleep((int) $updateTime);

        try {
            // Устанавливаем таймаут на запрос (например, 20 секунд)
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => $identificationId,
                "X-API-VERSION" => $apiVersion
            ])
                ->timeout(10) // Таймаут на запрос (в секундах)
                ->retry(3, $updateTime) // Задержка перед повтором в случае ошибки
                ->get($url);

            $messageAdmin = "getExecutionStatus response: status" . $response->status() . " Ответ: " . $response->body();
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            if ($response->failed()) {
                $messageAdmin = "Ошибка при получении статуса execution_status URL: $url Статус: " . $response->status() . "\nОтвет: " . $response->body();
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                return [
                    'success' => false,
                    'message' => 'API request failed',
                    'status_code' => $response->status()
                ];
            }

            $responseArr = json_decode($response, true);

            if (!isset($responseArr["execution_status"])) {
                $messageAdmin = "execution_status отсутствует в ответе URL: $url Ответ: " . json_encode($responseArr);
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                return [
                    'success' => false,
                    'message' => 'execution_status not found in response'
                ];
            }

            $messageAdmin = "Успешное получение execution_status URL: $url execution_status: " . print_r($responseArr["execution_status"], true) . " close_reason: " . ($responseArr["close_reason"] ?? 'null');
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return $responseArr;
        } catch (Illuminate\Http\Client\ConnectionException $e) {
            // Логирование ошибки в случае таймаута
            $messageAdmin = "Таймаут при запросе getExecutionStatus Ошибка: " . $e->getMessage() . " URL: " . $url;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            return [
                'success' => false,
                'message' => 'Server did not respond within 20 seconds'
            ];
        } catch (Exception $e) {
            // Логирование всех остальных ошибок
            $messageAdmin = "Исключение в getExecutionStatus Ошибка: " . $e->getMessage() . " URL: " . $url;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            return [
                'success' => false,
                'message' => 'An error occurred while fetching execution status'
            ];
        }
    }


    public function getStatus(
        $header,
        $url
    ) {
        $updateTime = 5;
        $messageAdmin = "getStatus "  . " url " . $url;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);


        try {
            // Устанавливаем таймаут на запрос (например, 20 секунд)
            $response = Http::withHeaders($header)
                ->timeout($updateTime) // Таймаут на запрос (в секундах)
                ->retry(3, $updateTime) // Задержка перед повтором в случае ошибки
                ->get($url);

            $messageAdmin = "getStatus response: status" . $response->status() . " Ответ: " . $response->body();
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            if ($response->failed()) {
                $messageAdmin = "Ошибка при получении статуса execution_status URL: $url Статус: " . $response->status() . "\nОтвет: " . $response->body();
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                return [
                    'success' => false,
                    'message' => 'API request failed',
                    'status_code' => $response->status()
                ];
            }

            $responseArr = json_decode($response, true);

            if (!isset($responseArr["execution_status"])) {
                $messageAdmin = "execution_status отсутствует в ответе URL: $url Ответ: " . json_encode($responseArr);
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                return [
                    'success' => false,
                    'message' => 'execution_status not found in response'
                ];
            }

            $messageAdmin = "Успешное получение execution_status URL: $url execution_status: " . print_r($responseArr["execution_status"], true) . " close_reason: " . ($responseArr["close_reason"] ?? 'null');
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return $responseArr;
        } catch (Illuminate\Http\Client\ConnectionException $e) {
            // Логирование ошибки в случае таймаута
            $messageAdmin = "Таймаут при запросе getExecutionStatus Ошибка: " . $e->getMessage() . " URL: " . $url;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            return [
                'success' => false,
                'message' => 'Server did not respond within 20 seconds'
            ];
        } catch (Exception $e) {
            // Логирование всех остальных ошибок
            $messageAdmin = "Исключение в getExecutionStatus Ошибка: " . $e->getMessage() . " URL: " . $url;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            return [
                'success' => false,
                'message' => 'An error occurred while fetching execution status'
            ];
        }
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
    )  {

        $startTime = time(); // Начальное время

        $maxExecutionTime = 2 * 60; // Время жизни отмены

        try {

            do {
                $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                $response = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId,
                    "X-API-VERSION" => $apiVersion
                ])->put($url);

                // Логируем тело ответа
                Log::debug("webordersCancel postRequestHTTP: " . $response->body());

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
                            break;
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
            self::messageAboutFalseCanceled($uid);
        } catch (\Exception $e) {
            // Обработка исключений
            Log::error("webordersCancel Exception caught: " . $e->getMessage());
            do {
                $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                $response = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId,
                    "X-API-VERSION" => $apiVersion
                ])->put($url);

                // Логируем тело ответа
                Log::debug("webordersCancel postRequestHTTP: " . $response->body());

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
                            break;
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
        }
    } /**
     * Запрос отмены заказа
     */
    public function webordersCancelReturn(
        $uid,
        $orderType,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ): bool {


        $startTime = time(); // Начальное время

        $maxExecutionTime = 2 * 60; // Время жизни отмены

        try {

            do {
                $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                $response = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId,
                    "X-API-VERSION" => $apiVersion
                ])->put($url);

                // Логируем тело ответа
                Log::debug("webordersCancel postRequestHTTP: " . $response->body());

                // Проверяем успешность ответа
                if ($response->successful()) {
                    // Обрабатываем успешный ответ
                    Log::debug("webordersCancel Request successful.");
                } else {
                    // Логируем ошибки в случае неудачного запроса
                    Log::error("webordersCancel Request failed with status: " . $response->status());
                    Log::error("webordersCancel Response: " . $response->body());
                    // Если мы вышли из цикла из-за превышения времени
                }



                Log::debug("webordersCancel: order_client_cancel_result is not 1, checking further.");
                // Проверка статуса после отмены
                sleep(5);
                $urlCheck = $connectAPI . '/api/weborders/' . $uid;
                try {
                    $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                    $response = Http::withHeaders([
                        "Authorization" => $authorization,
                        "X-WO-API-APP-ID" => $identificationId,
                        "X-API-VERSION" => $apiVersion
                    ])->put($url);

                    // Логируем тело ответа
                    Log::debug("webordersCancel postRequestHTTP: " . $response->body());

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
            self::messageAboutFalseCanceled($uid);
            return false;
        } catch (\Exception $e) {
            // Обработка исключений
            Log::error("webordersCancel Exception caught: " . $e->getMessage());
            do {
                Log::debug("webordersCancel: order_client_cancel_result is not 1, checking further.");
                // Проверка статуса после отмены
                sleep(5);
                $urlCheck = $connectAPI . '/api/weborders/' . $uid;
                try {
                    $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                    $response = Http::withHeaders([
                        "Authorization" => $authorization,
                        "X-WO-API-APP-ID" => $identificationId,
                        "X-API-VERSION" => $apiVersion
                    ])->put($url);

                    // Логируем тело ответа
                    Log::debug("webordersCancel postRequestHTTP: " . $response->body());


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
        $uid = (new MemoryOrderChangeController)->show($uid);
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($order) {
            $wfp_order_id = "Номер заказа WFP: "  . $order->wfp_order_id;
            $amount = $order->web_cost;
            $connectAPI = "Сервер " . $order->server;
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
            $messageAdmin = "Заказ $uid. Время $localCreatedAt. Ошибка отмены заказа $uid.
            $connectAPI.Маршрут $order->routefrom - $order->routeto. Телефон клиента:
            $order->user_phone. $wfp_order_id. Сумма  $amount грн.";


            try {
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            } catch (Exception $e) {
                Log::error("messageAboutFalseCanceled Exception caught: " . $e->getMessage());
            }
            Log::debug("messageAboutFalseCanceled Ошибка проверки статуса заказа: " . $messageAdmin);
        }
    }

    public static function messageAboutTrueCanceled($uid)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($order) {

            $amount = "Сумма $order->web_cost грн";
            $connectAPI = $order->server;
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
            $messageAdmin = "Отмена заказ $uid. Время $localCreatedAt. Сервер $connectAPI. Маршрут $order->routefrom - $order->routeto. Телефон клиента:  $order->user_phone. Сумма $amount";



            try {
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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

        $messageAdmin = "function orderReview запущена для  $bonusOrder, $doubleOrder, $bonusOrderHold ";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

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
        $order->comment_info = $params["comment_info"]; //
        $order->extra_charge_codes = $params["extra_charge_codes"]; //
        $order->taxiColumnId = $params["taxiColumnId"]; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = $params["payment_type"]; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->bonus_status = $params['bonus_status'];
        $order->pay_system = $params["pay_system"]; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->web_cost = $params['order_cost'];
        $order->dispatching_order_uid = $params['dispatching_order_uid'];
        $order->closeReason = $params['closeReason'];
        $order->closeReasonI = 1;
        $order->city = (new UniversalAndroidFunctionController)->findCity(
            (float) $params["startLat"],
            (float) $params["startLan"]
        );

        $order->server = $params['server'];

        $order->save();

        $order_id = $order->id;

        $order->city = (new UniversalAndroidFunctionController)->findCity($order->startLat, $order->startLan);
        if (isset($params['user_phone'], $params['email'], $params['comment_info'])
            && strpos($params['comment_info'], 'цифра номера') === false
        ) {
            $user = User::where("email", $params["email"])->first();

            if ($user) { // Проверка, что пользователь найден
                $user->user_phone = $params["user_phone"];
                $user->save();
            }
        }



        if ($params["payment_type"] != 1 && !$params["route_undefined"]) {
            (new FCMController)->writeDocumentToFirestore($params['dispatching_order_uid']);
        }

//        if ($params["payment_type"] == 1) {
//            StartStatusPaymentReview::dispatch($params['dispatching_order_uid']);
//        }

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

        $pay_type = " Оплата наличными. ";
        if($params["payment_type"] == 1) {
            $pay_type = " Оплата картой (возможно бонусами).";
        }


        if ($params["route_undefined"] != "1") {
            $order = "Нове замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " до "  . $params['to'] . " " . $params['to_number'] .
                ". Вартість поїздки становитиме: " . $params['order_cost'] . "грн. $pay_type Номер замовлення: " .
                $params['dispatching_order_uid'] .
                ", сервер " . $params['server'];
            ;
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " по місту. Вартість поїздки становитиме: " . $params['order_cost'] . "грн. $pay_type Номер замовлення: " .
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
            try {
                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
                Mail::to('cartaxi4@gmail.com')->send(new Check($paramsCheck));
            } catch (\Exception $e) {
                Log::error('Mail send failed: ' . $e->getMessage());
                // Дополнительные действия для предотвращения сбоя
            }

        };

        try {
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            Mail::to('cartaxi4@gmail.com')->send(new Check($paramsCheck));
        } catch (\Exception $e) {
            Log::error('Mail send failed: ' . $e->getMessage());
            // Дополнительные действия для предотвращения сбоя
        }
        return $order_id;
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

    public function addUserNoNameApp($email, $app)
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

            switch ($app) {
                case "PAS1":
                    $newUser->app_pas_1 = 1;
                    break;
                case "PAS2":
                    $newUser->app_pas_2 = 1;
                    break;
                default:
                    $newUser->app_pas_4 = 1;
            }

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

    public function addUserNoNameWithEmailAndPhoneApp($email, $phone, $app)
    {
        $newUser = User::whereRaw('BINARY email = ?', [$email])->first();
        if ($newUser == null) {
            $newUser = new User();

            $newUser->name = "user_";
            $newUser->email = $email;
            $newUser->user_phone = $phone;
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

            switch ($app) {
                case "PAS1":
                    $newUser->app_pas_1 = 1;
                    break;
                case "PAS2":
                    $newUser->app_pas_2 = 1;
                    break;
                default:
                    $newUser->app_pas_4 = 1;
            }


            $newUser->save();

            $user = User::where('email', $email)->first();
            $username = "user_" . $newUser->id;
            $user->name = $username;
            $user->save();
        }
    }

    public function verifyBlackListUser($email, $androidDom)
    {
        // Логируем начало проверки черного списка
        \Log::info("Проверка черного списка для email: $email", ['androidDom' => $androidDom]);

        (new IPController)->getIP("/android/$androidDom/startPage");

        $response_error["order_cost"] = 0;

        // Проверяем наличие email в черном списке
        $blackList = BlackList::where('email', $email)->first();
        if ($blackList == null) {
            $response_error["Message"] = "Не в черном списке";

            // Логируем, что email не найден в черном списке
            \Log::info("Email $email не найден в черном списке.");
        } else {
            $response_error["Message"] = "В черном списке";

            // Логируем, что email найден в черном списке
            \Log::warning("Email $email найден в черном списке.");
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

        if ($LatLng["lat"] == 0) {
            $locationService = new OpenStreetMapHelper();

// Название места для поиска
            $placeName = $to . " " . $to_number;

// Вызов метода для получения координат
            $coordinates = $locationService->getCoordinatesByPlaceName($placeName);

            if ($coordinates !== null) {
                $LatLng["lat"] = $coordinates['latitude'];
                $LatLng["lng"] = $coordinates['longitude'];
            } else {
                Log::error("Координаты для {$placeName} не найдены.");
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
        if ($LatLng["lat"] == 0) {
            $locationService = new OpenStreetMapHelper();

// Название места для поиска
            $placeName = $to;

// Вызов метода для получения координат
            $coordinates = $locationService->getCoordinatesByPlaceName($placeName);

            if ($coordinates !== null) {
                $LatLng["lat"] = $coordinates['latitude'];
                $LatLng["lng"] = $coordinates['longitude'];
            } else {
                Log::error("Координаты для {$placeName} не найдены.");
            }

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
//        $messageAdmin = "authorizationApp $cityString, $connectAPI, $app";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);

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
                break;
        }
//        $messageAdmin = "authorizationApp $city";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        if ($city) {
            $username = $city->login;
            $password = hash('SHA512', $city->password);
        } else {
            // Обработка случая, когда город не найден
            // Например, можно задать значение по умолчанию или вывести сообщение об ошибке
            $username = '0936734488'; // или любое другое значение
            // echo 'Город не найден';
            $password = hash('SHA512', "22223344");
        }

        Log::debug("connectAPI $connectAPI");
        Log::debug("username $username");
        Log::debug("password $password");
//        $messageAdmin = "authorizationApp  $connectAPI $username $password";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);
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
        if (isset($city)) {
            return $city->toArray()['versionApi'];
        } else {
            return "1.52.1";
        }
    }

    public function orderIdMemory($order_id, $uid, $pay_system)
    {
        Log::debug("orderIdMemory $uid");
        Log::debug("orderIdMemory $uid");
        Log::debug("orderIdMemory $pay_system");

        $uid = (new MemoryOrderChangeController)->show($uid);
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        switch ($pay_system) {
            case "wfp_payment":
                $orderweb->wfp_order_id = $order_id;
                self::wfpInvoice(
                    $order_id,
                    $orderweb->web_cost,
                    $uid);

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


    public function orderIdMemoryToken($orderReference, $order_id, $pay_system)
    {
        Log::debug("orderIdMemory: orderReference = $orderReference");
        Log::debug("orderIdMemory: order_id = $order_id");
        Log::debug("orderIdMemory: pay_system = $pay_system");

        $orderweb = Orderweb::find($order_id);

        if (!$orderweb) {
            Log::error("orderIdMemoryToken: Orderweb not found for order_id = $order_id");
            return;
        }

        $uid = $orderweb->dispatching_order_uid ?? null;

        switch ($pay_system) {
            case "wfp_payment":
                $orderweb->wfp_order_id = $orderReference;
                if ($uid !== null) {
                    self::wfpInvoice($orderReference, $orderweb->web_cost, $uid);
                } else {
                    Log::warning("orderIdMemoryToken: dispatching_order_uid is null for order_id = $order_id");
                }
                break;

            case "fondy_payment":
                $orderweb->fondy_order_id = $orderReference;
                break;

            case "mono_payment":
                $orderweb->mono_order_id = $orderReference; // Исправил на orderReference
                break;
        }

        $orderweb->pay_system = $pay_system;
        $orderweb->save();
    }



    public function wfpInvoice(
        $order_id,
        $amount,
        $uid
    ) {
        $uid = (new MemoryOrderChangeController)->show($uid);
        $wfp_invoice = new WfpInvoice();
        $wfp_invoice->dispatching_order_uid = $uid;
        $wfp_invoice->orderReference = $order_id;
        $wfp_invoice->amount = $amount;
        $wfp_invoice->save();

        Log::debug("wfpInvoice dispatching_order_uid");
        Log::debug("wfpInvoice $order_id");
        Log::debug("wfpInvoice $amount");
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
        $cityApp,
        $email,
        $pay_system
    ): \Illuminate\Http\JsonResponse {
        $user = User::where('email', $email)->first();
//        dd($application);


//        switch ($cityApp) {
//            case "PAS1":
//                 $city = "";
//                break;
//            case "PAS2":
//                $merchantInfo = City_PAS2::where("name", $cityApp)->first();
//                break;
//            default:
//                $merchantInfo = City_PAS4::where("name", $cityApp)->first();
//        }


        switch ($application) {
            case "PAS1":
                $merchantInfo = City_PAS1::where("name", $cityApp)->first();
                break;
            case "PAS2":
                $merchantInfo = City_PAS2::where("name", $cityApp)->first();
                break;
            default:
                $merchantInfo = City_PAS4::where("name", $cityApp)->first();
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
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $messageAdmin = "Нет подключения к серверу города $serverFalse->name http://" . $serverFalse->address
            . ". Приложение $application. IP $client_ip";
        Log::debug("cityNoOnlineMessage $messageAdmin");
        try {
            $alarmMessage->sendAlarmMessageLog($messageAdmin);
            $alarmMessage->sendMeMessageLog($messageAdmin);
        } catch (Exception $e) {
            $paramsCheck = [
                'subject' => 'Ошибка в телеграмм',
                'message' => $e,
            ];
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };
    }
    public function findCity($startLat, $startLan)
    {
        Log::debug("findCity $startLat, $startLan");
        $cities = [
            'city_kiev' => [
                'lat_min' => 49.8000,  // Минимальная широта для области
                'lat_max' => 51.5000,  // Максимальная широта для области
                'lan_min' => 29.9000,  // Минимальная долгота для области
                'lan_max' => 31.7000,  // Максимальная долгота для области
            ],
            'city_cherkassy' => [
                'lat_min' => 48.5000,  // Минимальная широта для области
                'lat_max' => 49.7000,  // Максимальная широта для области
                'lan_min' => 31.5000,  // Минимальная долгота для области
                'lan_max' => 32.5000,  // Максимальная долгота для области
            ],
            'city_odessa' => [
                'lat_min' => 45.5000,  // Минимальная широта для области
                'lat_max' => 47.0000,  // Максимальная широта для области
                'lan_min' => 29.5000,  // Минимальная долгота для области
                'lan_max' => 31.5000,  // Максимальная долгота для области
            ],
            'city_zaporizhzhia' => [
                'lat_min' => 47.4000,  // Минимальная широта для области
                'lat_max' => 48.2000,  // Максимальная широта для области
                'lan_min' => 34.5000,  // Минимальная долгота для области
                'lan_max' => 36.0000,  // Максимальная долгота для области
            ],
            'city_dnipro' => [
                'lat_min' => 47.8000,  // Минимальная широта для области
                'lat_max' => 49.1000,  // Максимальная широта для области
                'lan_min' => 33.8000,  // Минимальная долгота для области
                'lan_max' => 35.5000,  // Максимальная долгота для области
            ],
            'city_lviv' => [
                'lat_min' => 48.9000,
                'lat_max' => 50.6000,
                'lan_min' => 22.0000,
                'lan_max' => 25.5000,
            ],
            'city_ivano_frankivsk' => [
                'lat_min' => 47.5000,
                'lat_max' => 49.2000,
                'lan_min' => 23.8000,
                'lan_max' => 25.6000,
            ],
            'city_vinnytsia' => [
                'lat_min' => 48.2000,
                'lat_max' => 49.9000,
                'lan_min' => 27.3000,
                'lan_max' => 29.4000,
            ],
            'city_poltava' => [
                'lat_min' => 48.5000,
                'lat_max' => 50.6000,
                'lan_min' => 32.4000,
                'lan_max' => 35.0000,
            ],
            'city_sumy' => [
                'lat_min' => 50.1000,
                'lat_max' => 52.3000,
                'lan_min' => 33.0000,
                'lan_max' => 35.3000,
            ],
            'city_kharkiv' => [
                'lat_min' => 48.9000,
                'lat_max' => 50.6000,
                'lan_min' => 35.0000,
                'lan_max' => 37.3000,
            ],
            'city_chernihiv' => [
                'lat_min' => 50.4000,
                'lat_max' => 52.5000,
                'lan_min' => 30.4000,
                'lan_max' => 33.2000,
            ],
            'city_rivne' => [
                'lat_min' => 49.6000,
                'lat_max' => 51.0000,
                'lan_min' => 25.5000,
                'lan_max' => 27.4000,
            ],
            'city_ternopil' => [
                'lat_min' => 48.8000,
                'lat_max' => 50.2000,
                'lan_min' => 24.0000,
                'lan_max' => 26.2000,
            ],
            'city_khmelnytskyi' => [
                'lat_min' => 48.8000,
                'lat_max' => 50.3000,
                'lan_min' => 25.9000,
                'lan_max' => 28.0000,
            ],
            'city_zakarpattya' => [
                'lat_min' => 47.5000,
                'lat_max' => 49.0000,
                'lan_min' => 22.1000,
                'lan_max' => 24.4000,
            ],
            'city_zhytomyr' => [
                'lat_min' => 49.7000,
                'lat_max' => 51.5000,
                'lan_min' => 27.6000,
                'lan_max' => 29.4000,
            ],
            'city_kropyvnytskyi' => [
                'lat_min' => 47.2000,
                'lat_max' => 49.0000,
                'lan_min' => 30.4000,
                'lan_max' => 33.0000,
            ],
            'city_mykolaiv' => [
                'lat_min' => 46.0000,
                'lat_max' => 48.1000,
                'lan_min' => 30.0000,
                'lan_max' => 32.5000,
            ],
            'city_chernivtsi' => [
                'lat_min' => 47.9000,
                'lat_max' => 48.8000,
                'lan_min' => 25.6000,
                'lan_max' => 27.3000,
            ],
            'city_lutsk' => [
                'lat_min' => 50.5900,  // Минимальная широта для области
                'lat_max' => 51.8000,  // Максимальная широта для области
                'lan_min' => 23.5000,  // Минимальная долгота для области
                'lan_max' => 26.9000,  // Максимальная долгота для области
            ],
        ];



        foreach ($cities as $city => $coords) {
            if ($startLat >= $coords['lat_min'] && $startLat <= $coords['lat_max'] &&
                $startLan >= $coords['lan_min'] && $startLan <= $coords['lan_max']) {
                return $city; // Возвращаем имя города, если точка в пределах его границ
            }
        }

        return "all"; // Если город не найден
    }

    public function findCityJson($startLat, $startLan)
    {
        Log::debug("findCity $startLat, $startLan");
        $cities = [
            'city_kiev' => [
                'lat_min' => 49.8000,  // Минимальная широта для области
                'lat_max' => 51.5000,  // Максимальная широта для области
                'lan_min' => 29.9000,  // Минимальная долгота для области
                'lan_max' => 31.7000,  // Максимальная долгота для области
            ],
            'city_cherkassy' => [
                'lat_min' => 48.5000,  // Минимальная широта для области
                'lat_max' => 49.7000,  // Максимальная широта для области
                'lan_min' => 31.5000,  // Минимальная долгота для области
                'lan_max' => 32.5000,  // Максимальная долгота для области
            ],
            'city_odessa' => [
                'lat_min' => 45.5000,  // Минимальная широта для области
                'lat_max' => 47.0000,  // Максимальная широта для области
                'lan_min' => 29.5000,  // Минимальная долгота для области
                'lan_max' => 31.5000,  // Максимальная долгота для области
            ],
            'city_zaporizhzhia' => [
                'lat_min' => 47.4000,  // Минимальная широта для области
                'lat_max' => 48.2000,  // Максимальная широта для области
                'lan_min' => 34.5000,  // Минимальная долгота для области
                'lan_max' => 36.0000,  // Максимальная долгота для области
            ],
            'city_dnipro' => [
                'lat_min' => 47.8000,  // Минимальная широта для области
                'lat_max' => 49.1000,  // Максимальная широта для области
                'lan_min' => 33.8000,  // Минимальная долгота для области
                'lan_max' => 35.5000,  // Максимальная долгота для области
            ],
            'city_lviv' => [
                'lat_min' => 48.9000,
                'lat_max' => 50.6000,
                'lan_min' => 22.0000,
                'lan_max' => 25.5000,
            ],
            'city_ivano_frankivsk' => [
                'lat_min' => 47.5000,
                'lat_max' => 49.2000,
                'lan_min' => 23.8000,
                'lan_max' => 25.6000,
            ],
            'city_vinnytsia' => [
                'lat_min' => 48.2000,
                'lat_max' => 49.9000,
                'lan_min' => 27.3000,
                'lan_max' => 29.4000,
            ],
            'city_poltava' => [
                'lat_min' => 48.5000,
                'lat_max' => 50.6000,
                'lan_min' => 32.4000,
                'lan_max' => 35.0000,
            ],
            'city_sumy' => [
                'lat_min' => 50.1000,
                'lat_max' => 52.3000,
                'lan_min' => 33.0000,
                'lan_max' => 35.3000,
            ],
            'city_kharkiv' => [
                'lat_min' => 48.9000,
                'lat_max' => 50.6000,
                'lan_min' => 35.0000,
                'lan_max' => 37.3000,
            ],
            'city_chernihiv' => [
                'lat_min' => 50.4000,
                'lat_max' => 52.5000,
                'lan_min' => 30.4000,
                'lan_max' => 33.2000,
            ],
            'city_rivne' => [
                'lat_min' => 49.6000,
                'lat_max' => 51.0000,
                'lan_min' => 25.5000,
                'lan_max' => 27.4000,
            ],
            'city_ternopil' => [
                'lat_min' => 48.8000,
                'lat_max' => 50.2000,
                'lan_min' => 24.0000,
                'lan_max' => 26.2000,
            ],
            'city_khmelnytskyi' => [
                'lat_min' => 48.8000,
                'lat_max' => 50.3000,
                'lan_min' => 25.9000,
                'lan_max' => 28.0000,
            ],
            'city_zakarpattya' => [
                'lat_min' => 47.5000,
                'lat_max' => 49.0000,
                'lan_min' => 22.1000,
                'lan_max' => 24.4000,
            ],
            'city_zhytomyr' => [
                'lat_min' => 49.7000,
                'lat_max' => 51.5000,
                'lan_min' => 27.6000,
                'lan_max' => 29.4000,
            ],
            'city_kropyvnytskyi' => [
                'lat_min' => 47.2000,
                'lat_max' => 49.0000,
                'lan_min' => 30.4000,
                'lan_max' => 33.0000,
            ],
            'city_mykolaiv' => [
                'lat_min' => 46.0000,
                'lat_max' => 48.1000,
                'lan_min' => 30.0000,
                'lan_max' => 32.5000,
            ],
            'city_chernivtsi' => [
                'lat_min' => 47.9000,
                'lat_max' => 48.8000,
                'lan_min' => 25.6000,
                'lan_max' => 27.3000,
            ],
            'city_lutsk' => [
                'lat_min' => 50.5900,  // Минимальная широта для области
                'lat_max' => 51.8000,  // Максимальная широта для области
                'lan_min' => 23.5000,  // Минимальная долгота для области
                'lan_max' => 26.9000,  // Максимальная долгота для области
            ],
        ];



        foreach ($cities as $city => $coords) {
            if ($startLat >= $coords['lat_min'] && $startLat <= $coords['lat_max'] &&
                $startLan >= $coords['lan_min'] && $startLan <= $coords['lan_max']) {
                return response()->json(['city' => $city]); // Используем Response в Laravel
            }
        }

        return response()->json(['city' => "all"]); // Если город не найден

    }
    /**
     * @throws \Exception
     */
    public function calculateTimeToStart($orderweb, $response_arr)
    {

        $currentDateTime = Carbon::now(); // Получаем текущее время
        $kievTimeZone = new DateTimeZone('Europe/Kiev'); // Создаем объект временной зоны для Киева
        $dateTime = new DateTime($currentDateTime->format('Y-m-d H:i:s')); // Создаем объект DateTime
        $dateTime->setTimezone($kievTimeZone); // Устанавливаем временную зону на Киев

        $driver_latitude = $response_arr['lat'];
        $driver_longitude = $response_arr['lng'];

        $start_point_latitude = $orderweb->startLat;
        $start_point_longitude = $orderweb->startLan;

        $osrmHelper = new OpenStreetMapHelper();
        $driverDistance = round(
            $osrmHelper->getRouteDistance(
                (float) $driver_latitude,
                (float) $driver_longitude,
                (float) $start_point_latitude,
                (float) $start_point_longitude
            ) / 1000,
            2 // Округляем до 2 знаков после запятой
        );
        Log::info("driverDistance" . $driverDistance);
        // Скорость водителя (60 км/ч)
        $speed = 60;
        // Расчет времени в минутах
        $minutesToAdd = round(($driverDistance / $speed) * 60, 0); // Время в минутах
        if ($minutesToAdd < 1) {
            $minutesToAdd = 1;
        }
        // Устанавливаем время прибытия
        $dateTime->modify("+{$minutesToAdd} minutes");
        $orderweb->time_to_start_point = $dateTime->format('Y-m-d H:i:s'); // Сохраняем время в нужном формате
        $orderweb->save();

        Log::info("orderweb->time_to_start_point" . $orderweb->time_to_start_point);
        Log::info("Document successfully written!");
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostUpdate($uid, $typeAdd)
    {
        Log::info("Метод startAddCostUpdate вызван с UID: " . $uid);

        // Получаем UID из MemoryOrderChangeController
        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);

        // Ищем заказ
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        Log::debug("Найден order с UID: " . ($order ? $order->dispatching_order_uid : 'null'));

        // Ищем данные из памяти о заказе
        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден orderMemory с UID: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null'));

        // Проверяем существование заказа
        if (!$order || !$orderMemory) {
            Log::error("Не удалось найти order или orderMemory с UID: " . $uid);
            return null;
        }
//        $newOrder = $order->replicate();
//        $newOrder = $order;
        // Сохраняем копию в базе данных


        $city = $order->city;
        Log::info("Город заказа: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано: " . $application);

        // Переписываем город для определенных случаев
        $originalCity = $city;
        switch ($originalCity) {
            case "city_kiev":
                $city = "Kyiv City";
                break;
            case "city_cherkassy":
                $city = "Cherkasy Oblast";
                break;
            case "city_odessa":
                $city = "Odessa";
                break;
            case "city_zaporizhzhia":
                $city = "Zaporizhzhia";
                break;
            case "city_dnipro":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "city_lviv":
                $city = "Lviv";
                break;
            case "city_ivano_frankivsk":
                $city = "Ivano_frankivsk";
                break;
            case "city_vinnytsia":
                $city = "Vinnytsia";
                break;
            case "city_poltava":
                $city = "Poltava";
                break;
            case "city_sumy":
                $city = "Sumy";
                break;
            case "city_kharkiv":
                $city = "Kharkiv";
                break;
            case "city_chernihiv":
                $city = "Chernihiv";
                break;
            case "city_rivne":
                $city = "Rivne";
                break;
            case "city_ternopil":
                $city = "Ternopil";
                break;
            case "city_khmelnytskyi":
                $city = "Khmelnytskyi";
                break;
            case "city_zakarpattya":
                $city = "Zakarpattya";
                break;
            case "city_zhytomyr":
                $city = "Zhytomyr";
                break;
            case "city_kropyvnytskyi":
                $city = "Kropyvnytskyi";
                break;
            case "city_mykolaiv":
                $city = "Mykolaiv";
                break;
            case "city_chernivtsi":
                $city = "Сhernivtsi";
                break;
            case "city_lutsk":
                $city = "Lutsk";
                break;
            default:
                $city = "all";
        }

        Log::info("Город изменен с {$originalCity} на {$city}");

        // Вызываем отмену заказа в AndroidTestOSMController
//        (new AndroidTestOSMController)->webordersCancel($uid, $city, $application);


        $authorization = $orderMemory->authorization;
        $identificationId = $orderMemory->identificationId;
        $apiVersion = $orderMemory->apiVersion;
        $url = $orderMemory->connectAPI;
        $parameter = json_decode($orderMemory->response, true);

        if ($typeAdd == 20) {
            if ($order->attempt_20 != null) {
                $parameter['add_cost'] = (int) $order->add_cost + (int) $typeAdd * ((int) $order->attempt_20 + 1);
            } else {
                $parameter['add_cost'] = (int) $order->add_cost + (int)$typeAdd;
            }
        }
        if ($typeAdd == 60) {
            $parameter['add_cost'] = (int) $order->add_cost + (int)$typeAdd;
        }


        $messageAdmin = "Параметры запроса нового заказа" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        Log::info("Параметры API запроса: URL - {$url}, API Version - {$apiVersion}, ID - {$identificationId}");

        try {
            Log::info("Отправка POST-запроса с параметрами: " . json_encode($parameter, JSON_UNESCAPED_UNICODE));
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => $identificationId,
                "X-API-VERSION" => $apiVersion
            ])->post($url, $parameter);

            if ($response->successful() && $response->status() == 200) {
                // Вызываем отмену заказа в AndroidTestOSMController
                (new AndroidTestOSMController)->webordersCancel($uid, $city, $application);
                Log::info("Успешный ответ API с кодом 200");

                $responseArr = $response->json();
                Log::debug("Ответ от API: " . json_encode($responseArr));

                $orderNew = $responseArr["dispatching_order_uid"];
                Log::debug("Создан новый заказ с UID: " . $orderNew);
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                $order_old_uid = $order->dispatching_order_uid;
                $order_new_uid = $orderNew;

                (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);

                $orderMemory->dispatching_order_uid = $order_new_uid;
                $orderMemory->save();

                $order->dispatching_order_uid = $order_new_uid;
                $order->auto = null;

                $order->web_cost = $responseArr["order_cost"];

                if ($typeAdd == 20) {
                    $order->attempt_20 = (int)$order->attempt_20 + 1;
                }

                $order->closeReason = "-1";
                $order->closeReasonI = "0";
                $order->save();

                Log::info("Обновлен order с новым UID: " . $order_new_uid);

                if ($order->pay_system == "nal_payment" && $order->route_undefined == "0") {
                    (new FCMController)->writeDocumentToFirestore($order_new_uid);
                }

                Log::info("Запись в Firestore выполнена для UID: " . $order_new_uid);

                (new MessageSentController())->sentCarRestoreOrderAfterAddCost($order);
                Log::info("Сообщение о восстановлении машины отправлено.");


                return response()->json([
                    "response" => "200"
                ], 200);
            } else {
                Log::error("Неудачный запрос: статус " . $response->status());
                Log::error("Ответ от API: " . $response->body());
                return response()->json([
                    "response" => "400"
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error("Поймано исключение: 212 " . $e->getMessage());
            return response()->json([
                "response" => "401"
            ], 200);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostWithAddBottomUpdate($uid, $addCost)
    {
        Log::info("Метод startAddCostUpdate вызван с UID: " . $uid);


        // Получаем UID из MemoryOrderChangeController
        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);

        // Ищем заказ
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден order с UID: " . ($order ? $order->dispatching_order_uid : 'null'));


        // Ищем данные из памяти о заказе
        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден orderMemory с UID: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null'));

        // Проверяем существование заказа
        if (!$order || !$orderMemory) {
            Log::error("Не удалось найти order или orderMemory с UID: " . $uid);
            return null;
        }

        $email = $order->email;


        $city = $order->city;
        Log::info("Город заказа: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано: " . $application);

        // Переписываем город для определенных случаев
        $originalCity = $city;
        switch ($originalCity) {
            case "city_kiev":
                $city = "Kyiv City";
                break;
            case "city_cherkassy":
                $city = "Cherkasy Oblast";
                break;
            case "city_odessa":
                $city = "Odessa";
                break;
            case "city_zaporizhzhia":
                $city = "Zaporizhzhia";
                break;
            case "city_dnipro":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "city_lviv":
                $city = "Lviv";
                break;
            case "city_ivano_frankivsk":
                $city = "Ivano_frankivsk";
                break;
            case "city_vinnytsia":
                $city = "Vinnytsia";
                break;
            case "city_poltava":
                $city = "Poltava";
                break;
            case "city_sumy":
                $city = "Sumy";
                break;
            case "city_kharkiv":
                $city = "Kharkiv";
                break;
            case "city_chernihiv":
                $city = "Chernihiv";
                break;
            case "city_rivne":
                $city = "Rivne";
                break;
            case "city_ternopil":
                $city = "Ternopil";
                break;
            case "city_khmelnytskyi":
                $city = "Khmelnytskyi";
                break;
            case "city_zakarpattya":
                $city = "Zakarpattya";
                break;
            case "city_zhytomyr":
                $city = "Zhytomyr";
                break;
            case "city_kropyvnytskyi":
                $city = "Kropyvnytskyi";
                break;
            case "city_mykolaiv":
                $city = "Mykolaiv";
                break;
            case "city_chernivtsi":
                $city = "Сhernivtsi";
                break;
            case "city_lutsk":
                $city = "Lutsk";
                break;
            default:
                $city = "all";
        }

        Log::info("Город изменен с {$originalCity} на {$city}");

        $authorization = $orderMemory->authorization;
        $identificationId = $orderMemory->identificationId;
        $apiVersion = $orderMemory->apiVersion;
        $url = $orderMemory->connectAPI;
        $parameter = json_decode($orderMemory->response, true);

        $messageAdmin = "Параметры проверки стоимости" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $addCostBalance = OrderHelper::calculateCostBalanceAfterHourChange(
            $url,
            $parameter,
            $authorization,
            $identificationId,
            $apiVersion,
            $addCost,
            $order
        );


        $parameter['add_cost'] = (int) $order->attempt_20 + (int) $order->add_cost + (int)$addCost + $addCostBalance;
        $messageAdmin = "Параметры запроса нового заказа" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        Log::info("Параметры API запроса: URL - {$url}, API Version - {$apiVersion}, ID - {$identificationId}");

//        try {
            Log::info("Отправка POST-запроса с параметрами: " . json_encode($parameter, JSON_UNESCAPED_UNICODE));
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => $identificationId,
                "X-API-VERSION" => $apiVersion
            ])->post($url, $parameter);

            if ($response->successful() && $response->status() == 200) {
                // Вызываем отмену заказа в AndroidTestOSMController
                Log::info("Успешный ответ API с кодом 200");
                $responseArr = $response->json();

                if(isset($responseArr["Message"])) {
                    $Message = $responseArr["Message"];
                    return response()->json([
                        "response" => $Message
                    ], 200);
                }


                $orderNew = $responseArr["dispatching_order_uid"];

                (new PusherController)->sentUidAppEmailPayType(
                    $orderNew,
                    $application,
                    $email,
                    "nal_payment"
                );


                (new AndroidTestOSMController)->webordersCancelAddCostNal($uid, $city, $application);

                Log::debug("Ответ от API: " . json_encode($responseArr));
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                (new MessageSentController)->sentMessageAdmin($messageAdmin);



                Log::debug("Создан новый заказ с UID: " . $orderNew);

                $order_old_uid = $order->dispatching_order_uid;
                $order_new_uid = $orderNew;

                (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);
                $orderMemory->dispatching_order_uid = $order_new_uid;
                $orderMemory->save();

                $order->dispatching_order_uid = $order_new_uid;
                $order->auto = null;
                $order->web_cost = $responseArr["order_cost"];
                $order->closeReason = "-1";
                $order->closeReasonI = "0";
                $order->attempt_20 += $addCost;
                $order->save();

                Log::info("Обновлен order с новым UID: " . $order_new_uid);

                if ($order->pay_system == "nal_payment" && $order->route_undefined == "0") {
                    (new FCMController)->writeDocumentToFirestore($order_new_uid);
                }

                Log::info("Запись в Firestore выполнена для UID: " . $order_new_uid);

                (new MessageSentController())->sentCarRestoreOrderAfterAddCost($order);
                Log::info("Сообщение о восстановлении машины отправлено.");


                return response()->json([
                    "response" => "200"
                ], 200);
            } else {

                Log::error("Неудачный запрос: статус " . $response->status());
                Log::error("Ответ от API: " . $response->body());
                (new MessageSentController)->sentMessageAdmin("Неудачный запрос: статус " . $response->status());
                (new MessageSentController)->sentMessageAdmin("Ответ от API: " . $response->body());
                $responseArr = $response->json();

                $Message = $responseArr["Message"];
                return response()->json([
                    "response" => $Message
                ], 200);
            }
//        } catch (\Exception $e) {
//            Log::error("Поймано исключение: 212 " . $e->getMessage());
//            (new MessageSentController)->sentMessageAdmin("Поймано исключение: 212 " . $e->getMessage());
//            return response()->json([
//                "response" => "401"
//            ], 200);
//        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostBottomUpdate($uid, $addCost)
    {
        Log::info("Метод startAddCostUpdate вызван с UID: " . $uid);

        // Получаем UID из MemoryOrderChangeController

        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);

        // Ищем заказ
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        $email = $order->email;
        Log::debug("Найден order с UID: " . ($order ? $order->dispatching_order_uid : 'null'));

        // Ищем данные из памяти о заказе
        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден orderMemory с UID: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null'));

        // Проверяем существование заказа
        if (!$order || !$orderMemory) {
            Log::error("Не удалось найти order или orderMemory с UID: " . $uid);
            return null;
        }

        $city = $order->city;
        Log::info("Город заказа: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано: " . $application);

        // Переписываем город для определенных случаев
        $originalCity = $city;
        switch ($originalCity) {
            case "city_kiev":
                $city = "Kyiv City";
                break;
            case "city_cherkassy":
                $city = "Cherkasy Oblast";
                break;
            case "city_odessa":
                $city = "Odessa";
                break;
            case "city_zaporizhzhia":
                $city = "Zaporizhzhia";
                break;
            case "city_dnipro":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "city_lviv":
                $city = "Lviv";
                break;
            case "city_ivano_frankivsk":
                $city = "Ivano_frankivsk";
                break;
            case "city_vinnytsia":
                $city = "Vinnytsia";
                break;
            case "city_poltava":
                $city = "Poltava";
                break;
            case "city_sumy":
                $city = "Sumy";
                break;
            case "city_kharkiv":
                $city = "Kharkiv";
                break;
            case "city_chernihiv":
                $city = "Chernihiv";
                break;
            case "city_rivne":
                $city = "Rivne";
                break;
            case "city_ternopil":
                $city = "Ternopil";
                break;
            case "city_khmelnytskyi":
                $city = "Khmelnytskyi";
                break;
            case "city_zakarpattya":
                $city = "Zakarpattya";
                break;
            case "city_zhytomyr":
                $city = "Zhytomyr";
                break;
            case "city_kropyvnytskyi":
                $city = "Kropyvnytskyi";
                break;
            case "city_mykolaiv":
                $city = "Mykolaiv";
                break;
            case "city_chernivtsi":
                $city = "Сhernivtsi";
                break;
            case "city_lutsk":
                $city = "Lutsk";
                break;
            default:
                $city = "all";
        }

        Log::info("Город изменен с {$originalCity} на {$city}");



        $authorization = $orderMemory->authorization;
        $identificationId = $orderMemory->identificationId;
        $apiVersion = $orderMemory->apiVersion;
        $url = $orderMemory->connectAPI;
        $parameter = json_decode($orderMemory->response, true);
        $messageAdmin = "Параметры проверки стоимости" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        $addCostBalance = OrderHelper::calculateCostBalanceAfterHourChange(
            $url,
            $parameter,
            $authorization,
            $identificationId,
            $apiVersion,
            $addCost,
            $order
        );

        $parameter['add_cost'] = (int) $order->attempt_20 + (int) $order->add_cost + (int)$addCost + $addCostBalance;
//        $parameter['add_cost'] = (int) $order->attempt_20 + (int)$addCost+ $addCostBalance;

        $messageAdmin = "Параметры запроса нового заказа" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        Log::info("Параметры API запроса: URL - {$url}, API Version - {$apiVersion}, ID - {$identificationId}");

        try {
            Log::info("Отправка POST-запроса с параметрами: " . json_encode($parameter, JSON_UNESCAPED_UNICODE));
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => $identificationId,
                "X-API-VERSION" => $apiVersion
            ])->post($url, $parameter);

            if ($response->successful() && $response->status() == 200) {
                // Вызываем отмену заказа в AndroidTestOSMController
                (new AndroidTestOSMController)->webordersCancel($uid, $city, $application);
                Log::info("Успешный ответ API с кодом 200");

                $responseArr = $response->json();
                $orderNew = $responseArr["dispatching_order_uid"];


                (new PusherController)->sentUidAppEmailPayType(
                    $orderNew,
                    $application,
                    $email,
                    "nal_payment"
                );

                Log::debug("Ответ от API: " . json_encode($responseArr));

//                $newOrder = $order->replicate();
//                $newOrder = $order;
                $orderNew = $responseArr["dispatching_order_uid"];
                Log::debug("Создан новый заказ с UID: " . $orderNew);
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                $order_old_uid = $order->dispatching_order_uid;
                $order_new_uid = $orderNew;

                (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);
                $orderMemory->dispatching_order_uid = $order_new_uid;
                $orderMemory->save();


                $order->dispatching_order_uid = $order_new_uid;
                $order->auto = null;
                $order->web_cost = $responseArr["order_cost"];
                $order->closeReason = "-1";
                $order->closeReasonI = "0";
                $order->attempt_20 += (int)$addCost;
                $order->save();

                Log::info("Обновлен order с новым UID: " . $order_new_uid);

                if ($order->pay_system == "nal_payment" && $order->route_undefined == "0") {
                    (new FCMController)->writeDocumentToFirestore($order_new_uid);
                }

                Log::info("Запись в Firestore выполнена для UID: " . $order_new_uid);

                (new MessageSentController())->sentCarRestoreOrderAfterAddCost($order);
                Log::info("Сообщение о восстановлении машины отправлено.");


                return response()->json([
                    "response" => "200"
                ], 200);
            } else {
                Log::error("Неудачный запрос: статус " . $response->status());
                Log::error("Ответ от API: " . $response->body());
                return response()->json([
                    "response" => "400"
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error("Поймано исключение: 212 " . $e->getMessage());
            return response()->json([
                "response" => "401"
            ], 200);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostCardUpdate(
        $uid,
        $uid_Double,
        $pay_method,
        $orderReference,
        $city
    ): ?\Illuminate\Http\JsonResponse {
        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);

        // Ищем заказ
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        Log::debug("Найден order с UID: " . ($order ? $order->dispatching_order_uid : 'null'));

        // Ищем данные из памяти о заказе

        // Проверяем существование заказа
        if (!$order) {
            Log::error("Не удалось найти order или orderMemory с UID: " . $uid);
            return null;
        }

        Log::info("Город заказа: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано: " . $application);

        // Переписываем город для определенных случаев


        $startTime = time(); // Время начала выполнения скрипта
        $maxDuration = 60; // 60 секундах


        while (true) { // Бесконечный цикл
            // Отправка POST-запроса к API

            $response = (new WfpController)->checkStatus(
                $application,
                $city,
                $orderReference
            );

            if ($response != "error") {
                $data = json_decode($response, true);
                if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
                    $transactionStatus = $data['transactionStatus'];
                    if ($transactionStatus != "Approved" ||
                        $transactionStatus != "WaitingAuthComplete") {
                        $messageAdmin = "Доплата по счету $orderReference на сумму 20 $transactionStatus ";
                        (new MessageSentController)->sentMessageAdmin($messageAdmin);

                        StartAddCostCardCreat::dispatch(
                            $uid,
                            $uid_Double,
                            $pay_method,
                            $orderReference,
                            $application,
                            $city,
                            $transactionStatus
                        );
                        return response()->json([
                            "response" => "200"
                        ], 200);
                    }
                }
            }
            sleep(10);
            if (time() - $startTime > $maxDuration) {
                Log::debug("refund Превышен лимит времени. Прекращение попыток.");
                return response()->json([
                    "response" => "400"
                ], 200);
            }
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostCardBottomUpdate(
        $uid,
        $uid_Double,
        $pay_method,
        $orderReference,
        $city,
        $addCost
    )  {

        self::startAddCostCardBottomCreat(
            $uid,
            $uid_Double,
            $pay_method,
            $orderReference,
            $city,
            $addCost
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostCardCreat(
        $uid,
        $uid_Double,
        $pay_method,
        $orderReference,
        $transactionStatus,
        $city
    ): ?\Illuminate\Http\JsonResponse
    {
        Log::info("Метод startAddCostCardCreat вызван с UID: " . $uid);
        $messageAdmin = "Метод startAddCostCardCreat вызван с UID: " . $uid;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);


        $order_first = Orderweb::where("dispatching_order_uid", $uid)->first();
        // Получаем UID из MemoryOrderChangeController
        $uid = (new MemoryOrderChangeController)->show($uid);

        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);
        $messageAdmin = "MemoryOrderChangeController возвращает UID: " . $uid;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        // Ищем заказ
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

        if ($uid_history) {
            $uid = $uid_history->uid_bonusOrder;
            $uid_Double = $uid_history->uid_doubleOrder;
            Log::debug("uid_history startAddCostCardCreat :", $uid_history->toArray());
        }


        Log::debug("Найден order с UID: " . ($order ? $order->dispatching_order_uid : 'null'));

        // Ищем данные из памяти о заказе
        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден orderMemory с UID: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null'));

        // Проверяем существование заказа
        if (!$order || !$orderMemory) {
            Log::error("Не удалось найти order или orderMemory с UID: " . $uid);
            return null;
        }

        Log::info("Город заказа: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано: " . $application);

        // Выбор приложения по комментарию

        $connectAPI = (new AndroidTestOSMController)->connectAPIAppOrder($city, $application);
        $authorizationChoiceArr = (new AndroidTestOSMController)->authorizationChoiceApp($pay_method, $city, $connectAPI, $application);

        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];

        $identificationId = $orderMemory->identificationId;
        $apiVersion = $orderMemory->apiVersion;
        $url = $orderMemory->connectAPI;
        $parameter = json_decode($orderMemory->response, true);

        $parameter['add_cost'] = (int) $order->web_cost - (int) $order_first->web_cost + (int) $order->add_cost + 20;

        $messageAdmin = "Параметры запроса нового заказа" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        Log::info("Параметры API запроса: URL - {$url}, API Version - {$apiVersion}, ID - {$identificationId}");

//        try {
            Log::info("Отправка POST-запроса с параметрами: " . json_encode($parameter, JSON_UNESCAPED_UNICODE));
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
            Log::debug("responseBonusArr: startAddCostCardUpdate ", $responseBonusArr);

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
            Log::debug("responseDoubleArr: startAddCostCardUpdate ", $responseDoubleArr);

            //Сообщение что нет обоих заказаов безнального и дубля
            if ($responseBonusArr != null
                && isset($responseBonusArr["Message"])
                && $responseDoubleArr != null
                && isset($responseDoubleArr["Message"])
            ) {
                $messageAdmin = "startAddCostCardUpdate: новые заказы по вилке для доплаты не создались";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                return null;

            }
            if ($responseBonusArr == null
                || isset($responseBonusArr["Message"])
                && $responseDoubleArr != null
                && !isset($responseDoubleArr["Message"])
            ) {
                $responseFinal = $responseDouble;
                $messageAdmin = "startAddCostCardUpdate: безнал при +20 не создался";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
            }
            if (!isset($responseDoubleArr["Message"])) {
                $responseDoubleArr["url"] = $url;
                $responseDoubleArr["parameter"] = $parameter;
            } else {
                $messageAdmin = "startAddCostCardUpdate: дубль при +20 не создался";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                $responseDoubleArr = null;
            }




            if ($responseFinal->successful() && $responseFinal->status() == 200) {
                // Вызываем отмену заказа в AndroidTestOSMController
                Log::info("Успешный ответ API с кодом 200");
                (new AndroidTestOSMController)->webordersCancelDouble(
                    $uid,
                    $uid_Double,
                    $pay_method,
                    $city,
                    $application
                );

//                $newOrder = $order->replicate();
//                $newOrder = $order;


                $responseArr = $responseFinal->json();
                Log::debug("Ответ от API: " . json_encode($responseArr));

                $orderNew = $responseArr["dispatching_order_uid"];
                Log::debug("Создан новый заказ с UID: " . $orderNew);
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                $order_old_uid = $order->dispatching_order_uid;
                $order_new_uid = $orderNew;

                (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);

                $orderMemory->dispatching_order_uid = $order_new_uid;
                $orderMemory->save();

                $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $order_old_uid)-> get();
                if($wfpInvoices != null) {
                    foreach ($wfpInvoices as $value) {
                        $wfpInvoice = WfpInvoice::where("dispatching_order_uid", $value->dispatching_order_uid)->first();
                        $wfpInvoice->dispatching_order_uid = $order_new_uid;
                        $wfpInvoice->save();
                    }
                }


                $order->dispatching_order_uid = $order_new_uid;
                $order->auto = null;
                $order->wfp_order_id = $orderReference;
                $order->web_cost = $responseArr["order_cost"];
                $order->attempt_20 = (int)$order->attempt_20 + 1;
                $order->closeReason = "-1";
                $order->closeReasonI = "0";
                $order->save();

                Log::info("Обновлен order с новым UID: " . $order_new_uid);
//Запуск вилки
                if ($responseBonusArr != null
                    && $responseDoubleArr != null
                    && isset($responseBonusArr["dispatching_order_uid"])
                    && isset($responseDoubleArr["dispatching_order_uid"])
                ) {
                    Log::debug("responseDoubleArr: 44444444 ", $responseDoubleArr);
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

                    $uid_history = new Uid_history();
                    $uid_history->uid_bonusOrder = $responseBonusArr["dispatching_order_uid"];
                    $uid_history->uid_doubleOrder = $responseDoubleArr["dispatching_order_uid"];
                    $uid_history->uid_bonusOrderHold = $responseBonusArr["dispatching_order_uid"];
                    $uid_history->cancel = false;
                    $uid_history->orderId = $doubleOrder->id;
                    $uid_history->save();

                    Log::info("doubleOrder->id" . $doubleOrder->id);
                    Log::debug("StartNewProcessExecution " . $doubleOrder->id);
                    Log::debug("response_arr22222:" . json_encode($doubleOrder->toArray()));

                    $messageAdmin = "StartNewProcessExecution (startAddCostCardUpdate): " . json_encode($doubleOrder->toArray());
                    (new MessageSentController)->sentMessageAdmin($messageAdmin);

                    StartNewProcessExecution::dispatch($doubleOrder->id);
                    return null;
                }


                return null;
            } else {
                Log::error("Неудачный запрос: статус " . $response->status());
                Log::error("Ответ от API: " . $response->body());
                return null;
            }

    }
    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function startAddCostCardBottomCreat(
        $uid,
        $uid_Double,
        $pay_method,
        $orderReference,
        $city,
        $addCost
    ): ?\Illuminate\Http\JsonResponse
    {
        Log::info("Метод startAddCostCardBottomCreat вызван с UID  : " . $uid);


//        // Получаем UID из MemoryOrderChangeController

        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("MemoryOrderChangeController startAddCostCardBottomCreat возвращает UID:  " . $uid);

        // Ищем заказ

        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

        if (!$uid_history) {
            $uid_Double = $uid;
        } else {
            $uid_Double = $uid_history-> uid_doubleOrder;
        }
        $email = $order->email;


        Log::debug("Найден order с UID startAddCostCardBottomCreat : " . ($order ? $order->dispatching_order_uid : 'null'));
        $messageAdmin = "Найден order с UID startAddCostCardBottomCreat: " . ($order ? $order->dispatching_order_uid : 'null');
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        // Ищем данные из памяти о заказе

        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден orderMemory с UID startAddCostCardBottomCreat: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null'));
        $messageAdmin = "Найден orderMemory с UID startAddCostCardBottomCreat: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null');
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        // Проверяем существование заказа
        if (!$order || !$orderMemory) {
            Log::error("Не удалось найти order или orderMemory с UID: " . $uid);
            return response()->json([
                "response" => "400"
            ], 200);
        }

        Log::info("c startAddCostCardBottomCreat: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано startAddCostCardBottomCreat: " . $application);

        // Выбор приложения по комментарию

        $connectAPI = (new AndroidTestOSMController)->connectAPIAppOrder($city, $application);
        $authorizationChoiceArr = (new AndroidTestOSMController)->authorizationChoiceApp($pay_method, $city, $connectAPI, $application);

        $authorization = $authorizationChoiceArr["authorization"];
        $authorizationBonus = $authorizationChoiceArr["authorizationBonus"];
        $authorizationDouble = $authorizationChoiceArr["authorizationDouble"];
        $payment_type = $authorizationChoiceArr["payment_type"];

        $identificationId = $orderMemory->identificationId;
        $apiVersion = $orderMemory->apiVersion;
        $url = $orderMemory->connectAPI;
        $parameter = json_decode($orderMemory->response, true);

        $messageAdmin = "Параметры проверки стоимости" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $addCostBalance = OrderHelper::calculateCostBalanceAfterHourChange(
            $url,
            $parameter,
            $authorization,
            $identificationId,
            $apiVersion,
            $addCost,
            $order
        );


        $parameter['add_cost'] = (int) $order->attempt_20 + (int) $order->add_cost + (int) $addCost + $addCostBalance;

//        $parameter['add_cost'] = (int) $order->attempt_20 + (int)$addCost+ $addCostBalance;

        $messageAdmin = "Параметры запроса нового заказа" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);



        Log::info("Параметры API запроса startAddCostCardBottomCreat: URL - {$url}, API Version - {$apiVersion}, ID - {$identificationId}");

//        try {
            Log::info("Отправка POST-запроса с параметрами startAddCostCardBottomCreat: " . json_encode($parameter, JSON_UNESCAPED_UNICODE));
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



            Log::debug("responseBonusArr: startAddCostCardBottomCreat ", $responseBonusArr);

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

            Log::debug("responseDoubleArr: startAddCostCardBottomCreat ", $responseDoubleArr);

            //Сообщение что нет обоих заказаов безнального и дубля
            if ($responseBonusArr != null
                && isset($responseBonusArr["Message"])
                && $responseDoubleArr != null
                && isset($responseDoubleArr["Message"])
            ) {
                $messageAdmin = "startAddCostCardBottomCreat: новые заказы по вилке для доплаты не создались";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                return response()->json([
                    "response" => "Новый заказ не создался"
                ], 200);

            }
            if ($responseBonusArr == null
                || isset($responseBonusArr["Message"])
                && $responseDoubleArr != null
                && !isset($responseDoubleArr["Message"])
            ) {
                $responseFinal = $responseDouble;
                $messageAdmin = "startAddCostCardBottomCreat: безнал при +20 не создался";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

            }
            if (!isset($responseDoubleArr["Message"])) {
                $responseDoubleArr["url"] = $url;
                $responseDoubleArr["parameter"] = $parameter;
            } else {
                $messageAdmin = "startAddCostCardBottomCreat: дубль при +20 не создался";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                $responseDoubleArr = null;
            }


            if ($responseFinal->successful() && $responseFinal->status() == 200) {
                // Вызываем отмену заказа в AndroidTestOSMController






                Log::info("Успешный ответ API с кодом 200 startAddCostCardBottomCreat");

                $responseArr = $responseFinal->json();
                if(isset($responseArr["Message"])) {
                    $Message = $responseArr["Message"];
                    return response()->json([
                        "response" => $Message
                    ], 200);
                }
                $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
                $uid_history->cancel = true;
                $uid_history->save();

                $controller = new AndroidTestOSMController();
                $controller->webordersCancelDouble(
                    $uid,
                    $uid_Double,
                    $payment_type,
                    $city,
                    $application
                );

                Log::debug("Ответ от API startAddCostCardBottomCreat: " . json_encode($responseArr));

                $orderNew = $responseArr["dispatching_order_uid"];

                (new PusherController)->sentUidAppEmailPayType(
                    $orderNew,
                    $application,
                    $email,
                    $pay_method
                );


                Log::debug("Создан новый заказ с UID startAddCostCardBottomCreat: " . $orderNew);
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                $order_old_uid = $order->dispatching_order_uid;
                $order_new_uid = $orderNew;


                (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);

                $orderMemory->dispatching_order_uid = $order_new_uid;
                $orderMemory->save();

                $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $order_old_uid)-> get();
                if($wfpInvoices != null) {
                    foreach ($wfpInvoices as $value) {
                        $wfpInvoice = WfpInvoice::where("dispatching_order_uid", $value->dispatching_order_uid)->first();
                        $wfpInvoice->dispatching_order_uid = $order_new_uid;
                        $wfpInvoice->save();
                    }
                }

                $order->dispatching_order_uid = $order_new_uid;
                $order->auto = null;
                $order->wfp_order_id = $orderReference;
                $order->web_cost = $responseArr["order_cost"];
                $order->closeReason = "-1";
                $order->closeReasonI = "0";
                $order->attempt_20 += $addCost;
                $order->save();

                Log::info("Обновлен order с новым UID startAddCostCardBottomCreat: " . $order_new_uid);
//Запуск вилки
                if ($responseBonusArr != null
                    && $responseDoubleArr != null
                    && isset($responseBonusArr["dispatching_order_uid"])
                    && isset($responseDoubleArr["dispatching_order_uid"])
                ) {
                    Log::debug("responseDoubleArr: startAddCostCardBottomCreat ", $responseDoubleArr);
                                  Log::debug("******************************");
                    Log::debug("DoubleOrder parameters startAddCostCardBottomCreat: ", [
                        'responseBonusStr' => json_encode($responseBonusArr),
                        'responseDoubleStr' => json_encode($responseDoubleArr),
                        'authorizationBonus' => $authorizationBonus,
                        'authorizationDouble' => $authorizationDouble,
                        'connectAPI' => $connectAPI,
                        'identificationId' => $identificationId,
                        'apiVersion' => $apiVersion
                    ]);

                    Log::debug("******************************");

                    $doubleOrder = new DoubleOrder();
                    $doubleOrder->responseBonusStr = json_encode($responseBonusArr);
                    $doubleOrder->responseDoubleStr = json_encode($responseDoubleArr);
                    $doubleOrder->authorizationBonus = $authorizationBonus;
                    $doubleOrder->authorizationDouble = $authorizationDouble;
                    $doubleOrder->connectAPI = $connectAPI;
                    $doubleOrder->identificationId = $identificationId;
                    $doubleOrder->apiVersion = $apiVersion;

                    Log::debug("Values set in DoubleOrder startAddCostCardBottomCreat:", [
                        'responseBonusStr' => $doubleOrder->responseBonusStr,
                        'responseDoubleStr' => $doubleOrder->responseDoubleStr,
                        'authorizationBonus' => $doubleOrder->authorizationBonus,
                        'authorizationDouble' => $doubleOrder->authorizationDouble,
                        'connectAPI' => $doubleOrder->connectAPI,
                        'identificationId' => $doubleOrder->identificationId,
                        'apiVersion' => $doubleOrder->apiVersion,
                    ]);

                    $doubleOrder->save();


                    Log::info("doubleOrder->id startAddCostCardBottomCreat" . $doubleOrder->id);
                    Log::debug("StartNewProcessExecution startAddCostCardBottomCreat" . $doubleOrder->id);
                    Log::debug("response_arr : startAddCostCardBottomCreat" . json_encode($doubleOrder->toArray()));

                    $messageAdmin = "StartNewProcessExecution (startAddCostCardBottomCreat): " . json_encode($doubleOrder->toArray());
                    (new MessageSentController)->sentMessageAdmin($messageAdmin);

                    self::deleteJobById($uid_history->orderId);

                    $uid_history = new Uid_history();
                    $uid_history->uid_bonusOrder = $responseBonusArr["dispatching_order_uid"];
                    $uid_history->uid_doubleOrder = $responseDoubleArr["dispatching_order_uid"];
                    $uid_history->uid_bonusOrderHold = $responseBonusArr["dispatching_order_uid"];
                    $uid_history->cancel = false;
                    $uid_history->orderId = $doubleOrder->id;
                    $uid_history->save();

                    StartNewProcessExecution::dispatch($doubleOrder->id);

                }

                return response()->json([
                    "response" => "200"
                ], 200);
            } else {

                Log::error("Неудачный запрос: статус  " . $response->status());
                Log::error("Ответ от API: " . $response->body());
                return response()->json([
                    "response" => "400"
                ], 200);
            }

    }
    /**
     * Найти ближайшего водителя в секторе из Firestore.
     *
     * @param float $latitude Широта точки
     * @param float $longitude Долгота точки
     * @return array|null Данные ближайшего водителя или null, если не найден
     */
    public function findDriverInSector(float $latitude, float $longitude): ?array
    {
        Log::info("findDriverInSector: Starting search for driver in sector.", [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        $nearestDriver = null;
        $nearestDistance = PHP_FLOAT_MAX;
        $driverPositions = DriverPosition::all();
        if ($driverPositions != null) {
            $driverPositionsArr = $driverPositions->toArray();

            foreach ($driverPositionsArr as $value) {
                // Вычисляем расстояние до водителя
                $driverLatitude = (float)$value['latitude'];
                $driverLongitude = (float)$value['longitude'];

                // Используем OpenStreetMapHelper для вычисления расстояния
                $osrmHelper = new OpenStreetMapHelper();
                if ($driverLatitude != 0 && $driverLongitude !=0) {
                    $distance = $osrmHelper->getRouteDistance(
                        $driverLatitude,
                        $driverLongitude,
                        $latitude,
                        $longitude,
                    );
                    Log::info("findDriverInSector: Calculated distance to driver.", [
                        'driver_id' => $value['driver_uid'],
                        'distance' => $distance,
                    ]);

                    // Если расстояние меньше 3 км и ближе предыдущего, обновляем ближайшего водителя
                    if ($distance !== null && $distance < 3000 && $distance < $nearestDistance) {
                        $nearestDriver = $value;
                        $nearestDistance = $distance;
                        Log::info("findDriverInSector: Found closer driver.", [
                            'driver_id' => $value['driver_uid'],
                            'new_nearest_distance' => $nearestDistance,
                        ]);
                    }
                }
            }
        }
        // Возвращаем данные ближайшего водителя или null, если не найден
        if ($nearestDriver) {
            Log::info("findDriverInSector: Nearest driver found.", [
                'nearest_driver' => $nearestDriver,
                'nearest_distance' => $nearestDistance,
            ]);
        } else {
            Log::info("findDriverInSector: No driver found within 3km range.");
        }

        return $nearestDriver;
    }

    public function verifyRefusal($uid, $driver_uid)
    {
        Log::info("Driver verifyRefusal orderId $uid");
        Log::info("Driver verifyRefusal driver_uid $driver_uid");
        $uid = (new MemoryOrderChangeController)->show($uid);
// Поиск в таблице 'orders_refusal'

        return (new OrdersRefusalController)->show($driver_uid, $uid);
    }

    public function lastAddressUser($email, $city, $app)
    {
        switch ($city) {
            case "Kyiv City":
                $city = "city_kiev";
                break;
            case "Cherkasy Oblast":
                $city = "city_cherkassy";
                break;
            case "Odessa":
            case "OdessaTest":
                $city = "city_odessa";
                break;
            case "Zaporizhzhia":
                $city = "city_zaporizhzhia";
                break;
            case "Dnipropetrovsk Oblast":
                $city = "city_dnipro";
                break;
            case "Lviv":
                $city = "city_lviv";
                break;
            case "Ivano_frankivsk":
                $city = "city_ivano_frankivsk";
                break;
            case "Vinnytsia":
                $city = "city_vinnytsia";
                break;
            case "Poltava":
                $city = "city_poltava";
                break;
            case "Sumy":
                $city = "city_sumy";
                break;
            case "Kharkiv":
                $city = "city_kharkiv";
                break;
            case "Chernihiv":
                $city = "city_chernihiv";
                break;
            case "Rivne":
                $city = "city_rivne";
                break;
            case "Ternopil":
                $city = "city_ternopil";
                break;
            case "Khmelnytskyi":
                $city = "city_khmelnytskyi";
                break;
            case "Zakarpattya":
                $city = "city_zakarpattya";
                break;
            case "Zhytomyr":
                $city = "city_zhytomyr";
                break;
            case "Kropyvnytskyi":
                $city = "city_kropyvnytskyi";
                break;
            case "Mykolaiv":
                $city = "city_mykolaiv";
                break;
            case "Сhernivtsi":
                $city = "city_chernivtsi";
                break;
            case "Lutsk":
                $city = "city_lutsk";
                break;
            default:
                $city = "all";
        }

        switch ($app) {
            case "PAS1":
                $app_order = "taxi_easy_ua_pas1";
                break;
            case "PAS2":
                $app_order = "taxi_easy_ua_pas2";
                break;
            //case "PAS4":
            default:
                $app_order = "taxi_easy_ua_pas4";


        }

        $order = Orderweb::where('email', $email)
            ->where('comment', $app_order)
            ->where('city', $city)
            ->orderByDesc('updated_at')
            ->select('routefrom', 'startLat', 'startLan')
            ->first();

        if ($order) { // Проверка на наличие результата
            return [
                "routefrom" => $order->routefrom ?? "*", // Используем null coalescing оператор
                "startLat" => $order->startLat ?? "0.0", // Если startLat null, возвращаем "0.0"
                "startLan" => $order->startLan ?? "0.0"  // Если startLan null, возвращаем "0.0"
            ];
        } else {
            return [
                "routefrom" => "*",
                "startLat" => "0.0",
                "startLan" => "0.0"
            ];
        }


    }


    public function sendOrderResponse($app, $email)
    {
        // Данные, которые отправляем в запросе
        $response_arr = [
            "dispatching_order_uid" => "ac318bf73ec046e9b060ae6fabcf8715",
            "discount_trip" => false,
            "find_car_timeout" => 14400,
            "find_car_delay" => 0,
            "order_cost" => "78",
            "currency" => " грн",
            "route_address_from" => [
                "name" => "поселок Дымер",
                "number" => null,
                "lat" => 50.7871845319286,
                "lng" => 30.3048966509098
            ],
            "route_address_to" => [
                "name" => "поселок Дымер",
                "number" => null,
                "lat" => 50.7871845319286,
                "lng" => 30.3048966509098
            ]
        ];

        $costMap = self::parseOrderResponse(
            $response_arr,
            "321356441316861",
            $app,
            $email
        );

        if (empty($costMap)) {
            return response()->json(['error' => 'No data found'], 400); // Return an error if the data is empty
        }

        return response()->json($costMap, 200);

    }


    public function parseOrderResponse(
        $response_arr,
        $dispatching_order_uid_Double,
        $required_time,
        $app,
        $email
    ): array {
        $costMap = [];

        // Проверка, что данные получены
        if ($response_arr && isset($response_arr['order_cost'])) {
            Log::debug("API_CALL", ['Order cost' => $response_arr['order_cost']]); // Логируем стоимость заказа

            // Если стоимость заказа не равна 0
            if ($response_arr['order_cost'] !== "0") {
                $costMap['from_lat'] = $response_arr['route_address_from']['lat'];
                $costMap['from_lng'] = $response_arr['route_address_from']['lng'];
                $costMap['lat'] = $response_arr['route_address_to']['lat'];
                $costMap['lng'] = $response_arr['route_address_to']['lng'];
                $costMap['dispatching_order_uid'] = $response_arr['dispatching_order_uid'];
                $costMap['order_cost'] = $response_arr['order_cost'];
                $costMap['currency'] = $response_arr['currency'];
                $costMap['routefrom'] = $response_arr['route_address_from']['name'];
                $costMap['routefromnumber'] = $response_arr['route_address_from']['number'];
                $costMap['routeto'] = $response_arr['route_address_to']['name'];
                $costMap['to_number'] = $response_arr['route_address_to']['number'];

                if($required_time != null) {
                    $costMap['required_time'] = $required_time;
                } else {
                    $costMap['required_time'] = "1970-01-01T03:00";
                }



                // Проверка на дополнительные поля, если они существуют

                if ($dispatching_order_uid_Double != "*") {
                    $costMap['dispatching_order_uid_Double'] = $dispatching_order_uid_Double;
                } else {
                    $costMap['dispatching_order_uid_Double'] = " ";
                }
            } else {
                $costMap['order_cost'] = "0";
                $costMap['message'] = $response_arr['message'] ?? 'Нет сообщения';
                Log::debug("API_CALL", ['No cost found', 'Message' => $response_arr['message']]); // Логируем сообщение
            }
        } else {
            $costMap['order_cost'] = "0";
            $costMap['message'] = "Сталася помилка";
            Log::error("API_CALL", ['Error in response' => 'Неверный формат ответа']); // Логируем ошибку ответа
        }
//dd($costMap);
        (new PusherController)->sendOrder($costMap, $app, $email);
        return $costMap;

    }




}
