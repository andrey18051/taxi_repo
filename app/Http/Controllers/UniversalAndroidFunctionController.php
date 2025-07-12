<?php

namespace App\Http\Controllers;


use App\Helpers\OpenStreetMapHelper;

use App\Helpers\OrderHelper;
use App\Helpers\TimeHelper;
use App\Jobs\ProcessAutoOrder;
use App\Jobs\SearchAutoOrderJob;
use App\Jobs\WebordersCancelAndRestorDoubleJob;
use App\Jobs\WebordersCancelAndRestorNalJob;
use Illuminate\Support\Facades\Cache;
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
use Illuminate\Support\Facades\Redis;
use Pusher\ApiErrorException;
use Pusher\PusherException;
use SebastianBergmann\Diff\Exception;

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

    public function postRequestCostHTTP($url, $parameter, $authorization, $identificationId, $apiVersion): string
    {
        Log::debug("⏳ [postRequestHTTP] Запуск функции...");

        // Создаем уникальный ключ для запроса
        $requestKey = md5($url . json_encode($parameter));
        $retryAfter = $this->isDuplicateRequest($requestKey);

        if ($retryAfter !== null) {
            return json_encode([
                'Message' => 'Повторный запрос',
                'retry_after_seconds' => $retryAfter
            ], JSON_UNESCAPED_UNICODE);
        }


        // Логируем успешную запись в кэш
        Log::debug("[postRequestHTTP] Ключ {$requestKey} успешно записан в кэш с TTL 60 сек.");

        try {
            $this->logRequestInput($url, $parameter, $authorization, $identificationId, $apiVersion);

            $this->waitIfNearNextHour($url);

            $response = $this->sendHttpPost($url, $parameter, $authorization, $identificationId, $apiVersion);

            return $this->handleHttpResponse($response);
        } catch (\Exception $e) {
            Log::critical("[postRequestHTTP] Исключение при выполнении запроса: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return json_encode([
                'Message' => $e->getMessage(),
                'status' => 500,
                'success' => false,
                'exception' => true
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    private function isDuplicateRequest(string $key, int $ttlSeconds = 60): ?int
    {
        $redis = Redis::connection();
        $prefix = config('cache.prefix', 'laravel_cache');
        $fullKey = "{$prefix}{$key}";

        // Пробуем установить ключ с TTL через SET ... NX EX
        $set = $redis->set($fullKey, true, 'EX', $ttlSeconds, 'NX');

        if ($set) {
            Log::debug("[DuplicateRequest] Ключ {$key} успешно установлен в Redis на {$ttlSeconds} сек.");
            return null;
        }

        // TTL проверки
        $ttl = $redis->ttl($fullKey);
        Log::debug("[DuplicateRequest] TTL от Redis: {$ttl} (ключ: {$fullKey})");

        switch (true) {
            case $ttl === -2:
                $ttlMessage = 'ключ не найден';
                $ttl = 0;
                break;
            case $ttl === -1:
                $ttlMessage = 'TTL не установлен';
                $ttl = $ttlSeconds;
                break;
            case is_numeric($ttl) && (int)$ttl >= 0:
                $ttl = (int)$ttl;
                $ttlMessage = "{$ttl} сек.";
                break;
            default:
                Log::error("[DuplicateRequest] Неизвестный формат TTL от Redis: " . var_export($ttl, true));
                $ttlMessage = 'неизвестно';
                $ttl = $ttlSeconds;
                break;
        }

        Log::warning("[DuplicateRequest] Повторный запрос заблокирован. Осталось: {$ttlMessage} (ключ: {$fullKey})");
        return $ttl;
    }




    private function logRequestInput($url, $parameter, $authorization, $identificationId, $apiVersion): void
    {
        Log::debug("[postRequestHTTP] Входные данные", [
            'url' => $url,
            'headers' => [
                'Authorization' => $authorization,
                'X-WO-API-APP-ID' => $identificationId,
                'X-API-VERSION' => $apiVersion
            ],
            'parameter' => $parameter
        ]);
    }

    private function waitIfNearNextHour(string $url): void
    {
        if (self::containsApiWebordersCost($url)) {
            $secondsToNextHour = TimeHelper::isFifteenSecondsToNextHour();
            Log::debug("[postRequestHTTP] Проверка следующего часа: осталось {$secondsToNextHour} сек.");

            if ($secondsToNextHour <= 15) {
                $sleepTime = $secondsToNextHour + 1;
                Log::info("[postRequestHTTP] Ждём до следующего часа: спим {$sleepTime} сек.");
                sleep($sleepTime);
            }
        }
    }

    private function sendHttpPost(
        $url,
        $parameter,
        $authorization,
        $identificationId,
        $apiVersion
    ): \Illuminate\Http\Client\Response {
        Log::info("[postRequestHTTP] Выполнение POST-запроса к: $url");

        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])
            ->timeout(60)
            ->post($url, $parameter);
    }

    private function handleHttpResponse(\Illuminate\Http\Client\Response $response): string
    {
        $statusCode = $response->status();
        $body = $response->body();

        Log::debug("[postRequestHTTP] HTTP статус: $statusCode");
        Log::debug("[postRequestHTTP] Тело ответа: $body");

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning("[postRequestHTTP] Невалидный JSON: " . json_last_error_msg());

            return json_encode([
                'Message' => 'Некорректный JSON от сервера',
                'status' => $statusCode,
                'success' => false,
                'raw' => $body
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($response->successful()) {
            Log::info("[postRequestHTTP] Успешный ответ получен.");
            return $body;
        }

        $message = $decoded['Message'] ?? 'Ошибка со стороны сервера';
        Log::error("[postRequestHTTP] Ошибка HTTP: $statusCode / Message: $message");

        return json_encode([
            'Message' => $message,
            'status' => $statusCode,
            'success' => false,
            'data' => $decoded
        ], JSON_UNESCAPED_UNICODE);
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
            if ($secondsToNextHour <= 15) {
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
                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));

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

    public function deleteJobByUid($orderId)
    {
        try {
            $doubleOrderRecord = DoubleOrder::find($orderId);
            if ($doubleOrderRecord) {
                $doubleOrderRecord->delete();
                $messageAdmin = "Запись  $orderId удалена из DoubleOrder";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
            }


//            $id = $uid_history->jobId;
//            // Проверяем, существует ли запись с указанным ID
//            $job = DB::table('jobs')->where('id', $id)->first();
//            if (!$job) {
//                return response()->json([
//                    'success' => false,
//                    'message' => 'Запись с указанным ID не найдена',
//                ], 404);
//            }
//
//            // Удаляем запись
//            DB::table('jobs')->where('id', $id)->delete();
//            $messageAdmin = "Запись задачи $id удалена из Jobs";
//            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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
    private function writeAutoInfo($uid_history) {


        if ($uid_history) {

            $nalOrderInput = $uid_history->double_status;
            $cardOrderInput = $uid_history->bonus_status;
            $messageAdmin = "function writeAutoInfo nalOrderInput $nalOrderInput" ;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $messageAdmin = "function writeAutoInfo cardOrderInput $cardOrderInput" ;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $nalOrder = json_decode($nalOrderInput, true);
            $cardOrder = json_decode($cardOrderInput, true);

            $autoInfoNal =  $nalOrder['order_car_info'];
            $messageAdmin = "function writeAutoInfo autoInfoNal $autoInfoNal" ;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $autoInfoCard =  $cardOrder['order_car_info'];
            $messageAdmin = "function writeAutoInfo autoInfoCard $autoInfoCard" ;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $orderweb = Orderweb::where("dispatching_order_uid", $uid_history->uid_bonusOrderHold)->first();
            $old_auto = $orderweb->auto;
            if ($orderweb) {
                $orderweb->auto = $autoInfoNal ?? $autoInfoCard ?? null;

                $orderweb->save();
                $messageAdmin = "function writeAutoInfo $orderweb->auto" ;
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                if ($orderweb->auto != null && $old_auto != $orderweb->auto) {
                    Log::info('writeAutoInfo: Найден автоматический заказ, отправка ответа', [
                        'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                        'auto' => $orderweb->auto
                    ]);
                    (new UniversalAndroidFunctionController)->sendAutoOrderMyVodResponse($orderweb);
                    Log::info('writeAutoInfo: Ответ отправлен', [
                        'dispatching_order_uid' => $orderweb->dispatching_order_uid
                    ]);
                }
            }
        }
    }
    /**
     * @throws \Exception
     */
    public function startNewProcessExecutionStatusJob($doubleOrderId, $jobId): ?string
    {
        try {
            $messageAdmin = "!!! 17032025 !!! startNewProcessExecutionStatusJob задача $doubleOrderId / $jobId";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
//            $maxExecutionTime = 60*60; // Максимальное время выполнения - 3 суток

            $maxExecutionTime = config("app.exec_time");; // Максимальное время выполнения - 3 суток


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

            $messageAdmin = "maxExecutionTime $doubleOrderId / $jobId $maxExecutionTime";

            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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
                            $updateTime = 5;
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
//                while (time() - $startTime < $maxExecutionTime) {
                while (true) {

                     self::writeAutoInfo($uid_history);


                    $doubleOrderRecord = DoubleOrder::find($doubleOrderId);
                    if (!$doubleOrderRecord) {
                        return "exit";
                    }
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                                    $updateTime = 5;
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
                                                                    $updateTime = 5;
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
                                                                    $updateTime = 5;
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
                                                                    $updateTime = 5;
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
                                                                    $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                            $updateTime = 5;
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
                                                                    $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                        $updateTime = 5;
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
                                                                        $updateTime = 5;
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
                                                                        $updateTime = 5;
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
                                                                        $updateTime = 5;
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
                                                                        $updateTime = 5;
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
                                                                        $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
                                                                $updateTime = 5;
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
//                if (time() - $startTime >= $maxExecutionTime) {
//                    try {
//                        self::orderCanceled(
//                            $bonusOrder,
//                            'bonus',
//                            $connectAPI,
//                            $authorizationBonus,
//                            $identificationId,
//                            $apiVersion
//                        );
//
//                        self::orderCanceled(
//                            $doubleOrder,
//                            "double",
//                            $connectAPI,
//                            $authorizationDouble,
//                            $identificationId,
//                            $apiVersion
//                        );
//        //                $uid_history->delete();
//                        $canceledAll = self::canceledFinish(
//                            $lastStatusBonus,
//                            $lastStatusDouble,
//                            $bonusOrderHold,
//                            $bonusOrder,
//                            $connectAPI,
//                            $authorizationBonus,
//                            $identificationId,
//                            $apiVersion,
//                            $doubleOrder,
//                            $authorizationDouble
//                        );
//
//                        if ($canceledAll) {
//                            self::newStatus(
//                                $authorizationBonus,
//                                $identificationId,
//                                $apiVersion,
//                                $responseBonus["url"],
//                                $bonusOrder,
//                                "bonus",
//                                $lastTimeUpdate,
//                                $updateTime,
//                                $uid_history
//                            );
//
//                            self::newStatus(
//                                $authorizationDouble,
//                                $identificationId,
//                                $apiVersion,
//                                $responseDouble["url"],
//                                $doubleOrder,
//                                "double",
//                                $lastTimeUpdate,
//                                $updateTime,
//                                $uid_history
//                            );
//                            $messageAdmin = "canceled while
//                                     lastStatusBonus3:  $lastStatusBonus
//                                     lastStatusDouble3:  $lastStatusDouble
//                                     doubleOrderRecord 3 $doubleOrderRecord";
//
//                            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//
//                            $doubleOrderRecord->delete();
//        //                            self::orderReview($bonusOrder, $doubleOrder, $bonusOrderHold);
//
//                            return "exit";
//                        }
//
//                        $messageAdmin = "doubleOrderRecord orderCanceled $doubleOrderRecord";
//
//                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//                        Log::info("Превышено время выполнения для doubleOrderId: $doubleOrderId, перенос задания");
////                        StartNewProcessExecution::dispatch($doubleOrderId)->delay(now()->addMinutes(5));
//                        $doubleOrderRecord->delete();
//                        return "exit";
//                    } catch (\Exception $e) {
//                        Log::error("Ошибка в цикле для doubleOrderId: $doubleOrderId: " . $e->getMessage());
//                        throw $e; // Повторно выбросить для отметки задания как неудачного
//                    }
//                    sleep(5);
//
//                }
//                return "exit";
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

             (new MessageSentController)->sentMessageAdmin($messageAdmin);

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
//             (new OrderStatusController)->getOrderStatusMessageResultPush($uid_history->uid_bonusOrderHold);

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

                try {
                    $orderweb = Orderweb::where("dispatching_order_uid", $uid_bonusOrderHold)->first();

                    $orderweb->closeReason = "1";
                    $orderweb->save();
                    $uid_history->cancel = "1";
                    $uid_history->save();

                    $email = $orderweb->email;

                    switch ($orderweb->comment) {
                        case "taxi_easy_ua_pas1":
                            $app = "PAS1";
                            break;
                        case "taxi_easy_ua_pas2":
                            $app = "PAS2";
                            break;
                        //case "PAS4":
                        default:
                            $app = "PAS4";
                    }

                    $dispatching_order_uid = $orderweb->dispatching_order_uid;
                    (new PusherController)->sentCanceledStatus(
                        $app,
                        $email,
                        $dispatching_order_uid
                    );
                    return "exit";
                } catch (ApiErrorException | PusherException $e) {
                }

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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
    ) {
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
        Log::info("orderReview: dispatching_order_uid=$bonusOrderHold, closeReason={$order->closeReason}");

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
        if (isset($params['clientCost'])) {
            if($params['clientCost'] != "0") {
                $order->client_cost = $params['clientCost'];
            } else {
                $order->client_cost = $params['order_cost'];
            }

        }

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

        if ($params["payment_type"] != 1) {
            SearchAutoOrderJob::dispatch($params['dispatching_order_uid']);
        } else {
//            SearchAutoOrderCardJob::dispatch($params['dispatching_order_uid']);
        }


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
                ". Вартість поїздки становитиме: " . $params['clientCost'] . "грн. $pay_type Номер замовлення: " .
                $params['dispatching_order_uid'] .
                ", сервер " . $params['server'];
            ;
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " по місту. Вартість поїздки становитиме: " . $params['clientCost'] . "грн. $pay_type Номер замовлення: " .
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
                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
                Mail::to('cartaxi4@gmail.com')->send(new Check($paramsCheck));
            } catch (\Exception $e) {
                Log::error('Mail send failed: ' . $e->getMessage());
                // Дополнительные действия для предотвращения сбоя
            }

        };

        try {
            Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
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

//        switch ($app) {
//            case "PAS1":
//                $city = City_PAS1::where('name', $cityString)
//                    ->where("address", str_replace("http://", "", $connectAPI))
//                    ->first();
//                break;
//            case "PAS2":
//                $city = City_PAS2::where('name', $cityString)
//                    ->where("address", str_replace("http://", "", $connectAPI))
//                    ->first();
//                break;
//            //case "PAS4":
//            default:
//                $city = City_PAS4::where('name', $cityString)
//                    ->where("address", str_replace("http://", "", $connectAPI))
//                    ->first();
//                break;
//        }

        $cacheKey = $app . '_' . $cityString . '_' . $connectAPI;
//        $messageAdmin = "cacheKey $cacheKey";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        try {
            $city = Cache::remember($cacheKey, now()->addDays(1), function () use ($app, $cityString, $connectAPI) {
                switch ($app) {
                    case "PAS1":
                        return City_PAS1::where('name', $cityString)
                            ->where("address", str_replace("http://", "", $connectAPI))
                            ->first();
                    case "PAS2":
                        return City_PAS2::where('name', $cityString)
                            ->where("address", str_replace("http://", "", $connectAPI))
                            ->first();
                    default:
                        return City_PAS4::where('name', $cityString)
                            ->where("address", str_replace("http://", "", $connectAPI))
                            ->first();
                }
            });

        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error("Cache driver failed: " . $e->getMessage());

            // Fallback: прямой запрос к базе данных
            $cleanedAddress = str_replace("http://", "", $connectAPI);
            switch ($app) {
                case "PAS1":
                    return City_PAS1::where('name', $cityString)
                        ->where("address", $cleanedAddress)
                        ->first();
                case "PAS2":
                    return City_PAS2::where('name', $cityString)
                        ->where("address", $cleanedAddress)
                        ->first();
                default:
                    return City_PAS4::where('name', $cityString)
                        ->where("address", $cleanedAddress)
                        ->first();
            }
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

    public function costCorrectionValue($cityString, $connectAPI, $app): string
    {

        $cacheKey = $app . '_' . $cityString . '_' . $connectAPI . '_costCorrectionValue';

        try {
            return Cache::remember($cacheKey, now()->addDays(1), function () use ($app, $cityString, $connectAPI) {
                switch ($app) {
                    case "PAS1":
                        $city = City_PAS1::where('name', $cityString)
                            ->where('address', str_replace('http://', '', $connectAPI))
                            ->first();

                        return $city ? $city->cost_correction : 0;
                    case "PAS2":
                        $city = City_PAS2::where('name', $cityString)
                            ->where('address', str_replace('http://', '', $connectAPI))
                            ->first();

                        return $city ? $city->cost_correction : 0;

                    default:
                        $city = City_PAS4::where('name', $cityString)
                            ->where('address', str_replace('http://', '', $connectAPI))
                            ->first();

                        return $city ? $city->cost_correction : 0;
                }
            });

        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error("Cache driver failed: " . $e->getMessage());

            // Fallback: прямой запрос к базе данных

            switch ($app) {
                case "PAS1":
                    $city = City_PAS1::where('name', $cityString)
                        ->where('address', str_replace('http://', '', $connectAPI))
                        ->first();
                    return $city ? $city->cost_correction : 0;
                case "PAS2":
                    $city = City_PAS2::where('name', $cityString)
                        ->where('address', str_replace('http://', '', $connectAPI))
                        ->first();
                    return $city ? $city->cost_correction : 0;
                default:
                    $city = City_PAS4::where('name', $cityString)
                        ->where('address', str_replace('http://', '', $connectAPI))
                        ->first();
                    return $city ? $city->cost_correction : 0;
            }
        }
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
//        switch ($app) {
//            case "PAS1":
//                $city = City_PAS1::where('name', $name)->where('address', $cleanedUrl)->first();
//                break;
//            case "PAS2":
//                $city = City_PAS2::where('name', $name)->where('address', $cleanedUrl)->first();
//                break;
//           //case "PAS4":
//            default:
//                $city = City_PAS4::where('name', $name)->where('address', $cleanedUrl)->first();
//        }

        $cacheTime = 60 * 60 * 24; // 1 день

        try {
            $city = Cache::remember("city_{$name}_{$cleanedUrl}_{$app}", $cacheTime, function () use ($app, $name, $cleanedUrl) {
                switch ($app) {
                    case "PAS1":
                        return City_PAS1::where('name', $name)->where('address', $cleanedUrl)->first();
                    case "PAS2":
                        return City_PAS2::where('name', $name)->where('address', $cleanedUrl)->first();
                    default:
                        return City_PAS4::where('name', $name)->where('address', $cleanedUrl)->first();
                }
            });
        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error("Cache driver failed: " . $e->getMessage());
            // Fallback: прямой запрос к базе данных
            switch ($app) {
                case "PAS1":
                    return City_PAS1::where('name', $name)->where('address', $cleanedUrl)->first();
                case "PAS2":
                    return City_PAS2::where('name', $name)->where('address', $cleanedUrl)->first();
                default:
                    return City_PAS4::where('name', $name)->where('address', $cleanedUrl)->first();
            }
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
                    $orderweb->client_cost,
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
        $cost = $orderweb->web_cost;
        if($orderweb->client_cost !=null) {
            $cost = $orderweb->client_cost;
        }
        switch ($pay_system) {
            case "wfp_payment":
                $orderweb->wfp_order_id = $orderReference;
                if ($uid !== null) {
                    self::wfpInvoice($orderReference, $cost, $uid);
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
        $isCurrentTimeInRange = (new UniversalAndroidFunctionController)->isCurrentTimeInRange();
        if (!$isCurrentTimeInRange) {
            try {
                $alarmMessage->sendAlarmMessageLog($messageAdmin);
                $alarmMessage->sendMeMessageLog($messageAdmin);
            } catch (Exception $e) {
                $paramsCheck = [
                    'subject' => 'Ошибка в телеграмм',
                    'message' => $e,
                ];
                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
            };
        }
    }
//    public function findCity($startLat, $startLan)
//    {
//        Log::debug("findCity $startLat, $startLan");
//
//        $cities = [
//            'city_kiev' => [ // оставляем как есть
//                'lat_min' => 49.8000,
//                'lat_max' => 50.2999,
//                'lan_min' => 29.9000,
//                'lan_max' => 31.4999,
//            ],
//            'city_cherkassy' => [
//                'lat_min' => 49.3500,
//                'lat_max' => 49.5000,
//                'lan_min' => 31.9500,
//                'lan_max' => 32.1500,
//            ],
//            'city_odessa' => [
//                'lat_min' => 46.3500,
//                'lat_max' => 46.6000,
//                'lan_min' => 30.5500,
//                'lan_max' => 30.9000,
//            ],
//            'city_zaporizhzhia' => [
//                'lat_min' => 47.7500,
//                'lat_max' => 47.9500,
//                'lan_min' => 35.0000,
//                'lan_max' => 35.3000,
//            ],
//            'city_dnipro' => [
//                'lat_min' => 48.3500,
//                'lat_max' => 48.5500,
//                'lan_min' => 34.8500,
//                'lan_max' => 35.1500,
//            ],
//            'city_lviv' => [
//                'lat_min' => 49.7500,
//                'lat_max' => 49.9500,
//                'lan_min' => 23.9000,
//                'lan_max' => 24.2000,
//            ],
//            'city_ivano_frankivsk' => [
//                'lat_min' => 48.8500,
//                'lat_max' => 48.9500,
//                'lan_min' => 24.6500,
//                'lan_max' => 24.8500,
//            ],
//            'city_vinnytsia' => [
//                'lat_min' => 49.1900,
//                'lat_max' => 49.3000,
//                'lan_min' => 28.3500,
//                'lan_max' => 28.5500,
//            ],
//            'city_poltava' => [
//                'lat_min' => 49.5300,
//                'lat_max' => 49.6500,
//                'lan_min' => 34.4500,
//                'lan_max' => 34.6500,
//            ],
//            'city_sumy' => [
//                'lat_min' => 50.8500,
//                'lat_max' => 50.9500,
//                'lan_min' => 34.7000,
//                'lan_max' => 34.9000,
//            ],
//            'city_kharkiv' => [
//                'lat_min' => 49.8500,
//                'lat_max' => 50.0500,
//                'lan_min' => 36.1500,
//                'lan_max' => 36.4500,
//            ],
//            'city_chernihiv' => [
//                'lat_min' => 51.4500,
//                'lat_max' => 51.6000,
//                'lan_min' => 31.2000,
//                'lan_max' => 31.5000,
//            ],
//            'city_rivne' => [
//                'lat_min' => 50.5800,
//                'lat_max' => 50.7000,
//                'lan_min' => 26.2000,
//                'lan_max' => 26.4000,
//            ],
//            'city_ternopil' => [
//                'lat_min' => 49.5000,
//                'lat_max' => 49.6500,
//                'lan_min' => 25.5000,
//                'lan_max' => 25.7000,
//            ],
//            'city_khmelnytskyi' => [
//                'lat_min' => 49.3700,
//                'lat_max' => 49.5000,
//                'lan_min' => 26.9500,
//                'lan_max' => 27.1500,
//            ],
//            'city_zakarpattya' => [ // Ужгород
//                'lat_min' => 48.5800,
//                'lat_max' => 48.6700,
//                'lan_min' => 22.2300,
//                'lan_max' => 22.3500,
//            ],
//            'city_zhytomyr' => [
//                'lat_min' => 50.2000,
//                'lat_max' => 50.3500,
//                'lan_min' => 28.5500,
//                'lan_max' => 28.7500,
//            ],
//            'city_kropyvnytskyi' => [
//                'lat_min' => 48.4500,
//                'lat_max' => 48.6000,
//                'lan_min' => 32.1500,
//                'lan_max' => 32.3500,
//            ],
//            'city_mykolaiv' => [
//                'lat_min' => 46.8500,
//                'lat_max' => 47.0000,
//                'lan_min' => 31.9000,
//                'lan_max' => 32.1500,
//            ],
//            'city_chernivtsi' => [
//                'lat_min' => 48.2500,
//                'lat_max' => 48.3500,
//                'lan_min' => 25.8500,
//                'lan_max' => 26.0500,
//            ],
//            'city_lutsk' => [
//                'lat_min' => 50.7000,
//                'lat_max' => 50.8500,
//                'lan_min' => 25.2500,
//                'lan_max' => 25.4500,
//            ],
//        ];
//
//
//
//        foreach ($cities as $city => $coords) {
//            if ($startLat >= $coords['lat_min'] && $startLat <= $coords['lat_max'] &&
//                $startLan >= $coords['lan_min'] && $startLan <= $coords['lan_max']) {
//                return $city; // Возвращаем имя города, если точка в пределах его границ
//            }
//        }
//
//        return "all"; // Если город не найден
//    }



    public function findCityOld($startLat, $startLan)
    {
        Log::debug("findCity: запрошены координаты lat=$startLat, lon=$startLan");

        $cacheKey = "geo_city_" . round($startLat, 4) . "_" . round($startLan, 4);

        return Cache::remember($cacheKey, now()->addMinutes(1), function () use ($startLat, $startLan) {

            Log::debug("findCity: выполняем запрос к Nominatim...");

            $cityNameMap = [
                // Kyiv
                'kyiv' => 'city_kiev',
                'київ' => 'city_kiev',
                'киев' => 'city_kiev',

                // Cherkasy
                'cherkasy' => 'city_cherkassy',
                'черкаси' => 'city_cherkassy',
                'черкассы' => 'city_cherkassy',

                // Odesa
                'odesa' => 'city_odessa',
                'одеса' => 'city_odessa',
                'одесса' => 'city_odessa',

                // Zaporizhzhia
                'zaporizhzhia' => 'city_zaporizhzhia',
                'запоріжжя' => 'city_zaporizhzhia',
                'запорожье' => 'city_zaporizhzhia',

                // Dnipro
                'dnipro' => 'city_dnipro',
                'дніпро' => 'city_dnipro',
                'днепр' => 'city_dnipro',

                // Lviv
                'lviv' => 'city_lviv',
                'львів' => 'city_lviv',
                'львов' => 'city_lviv',

                // Ivano-Frankivsk
                'ivano-frankivsk' => 'city_ivano_frankivsk',
                'івано-франківськ' => 'city_ivano_frankivsk',
                'ивано-франковск' => 'city_ivano_frankivsk',

                // Vinnytsia
                'vinnytsia' => 'city_vinnytsia',
                'вінниця' => 'city_vinnytsia',
                'винница' => 'city_vinnytsia',

                // Poltava
                'poltava' => 'city_poltava',
                'полтава' => 'city_poltava',

                // Sumy
                'sumy' => 'city_sumy',
                'суми' => 'city_sumy',

                // Kharkiv
                'kharkiv' => 'city_kharkiv',
                'харків' => 'city_kharkiv',
                'харьков' => 'city_kharkiv',

                // Chernihiv
                'chernihiv' => 'city_chernihiv',
                'чернігів' => 'city_chernihiv',
                'чернигов' => 'city_chernihiv',

                // Rivne
                'rivne' => 'city_rivne',
                'рівне' => 'city_rivne',
                'ровно' => 'city_rivne',

                // Ternopil
                'ternopil' => 'city_ternopil',
                'тернопіль' => 'city_ternopil',
                'тернополь' => 'city_ternopil',

                // Khmelnytskyi
                'khmelnytskyi' => 'city_khmelnytskyi',
                'хмельницький' => 'city_khmelnytskyi',
                'хмельницкий' => 'city_khmelnytskyi',

                // Uzhhorod (Zakarpattya)
                'uzhhorod' => 'city_zakarpattya',
                'ужгород' => 'city_zakarpattya',

                // Zhytomyr
                'zhytomyr' => 'city_zhytomyr',
                'житомир' => 'city_zhytomyr',

                // Kropyvnytskyi
                'kropyvnytskyi' => 'city_kropyvnytskyi',
                'кропивницький' => 'city_kropyvnytskyi',
                'кировоград' => 'city_kropyvnytskyi', // старое название

                // Mykolaiv
                'mykolaiv' => 'city_mykolaiv',
                'миколаїв' => 'city_mykolaiv',
                'николаев' => 'city_mykolaiv',

                // Chernivtsi
                'chernivtsi' => 'city_chernivtsi',
                'чернівці' => 'city_chernivtsi',
                'черновцы' => 'city_chernivtsi',

                // Lutsk
                'lutsk' => 'city_lutsk',
                'луцьк' => 'city_lutsk',
                'луцк' => 'city_lutsk',
            ];

        //    https://nominatim.openstreetmap.org/reverse?format=json&lat=50.787184&lon=30.304899


            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$startLat&lon=$startLan";
            $context = stream_context_create([
                "http" => [
                    "header" => "User-Agent: MyApp/1.0\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if (!$response) {
                Log::error("findCity: ошибка при обращении к Nominatim для координат lat=$startLat, lon=$startLan");
                return "all";
            }

            Log::debug("findCity: успешный ответ от Nominatim");

            $data = json_decode($response, true);
            $possibleNames = [];

            if (isset($data['address'])) {
                $address = $data['address'];
                Log::debug("findCity: адрес из Nominatim: " . json_encode($address, JSON_UNESCAPED_UNICODE));

                foreach (['city', 'town', 'village', 'municipality', 'state'] as $field) {
                    if (!empty($address[$field])) {
                        $normalized = mb_strtolower(trim($address[$field]));
                        $possibleNames[] = $normalized;
                        Log::debug("findCity: найдено имя '$normalized' в поле '$field'");
                    }
                }
            } else {
                Log::warning("findCity: поле address отсутствует в ответе Nominatim");
            }

            foreach ($possibleNames as $name) {
                if (isset($cityNameMap[$name])) {
                    Log::info("findCity: сопоставлено '$name' => " . $cityNameMap[$name]);
                    return $cityNameMap[$name];
                } else {
                    Log::debug("findCity: '$name' не найдено в cityNameMap");
                }
            }

            Log::info("findCity: город не найден, возвращаем 'all'");
            return "all";
        });
    }

    public function findCity($startLat, $startLon)
    {
        Log::debug("findCity: запрошены координаты lat=$startLat, lon=$startLon");

        $cacheKey = "geo_city_" . round($startLat, 4) . "_" . round($startLon, 4);

        return Cache::remember($cacheKey, now()->addMinutes(1), function () use ($startLat, $startLon) {

            // Вспомогательная функция для нормализации
            $normalize = function ($name) {
                return mb_strtolower(trim($name));
            };


            // Прямое соответствие: название города => код
            $rawCityMap = [
                'Киев' => 'city_kiev', 'Київ' => 'city_kiev',
                'Черкассы' => 'city_cherkassy', 'Черкаси' => 'city_cherkassy',
                'Одесса' => 'city_odessa', 'Одеса' => 'city_odessa',
                'Запорожье' => 'city_zaporizhzhia', 'Запоріжжя' => 'city_zaporizhzhia',
                'Днепр' => 'city_dnipro', 'Дніпро' => 'city_dnipro',
                'Львов' => 'city_lviv', 'Львів' => 'city_lviv',
                'Ивано-Франковск' => 'city_ivano_frankivsk', 'Івано-Франківськ' => 'city_ivano_frankivsk',
                'Винница' => 'city_vinnytsia', 'Вінниця' => 'city_vinnytsia',
                'Полтава' => 'city_poltava',
                'Сумы' => 'city_sumy', 'Суми' => 'city_sumy',
                'Харьков' => 'city_kharkiv', 'Харків' => 'city_kharkiv',
                'Чернигов' => 'city_chernihiv', 'Чернігів' => 'city_chernihiv',
                'Ровно' => 'city_rivne', 'Рівне' => 'city_rivne',
                'Тернополь' => 'city_ternopil', 'Тернопіль' => 'city_ternopil',
                'Хмельницкий' => 'city_khmelnytskyi', 'Хмельницький' => 'city_khmelnytskyi',
                'Ужгород' => 'city_zakarpattya',
                'Луцк' => 'city_lutsk', 'Луцьк' => 'city_lutsk',
                'Житомир' => 'city_zhytomyr',
                'Кропивницкий' => 'city_kropyvnytskyi', 'Кропивницький' => 'city_kropyvnytskyi',
                'Кировоград' => 'city_kropyvnytskyi',
                'Николаев' => 'city_mykolaiv', 'Миколаїв' => 'city_mykolaiv',
                'Черновцы' => 'city_chernivtsi', 'Чернівці' => 'city_chernivtsi',
                'Донецк' => 'city_donetsk', 'Донецьк' => 'city_donetsk',
                'Луганск' => 'city_luhansk', 'Луганськ' => 'city_luhansk',
                'Херсон' => 'city_kherson',
                'Симферополь' => 'city_simferopol',
            ];
            $cityNameMap = [];
            foreach ($rawCityMap as $name => $code) {
                $cityNameMap[$normalize($name)] = $code;
            }

            // Сопоставление регионов
            $rawRegionMap = [
                'Київська область' => 'city_kiev', 'Киевская область' => 'city_kiev',
                'Черкаська область' => 'city_cherkassy', 'Черкасская область' => 'city_cherkassy',
                'Одеська область' => 'city_odessa', 'Одесская область' => 'city_odessa',
                'Запорізька область' => 'city_zaporizhzhia', 'Запорожская область' => 'city_zaporizhzhia',
                'Дніпропетровська область' => 'city_dnipro', 'Днепропетровская область' => 'city_dnipro',
                'Львівська область' => 'city_lviv', 'Львовская область' => 'city_lviv',
                'Івано-Франківська область' => 'city_ivano_frankivsk', 'Ивано-Франковская область' => 'city_ivano_frankivsk',
                'Вінницька область' => 'city_vinnytsia', 'Винницкая область' => 'city_vinnytsia',
                'Полтавська область' => 'city_poltava', 'Полтавская область' => 'city_poltava',
                'Сумська область' => 'city_sumy', 'Сумская область' => 'city_sumy',
                'Харківська область' => 'city_kharkiv', 'Харьковская область' => 'city_kharkiv',
                'Чернігівська область' => 'city_chernihiv', 'Черниговская область' => 'city_chernihiv',
                'Рівненська область' => 'city_rivne', 'Ровненская область' => 'city_rivne',
                'Тернопільська область' => 'city_ternopil', 'Тернопольская область' => 'city_ternopil',
                'Хмельницька область' => 'city_khmelnytskyi', 'Хмельницкая область' => 'city_khmelnytskyi',
                'Закарпатська область' => 'city_zakarpattya', 'Закарпатская область' => 'city_zakarpattya',
                'Житомирська область' => 'city_zhytomyr', 'Житомирская область' => 'city_zhytomyr',
                'Кіровоградська область' => 'city_kropyvnytskyi', 'Кировоградская область' => 'city_kropyvnytskyi',
                'Миколаївська область' => 'city_mykolaiv', 'Николаевская область' => 'city_mykolaiv',
                'Чернівецька область' => 'city_chernivtsi', 'Черновицкая область' => 'city_chernivtsi',
                'Волинська область' => 'city_lutsk', 'Волынская область' => 'city_lutsk',
                'Донецька область' => 'city_donetsk', 'Донецкая область' => 'city_donetsk',
                'Луганська область' => 'city_luhansk', 'Луганская область' => 'city_luhansk',
                'Херсонська область' => 'city_kherson', 'Херсонская область' => 'city_kherson',
                'Автономна Республіка Крим' => 'city_simferopol', 'Автономная Республика Крым' => 'city_simferopol',
            ];
            $regionToCityMap = [];
            foreach ($rawRegionMap as $name => $code) {
                $regionToCityMap[$normalize($name)] = $code;
            }

            // Запрос к Nominatim
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$startLat&lon=$startLon";
            $context = stream_context_create([
                "http" => [
                    "header" => "User-Agent: MyApp/1.0\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);
            if (!$response) {
                Log::error("findCity: ошибка при запросе к Nominatim");
                return 'all';
            }

            Log::debug("findCity: успешный ответ от Nominatim");

            $data = json_decode($response, true);
            $possibleNames = [];

            if (!empty($data['address'])) {
                $address = $data['address'];
                Log::debug("findCity: адрес из Nominatim: " . json_encode($address, JSON_UNESCAPED_UNICODE));

                foreach (['city', 'town', 'village', 'municipality', 'borough', 'district', 'state'] as $field) {
                    if (!empty($address[$field])) {
                        $normalized = $normalize($address[$field]);
                        $possibleNames[] = $normalized;
                        Log::debug("findCity: найдено значение '$normalized' в поле '$field'");
                    }
                }
            }

            Log::debug("findCity: список возможных названий: " . implode(', ', $possibleNames));

            // Сопоставление по названию города
            foreach ($possibleNames as $name) {
                if (isset($cityNameMap[$name])) {
                    Log::info("findCity: прямое совпадение '$name' => " . $cityNameMap[$name]);
                    return $cityNameMap[$name];
                }
            }

            // Сопоставление по региону
            foreach ($possibleNames as $name) {
                if (isset($regionToCityMap[$name])) {
                    Log::info("findCity: совпадение по региону '$name' => " . $regionToCityMap[$name]);
                    return $regionToCityMap[$name];
                }
            }

            Log::info("findCity: город не определён, возвращаем 'all'");
            return 'all';
        });
    }



    public function detectCity($lat, $lon)
    {
        if (!$lat || !$lon) {
            return response()->json(['error' => 'Missing coordinates'], 400);
        }

        $cityCode = $this->findCity($lat, $lon);

        return response()->json(['city_code' => $cityCode]);
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
                $city = "Chernivtsi";
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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
    public function startAddCostWithAddBottomUpdate($uid, $addCost): ?\Illuminate\Http\JsonResponse
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
                $city = "Chernivtsi";
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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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


//                (new AndroidTestOSMController)->webordersCancelAddCostNal($uid, $city, $application);
//                (new AndroidTestOSMController)->webordersCancelRestorAddCostNal($uid, $city, $application, $order);
                WebordersCancelAndRestorNalJob::dispatch($uid, $city, $application, $order);

                Log::debug("Ответ от API: " . json_encode($responseArr));
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);



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
                (new MessageSentController)->sentMessageAdminLog("Неудачный запрос: статус " . $response->status());
                (new MessageSentController)->sentMessageAdmin("Неудачный запрос: статус" . $response->status() . "Ответ от API: " . $response->body());
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
                $city = "Chernivtsi";
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
//        $parameter['add_cost'] = (int) $order->attempt_20 + (int)$addCost+ $addCostBalance;

        $messageAdmin = "Параметры запроса нового заказа" . json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);


        $order_first = Orderweb::where("dispatching_order_uid", $uid)->first();
        // Получаем UID из MemoryOrderChangeController
        $uid = (new MemoryOrderChangeController)->show($uid);

        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);
        $messageAdmin = "MemoryOrderChangeController возвращает UID: " . $uid;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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
                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        // Ищем данные из памяти о заказе

        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден orderMemory с UID startAddCostCardBottomCreat: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null'));
        $messageAdmin = "Найден orderMemory с UID startAddCostCardBottomCreat: " . ($orderMemory ? $orderMemory->dispatching_order_uid : 'null');
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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

//        $addCostBalance = OrderHelper::calculateCostBalanceAfterHourChange(
//            $url,
//            $parameter,
//            $authorization,
//            $identificationId,
//            $apiVersion,
//            $addCost,
//            $order
//        );
//
//
//        $parameter['add_cost'] = (int) $order->attempt_20 + (int) $order->add_cost + (int) $addCost + $addCostBalance;
        $parameter['add_cost'] = (int) $order->attempt_20 + (int) $order->add_cost + (int) $addCost;

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


                $messageAdmin = "Add cost webordersCancelDouble \n uid $uid \n uid_Double $uid_Double \n payment_type $payment_type \n application $application" ;

                (new MessageSentController)->sentMessageAdminLog($messageAdmin);



                Log::debug("Ответ от API startAddCostCardBottomCreat: " . json_encode($responseArr));

                $orderNew = $responseArr["dispatching_order_uid"];


                (new PusherController)->sentUidAppEmailPayType(
                    $orderNew,
                    $application,
                    $email,
                    $pay_method
                );

                if(isset($responseDoubleArr["dispatching_order_uid"])) {
                    $orderDoubleNew = $responseDoubleArr["dispatching_order_uid"];
                    (new PusherController)->sentUidDoubleAppEmailPayType(
                        $orderDoubleNew,
                        $application,
                        $email,
                        $pay_method
                    );
                }
//                CacheHandler::cacheEventPut($uid, true, 60);
//                (new AndroidTestOSMController)->webordersCancelAndRestorDouble(
//                    $uid,
//                    $uid_Double,
//                    $payment_type,
//                    $city,
//                    $application
//                );

                WebordersCancelAndRestorDoubleJob::dispatch($uid, $uid_Double, $city, $application, $order);

                $order_old_uid = $order->dispatching_order_uid;
                $order_new_uid = $orderNew;

                (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);
                Log::debug("Создан новый заказ с UID startAddCostCardBottomCreat: " . $orderNew);
                $messageAdmin = "Создан новый заказ" . json_encode($responseArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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
            case "Chernivtsi":
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


//    public function searchAutoOrderJob($uid) {
//        $uid = (new MemoryOrderChangeController)->show($uid);
//        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
//        if($orderweb) {
//            do {
//                if ($orderweb->closeReason == "-1") {
//                    if ($orderweb->auto != null) {
//                        self::sendAutoOrderResponse($orderweb);
//                        return ['status' => 'success', 'message' => $orderweb->auto];
//                    } else {
//                        sleep(5);
//                    }
//                } else {
//                    return ['status' => 'success', 'message' => 'Заказ снят'];
//                }
//
//            } while (true);
//        } else {
//            return ['status' => 'success', 'message' => 'Заказ не найден'];
//        }
//    }

//    public function sendAutoOrderResponse($orderweb) {
//        $costMap['dispatching_order_uid'] = $orderweb->dispatching_order_uid;
//        $costMap['order_cost'] = $orderweb->client_cost;
//        $costMap['currency'] = $orderweb->currency;
//        $costMap['routefrom'] = $orderweb->routefrom;
//        $costMap['routefromnumber'] = $orderweb->routefromnumber;
//        $costMap['routeto'] = $orderweb->routeto;
//        $costMap['to_number'] = $orderweb->routetonumber;
//
//        if($orderweb->required_time != null) {
//            $costMap['required_time'] = $orderweb->required_time;
//        } else {
//            $costMap['required_time'] = "1970-01-01T03:00";
//        }
//
//        $costMap['comment_info'] = $orderweb->comment_info;
//        $costMap['extra_charge_codes'] = $orderweb->extra_charge_codes;
//
//
//        // Проверка на дополнительные поля, если они существуют
//        $uid_history = Uid_history::where("uid_bonusOrderHold", $orderweb->dispatching_order_uid)->first();
//
//        if ($uid_history) {
//            // Если запись найдена, выходим из цикла
//            $costMap['dispatching_order_uid'] = $uid_history->uid_bonusOrder;
//            $dispatching_order_uid_Double = $uid_history->uid_doubleOrder;
//            $costMap['dispatching_order_uid_Double'] = $dispatching_order_uid_Double;
//        } else {
//            $costMap['dispatching_order_uid_Double'] = " ";
//        }
//
//        $email = $orderweb->email;
//
//        switch ($orderweb->comment) {
//            case "taxi_easy_ua_pas1":
//                $app = "PAS1";
//                break;
//            case "taxi_easy_ua_pas2":
//                $app = "PAS2";
//                break;
//            //case "PAS4":
//            default:
//                $app = "PAS4";
//                break;
//        }
//        (new PusherController)->sendAutoOrder($costMap, $app, $email);
//
//    }

    public function searchAutoOrderJob($uid)
    {
        Log::info('searchAutoOrderJob: Начало обработки', ['uid' => $uid]);

        // Валидация входного параметра
        if (empty($uid)) {
            Log::error('searchAutoOrderJob: Неверный UID', ['uid' => $uid]);
            return ['status' => 'error', 'message' => 'Неверный UID'];
        }

        try {
            // Получение обработанного UID
            $processedUid = (new MemoryOrderChangeController)->show($uid);
            if ($processedUid === null) {
                Log::warning('searchAutoOrderJob: Обработка UID вернула null', ['uid' => $uid]);
                return ['status' => 'error', 'message' => 'Обработка UID не удалась'];
            }
            Log::info('searchAutoOrderJob: UID обработан', ['uid' => $uid, 'processedUid' => $processedUid]);

            // Поиск заказа
            $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();
            if (!$orderweb) {
                Log::warning('searchAutoOrderJob: Заказ не найден', ['processedUid' => $processedUid]);
                return ['status' => 'success', 'message' => 'Заказ не найден'];
            }
            Log::info('searchAutoOrderJob: Заказ найден', [
                'processedUid' => $processedUid,
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'closeReason' => $orderweb->closeReason
            ]);


            do {
                Log::info('searchAutoOrderJob: Проверка условий цикла', [
                    'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                    'closeReason' => $orderweb->closeReason,
                    'auto' => $orderweb->auto
                ]);

                $messageAdmin = 'searchAutoOrderJob: dispatching_order_uid' . $orderweb->dispatching_order_uid . "\n closeReason" . $orderweb->closeReason;
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                if ($orderweb->closeReason == "-1" || $orderweb->closeReason == "101" ) {
                    if ($orderweb->auto != null) {
                        Log::info('searchAutoOrderJob: Найден автоматический заказ, отправка ответа', [
                            'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                            'auto' => $orderweb->auto
                        ]);
                        if($orderweb->closeReason == "-1" ) {
                            (new FCMController)->deleteDocumentFromFirestore($processedUid);
                            (new FCMController)->deleteDocumentFromFirestoreOrdersTakingCancel($processedUid);
                            (new FCMController)->deleteDocumentFromSectorFirestore($processedUid);
                            (new FCMController)->writeDocumentToHistoryFirestore($processedUid, "cancelled");

                        }
                        self::sendAutoOrderResponse($orderweb);

                        ProcessAutoOrder::dispatch($processedUid);

                        Log::info('searchAutoOrderJob: Ответ отправлен', [
                            'dispatching_order_uid' => $orderweb->dispatching_order_uid
                        ]);
                        return ['status' => 'success', 'message' => $orderweb->auto];
                    } else {
                        sleep(5);
                        $processedUid = (new MemoryOrderChangeController)->show($uid);
                        $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();
//                        $city = "OdessaTest";
                        $city = self::cityFinder($orderweb->city, $orderweb->server);

//                        $application = "PAS2";
                        $application = self::appFinder($orderweb->comment);

                        (new AndroidTestOSMController)->historyUIDStatusNew(
                            $processedUid,
                            $city,
                            $application
                        );
                    }
                } else {
                    Log::info('searchAutoOrderJob: Заказ снят', [
                        'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                        'closeReason' => $orderweb->closeReason
                    ]);
                    return ['status' => 'success', 'message' => 'Заказ снят'];
                }

            } while (true);
        } catch (\Exception $e) {
            Log::error('searchAutoOrderJob: Произошла ошибка', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'error', 'message' => 'Ошибка обработки: ' . $e->getMessage()];
        }
    }

    public function searchAutoOrderCardJob($uid)
    {
        Log::info('searchAutoOrderCardJob: Начало обработки', ['uid' => $uid]);

        // Валидация входного параметра
        if (empty($uid)) {
            Log::error('searchAutoOrderCardJob: Неверный UID', ['uid' => $uid]);
            return ['status' => 'error', 'message' => 'Неверный UID'];
        }

        try {
            // Получение обработанного UID
            $processedUid = (new MemoryOrderChangeController)->show($uid);
            if ($processedUid === null) {
                Log::warning('searchAutoOrderCardJob: Обработка UID вернула null', ['uid' => $uid]);
                return ['status' => 'error', 'message' => 'Обработка UID не удалась'];
            }
            Log::info('searchAutoOrderCardJob: UID обработан', ['uid' => $uid, 'processedUid' => $processedUid]);

            // Поиск заказа
            $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();
            if (!$orderweb) {
                Log::warning('searchAutoOrderCardJob: Заказ не найден', ['processedUid' => $processedUid]);
                return ['status' => 'success', 'message' => 'Заказ не найден'];
            }
            Log::info('searchAutoOrderCardJob: Заказ найден', [
                'processedUid' => $processedUid,
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'closeReason' => $orderweb->closeReason
            ]);

            $messageAdmin = 'searchAutoOrderCardJob: closeReason' . $orderweb->closeReason;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            do {
                Log::info('searchAutoOrderCardJob: Проверка условий цикла', [
                    'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                    'closeReason' => $orderweb->closeReason,
                    'auto' => $orderweb->auto
                ]);
                if ($orderweb->closeReason == "-1") {
                    if ($orderweb->auto != null) {
                        Log::info('searchAutoOrderCardJob: Найден автоматический заказ, отправка ответа', [
                            'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                            'auto' => $orderweb->auto
                        ]);
                        self::sendAutoOrderResponse($orderweb);
                        Log::info('searchAutoOrderCardJob: Ответ отправлен', [
                            'dispatching_order_uid' => $orderweb->dispatching_order_uid
                        ]);
                        return ['status' => 'success', 'message' => $orderweb->auto];
                    } else {
                        sleep(5);
                        $processedUid = (new MemoryOrderChangeController)->show($uid);
                        $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();
                        (new OrderStatusController)->getOrderStatusMessageResultPush($processedUid);
                    }
                } else {
                    Log::info('searchAutoOrderCardJob: Заказ снят', [
                        'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                        'closeReason' => $orderweb->closeReason
                    ]);
                    return ['status' => 'success', 'message' => 'Заказ снят'];
                }

            } while (true);
        } catch (\Exception $e) {
            Log::error('searchAutoOrderCardJob: Произошла ошибка', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'error', 'message' => 'Ошибка обработки: ' . $e->getMessage()];
        }
    }
    public function cityFinder(String $cityInp, String $serverInp): string {
//$order->city $order->server
        switch ($cityInp) {
            case "city_kiev":
                $city = "Kyiv City";
                break;
            case "city_cherkassy":
                $city = "Cherkasy Oblast";
                break;
            case "city_odessa":
                if($serverInp == "http://188.190.245.102:7303") {
                    $city = "OdessaTest";
                } else {
                    $city = "Odessa";
                }
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
                $city = "Chernivtsi";
                break;
            case "city_lutsk":
                $city = "Lutsk";
                break;
            default:
                $city = "all";
        }
        Log::debug("cityFinder $city");
        return $city;
    }


    public function appFinder(String $comment): string {
//        $orderweb->comment
        switch ($comment) {
            case 'taxi_easy_ua_pas1':
                $application = "PAS1";
                break;
            case 'taxi_easy_ua_pas2':
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
        }
        Log::debug("appFinder $application");
        return $application;
    }
    /**
     * Отправка ответа для автоматического заказа.
     *
     * @param Orderweb $orderweb Объект заказа
     * @return void
     */
    public function sendAutoOrderResponse($orderweb): void
    {
        Log::info('sendAutoOrderResponse started', [
            'dispatching_order_uid' => $orderweb->dispatching_order_uid
        ]);

        if (isset($orderweb->client_cost)) {
            $cost = $orderweb->client_cost  + $orderweb->attempt_20;
        } else {
            if (isset($orderweb->web_cost)) {
                $cost = $orderweb->web_cost + $orderweb->attempt_20;
            } else {
                $cost = 0;
            }
        }
        try {
            // Формирование costMap
            $costMap = [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
//                'order_cost' => $cost + $orderweb->attempt_20 + $orderweb->add_cost,
                'order_cost' => $cost,
                'currency' => $orderweb->currency,
                'routefrom' => $orderweb->routefrom,
                'routefromnumber' => $orderweb->routefromnumber,
                'routeto' => $orderweb->routeto,
                'to_number' => $orderweb->routetonumber,
                'required_time' => $orderweb->required_time ?? '1970-01-01T03:00',
                'comment_info' => $orderweb->comment_info,
                'extra_charge_codes' => $orderweb->extra_charge_codes,
            ];
            Log::info('sendAutoOrderResponse: costMap prepared', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'costMap' => $costMap
            ]);

            // Проверка на дополнительные поля
            $uid_history = Uid_history::where('uid_bonusOrderHold', $orderweb->dispatching_order_uid)->first();
            if ($uid_history) {
                $costMap['dispatching_order_uid'] = $uid_history->uid_bonusOrder;
                $costMap['dispatching_order_uid_Double'] = $uid_history->uid_doubleOrder;
                $costMap['pay_method'] = "wfp_payment";
                Log::info('sendAutoOrderResponse: Uid_history found', [
                    'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                    'uid_bonusOrder' => $uid_history->uid_bonusOrder,
                    'uid_doubleOrder' => $uid_history->uid_doubleOrder,
                    'pay_method' => $uid_history->uid_doubleOrder

                ]);
            } else {
                $costMap['dispatching_order_uid_Double'] = ' ';
                Log::info('sendAutoOrderResponse: Uid_history not found', [
                    'dispatching_order_uid' => $orderweb->dispatching_order_uid
                ]);
            }

            // Определение приложения
            $email = $orderweb->email;
            switch ($orderweb->comment) {
                case "taxi_easy_ua_pas1":
                    $app = "PAS1";
                    break;
                case "taxi_easy_ua_pas2":
                    $app = "PAS2";
                    break;
                //case "PAS4":
                default:
                    $app = "PAS4";
                    break;
            }
            Log::info('sendAutoOrderResponse: App determined', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'app' => $app,
                'email' => $email
            ]);

            // Отправка данных через PusherController
            (new PusherController)->sendAutoOrder($costMap, $app, $email);

            // Отправка данных через FCMController
            $user = User::where("email", $orderweb->email)->first();
            if(isset ($user)) {

                if ($orderweb->closeReason !== "-1") {
                    $storedData = $orderweb->auto;

                    $dataDriver = json_decode($storedData, true);
//                            $name = $dataDriver["name"];
                    $color = $dataDriver["color"];
                    $brand = $dataDriver["brand"];
                    $model = $dataDriver["model"];
                    $number = $dataDriver["number"];
                    $auto = "$number, $color  $brand $model";
                } else{
                    $auto = $orderweb->auto;
                }

                $body = $auto;
                (new FCMController)->sendNotificationAuto($body, $app, $user->id);
            }
            Log::info('sendAutoOrderResponse: Auto order sent successfully', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid
            ]);

        } catch (\Exception $e) {
            Log::error('sendAutoOrderResponse: Exception occurred', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Перебрасываем исключение для обработки выше
        }
    }

    public function sendAutoOrderMyVodResponse($orderweb): void
    {
        Log::info('sendAutoOrderResponse started', [
            'dispatching_order_uid' => $orderweb->dispatching_order_uid
        ]);

        if (isset($orderweb->client_cost)) {
            $cost = $orderweb->client_cost  + $orderweb->attempt_20;
        } else {
            if (isset($orderweb->web_cost)) {
                $cost = $orderweb->web_cost + $orderweb->attempt_20;
            } else {
                $cost = 0;
            }
        }
        try {
            // Формирование costMap
            $costMap = [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
//                'order_cost' => $cost + $orderweb->attempt_20 + $orderweb->add_cost,
                'order_cost' => $cost,
                'currency' => $orderweb->currency,
                'routefrom' => $orderweb->routefrom,
                'routefromnumber' => $orderweb->routefromnumber,
                'routeto' => $orderweb->routeto,
                'to_number' => $orderweb->routetonumber,
                'required_time' => $orderweb->required_time ?? '1970-01-01T03:00',
                'comment_info' => $orderweb->comment_info,
                'extra_charge_codes' => $orderweb->extra_charge_codes,
            ];
            Log::info('sendAutoOrderResponse: costMap prepared', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'costMap' => $costMap
            ]);

            // Проверка на дополнительные поля
            $uid_history = Uid_history::where('uid_bonusOrderHold', $orderweb->dispatching_order_uid)->first();
            if ($uid_history) {
                $costMap['dispatching_order_uid'] = $uid_history->uid_bonusOrder;
                $costMap['dispatching_order_uid_Double'] = $uid_history->uid_doubleOrder;
                $costMap['pay_method'] = "wfp_payment";
                Log::info('sendAutoOrderResponse: Uid_history found', [
                    'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                    'uid_bonusOrder' => $uid_history->uid_bonusOrder,
                    'uid_doubleOrder' => $uid_history->uid_doubleOrder,
                    'pay_method' => $uid_history->uid_doubleOrder

                ]);
            } else {
                $costMap['dispatching_order_uid_Double'] = ' ';
                Log::info('sendAutoOrderResponse: Uid_history not found', [
                    'dispatching_order_uid' => $orderweb->dispatching_order_uid
                ]);
            }

            // Определение приложения
            $email = $orderweb->email;
            switch ($orderweb->comment) {
                case "taxi_easy_ua_pas1":
                    $app = "PAS1";
                    break;
                case "taxi_easy_ua_pas2":
                    $app = "PAS2";
                    break;
                //case "PAS4":
                default:
                    $app = "PAS4";
                    break;
            }
            Log::info('sendAutoOrderResponse: App determined', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'app' => $app,
                'email' => $email
            ]);

            // Отправка данных через PusherController
            (new PusherController)->sendAutoOrder($costMap, $app, $email);

            // Отправка данных через FCMController
            $user = User::where("email", $orderweb->email)->first();
//            if(isset ($user)) {
//
//                if ($orderweb->closeReason !== "-1") {
//                    $storedData = $orderweb->auto;
//
//                    $dataDriver = json_decode($storedData, true);
////                            $name = $dataDriver["name"];
//                    $color = $dataDriver["color"];
//                    $brand = $dataDriver["brand"];
//                    $model = $dataDriver["model"];
//                    $number = $dataDriver["number"];
//                    $auto = "$number, $color  $brand $model";
//                } else{
//                    $auto = $orderweb->auto;
//                }
//
//                $body = $auto;
//                (new FCMController)->sendNotificationAuto($body, $app, $user->id);
//            }
            Log::info('sendAutoOrderResponse: Auto order sent successfully', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid
            ]);

        } catch (\Exception $e) {
            Log::error('sendAutoOrderResponse: Exception occurred', [
                'dispatching_order_uid' => $orderweb->dispatching_order_uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Перебрасываем исключение для обработки выше
        }
    }
     public function testCache () {
         Cache::put('test_key_redis', 'test_value_redis', 60); // Сохранить на 60 second
         $value = Cache::get('test_key_redis');
         dd($value); // Должно вывести 'test_value'
     }



    public function isCurrentTimeInRange(): bool
    {
        // Устанавливаем временную зону Киева
        $now = Carbon::now('Europe/Kiev');

        // Получаем start_time и end_time из конфига
        $startTime = Carbon::createFromFormat('H:i', config('app.start_time'), 'Europe/Kiev');
        $endTime = Carbon::createFromFormat('H:i', config('app.end_time'), 'Europe/Kiev');

        // Проверяем, находится ли текущее время в диапазоне
        $isInRange = $now->between($startTime, $endTime);

        // Формируем сообщение
        $messageAdmin = sprintf(
            "Current Kyiv time: %s\nTime range: %s - %s\nIs in range: %s",
            $now->format('H:i'),
            $startTime->format('H:i'),
            $endTime->format('H:i'),
            $isInRange ? 'Yes' : 'No'
        );

        // Логируем сообщение
        Log::debug($messageAdmin);

        // Отправляем сообщение через Telegram
//        $alarmMessage = new TelegramController();
//        $alarmMessage->sendMeMessage($messageAdmin);

        return $isInRange;
    }

}
