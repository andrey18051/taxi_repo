<?php

namespace App\Http\Controllers;

use App\Jobs\StartNewProcessExecution;
use App\Models\DoubleOrder;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Models\WfpInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;

class DailyTaskController extends Controller
{

    /**
     * Retry a database query multiple times with a delay.
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    private function retryQuery(callable $callback)
    {
        $attempt = 0;
        do {
            try {
                // Проверяем соединение перед выполнением запроса
                DB::connection()->getPdo();
                return $callback();
            } catch (Exception $e) {
                if (++$attempt >= 30) {
                    throw $e;
                }
                usleep(5000 * 1000); // задержка в миллисекундах

                // Пытаемся повторно подключиться к базе данных
                DB::connection('mysql')->reconnect();
            }
        } while (true);
    }

    public function sentTaskMessage($message)
    {
        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($message);
            $alarmMessage->sendMeMessage($message);
            Log::info("sentTaskMessage: $message");
        } catch (Exception $e) {
            Log::error("sentTaskMessage: Ошибка отправки в телеграмм");
        };
    }

    /**
     *
     */



    public function restartProcessExecutionStatus()
    {
        try {
            $doubleOrders = $this->retryQuery(function () {
                return DoubleOrder::all();
            });

            $message = "Перезапуск сервера";
            Log::info("restartProcessExecutionStatus: $message");
            self::sentTaskMessage($message);

            if ($doubleOrders->isEmpty()) {
                $message = "Нет активных задач опроса для перезапуска";
                Log::info("restartProcessExecutionStatus: $message");
                self::sentTaskMessage($message);
                return;
            }

            foreach ($doubleOrders as $order) {
                $responseBonusStrArr = json_decode($order->responseBonusStr, true);
                $uid = $responseBonusStrArr['dispatching_order_uid'] ?? 'неизвестен';

                // Проверяем ключ в Redis, например "double_order_processing:{id}"
                $redisKey = "double_order_processing_key:" . $order->id;

                if (Redis::exists($redisKey)) {
                    Log::info("restartProcessExecutionStatus: Задача для заказа $uid (ID {$order->id}) уже запущена, пропускаем");
                    continue; // задача уже в очереди или выполняется
                }

                // Помечаем, что задача запущена
                // Установка ключа без срока жизни (будет храниться бессрочно)
                Redis::set($redisKey, true);


                $message = "Запущен заново процесс опроса статусов заказа: $uid (ID: {$order->id})";
                Log::info("restartProcessExecutionStatus: $message");
                self::sentTaskMessage($message);

                dispatch(new StartNewProcessExecution($order->id))
                    ->onQueue('high');

            }
        } catch (Exception $e) {
            Log::error("Ошибка при выполнении запроса: " . $e->getMessage());
            self::sentTaskMessage("Ошибка при выполнении запроса: " . $e->getMessage());
        }
    }


    /**
     * Пересмотр холдов
     */
    public function orderCardWfpReviewTask()
    {
        $currentTime = Carbon::now();

// Фильтрация записей
        $wfpInvoices = WfpInvoice::where(function ($query) {
            $query->where('transactionStatus', 'WaitingAuthComplete') // Все записи с WaitingAuthComplete
            ->orWhere(function ($subQuery) { // Только записи с InProcessing, обновленные менее минуты назад
                $subQuery->where('transactionStatus', 'InProcessing')
                    ->where('updated_at', '>=', Carbon::now()->subMinute());
            });
        })->get();

        if (!$wfpInvoices->isEmpty()) {
            Log::info("orderCardWfpReviewTask: Начинаем обработку " . $wfpInvoices->count() . " инвойсов WFP", $wfpInvoices->toArray());

            $bonusOrderHold = null;
            $processedCount = 0;

            foreach ($wfpInvoices->toArray() as $index => $value) {
                $processedCount++;
                Log::info("orderCardWfpReviewTask: Обработка инвойса #{$processedCount}", [
                    'index' => $index,
                    'value' => $value
                ]);

                $uid = $value['dispatching_order_uid'];
                Log::info("orderCardWfpReviewTask: Исходный UID из инвойса: {$uid}");

                $uid = (new MemoryOrderChangeController)->show($uid);
                Log::info("orderCardWfpReviewTask: UID после MemoryOrderChangeController: {$uid}");

                $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

                if (!$orderweb) {
                    Log::warning("orderCardWfpReviewTask: Orderweb не найден для UID: {$uid}");
                    continue;
                }

                Log::info("orderCardWfpReviewTask: Найден Orderweb", [
                    'uid' => $orderweb->dispatching_order_uid,
                    'server' => $orderweb->server,
                    'closeReason' => $orderweb->closeReason,
                    'comment' => $orderweb->comment,
                    'city' => $orderweb->city
                ]);

                if ($orderweb->server == "my_server_api" || in_array($orderweb->closeReason, ['104'])) {
                    Log::info("orderCardWfpReviewTask: Условие my_server_api или closeReason=104 выполнено");

                    $wfpInvoicesForOrder = WfpInvoice::where('dispatching_order_uid', $uid)->get();
                    Log::info("orderCardWfpReviewTask: Найдено инвойсов WFP для этого заказа: " . $wfpInvoicesForOrder->count());

                    // Определяем приложение
                    $application = "PAS4"; // значение по умолчанию
                    switch ($orderweb->comment) {
                        case "taxi_easy_ua_pas1":
                            $application = "PAS1";
                            break;
                        case "taxi_easy_ua_pas2":
                            $application = "PAS2";
                            break;
                        case "taxi_easy_ua_pas4":
                            $application = "PAS4";
                            break;
                        default:
                            $application = "PAS5";
                            break;
                    }
                    Log::info("orderCardWfpReviewTask: Приложение определено как: {$application}");

                    // Определяем город
                    $city = "all"; // значение по умолчанию
                    switch ($orderweb->city) {
                        case "city_kiev": $city = "Kyiv City"; break;
                        case "city_cherkassy": $city = "Cherkasy Oblast"; break;
                        case "city_odessa":
                            if($orderweb->server == "http://188.190.245.102:7303" || $orderweb->server == "my_server_api") {
                                $city = "OdessaTest";
                            } else {
                                $city = "Odessa";
                            }
                            break;
                        case "city_zaporizhzhia": $city = "Zaporizhzhia"; break;
                        case "city_dnipro": $city = "Dnipropetrovsk Oblast"; break;
                        case "city_lviv": $city = "Lviv"; break;
                        case "city_ivano_frankivsk": $city = "Ivano_frankivsk"; break;
                        case "city_vinnytsia": $city = "Vinnytsia"; break;
                        case "city_poltava": $city = "Poltava"; break;
                        case "city_sumy": $city = "Sumy"; break;
                        case "city_kharkiv": $city = "Kharkiv"; break;
                        case "city_chernihiv": $city = "Chernihiv"; break;
                        case "city_rivne": $city = "Rivne"; break;
                        case "city_ternopil": $city = "Ternopil"; break;
                        case "city_khmelnytskyi": $city = "Khmelnytskyi"; break;
                        case "city_zakarpattya": $city = "Zakarpattya"; break;
                        case "city_zhytomyr": $city = "Zhytomyr"; break;
                        case "city_kropyvnytskyi": $city = "Kropyvnytskyi"; break;
                        case "city_mykolaiv": $city = "Mykolaiv"; break;
                        case "city_chernivtsi": $city = "Chernivtsi"; break;
                        case "city_lutsk": $city = "Lutsk"; break;
                        default: $city = "all";
                    }
                    Log::info("orderCardWfpReviewTask: Город определен как: {$city}");

                    // Обработка в зависимости от closeReason
                    switch ($orderweb->closeReason) {
                        case "1":
                            Log::info("orderCardWfpReviewTask: closeReason=1 - выполняем refund");
                            if ($wfpInvoicesForOrder != null && !$wfpInvoicesForOrder->isEmpty()) {
                                foreach ($wfpInvoicesForOrder as $invoiceIndex => $valueInv) {
                                    Log::info("orderCardWfpReviewTask: Обработка инвойса для refund #" . ($invoiceIndex + 1), [
                                        'orderReference' => $valueInv->orderReference,
                                        'amount' => $valueInv->amount
                                    ]);

                                    $orderReference = $valueInv->orderReference;
                                    $amount = $valueInv->amount;

                                    try {
                                        $result = (new WfpController)->refund(
                                            $application,
                                            $city,
                                            $orderReference,
                                            $amount
                                        );
                                        Log::info("orderCardWfpReviewTask: Refund выполнен успешно", [
                                            'orderReference' => $orderReference,
                                            'amount' => $amount,
                                            'result' => $result
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error("orderCardWfpReviewTask: Ошибка при refund", [
                                            'orderReference' => $orderReference,
                                            'amount' => $amount,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            } else {
                                Log::warning("orderCardWfpReviewTask: Нет инвойсов для refund при closeReason=1");
                            }
                            break;

                        case "104":
                            Log::info("orderCardWfpReviewTask: closeReason=104 - выполняем settle");
                            if ($wfpInvoicesForOrder != null && !$wfpInvoicesForOrder->isEmpty()) {
                                foreach ($wfpInvoicesForOrder as $invoiceIndex => $valueInv) {
                                    Log::info("orderCardWfpReviewTask: Обработка инвойса для settle #" . ($invoiceIndex + 1), [
                                        'orderReference' => $valueInv->orderReference,
                                        'amount' => $valueInv->amount
                                    ]);

                                    $orderReference = $valueInv->orderReference;
                                    $amount = $valueInv->amount;

                                    try {
                                        $result = (new WfpController)->settle(
                                            $application,
                                            $city,
                                            $orderReference,
                                            $amount
                                        );
                                        Log::info("orderCardWfpReviewTask: Settle выполнен успешно", [
                                            'orderReference' => $orderReference,
                                            'amount' => $amount,
                                            'result' => $result
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error("orderCardWfpReviewTask: Ошибка при settle", [
                                            'orderReference' => $orderReference,
                                            'amount' => $amount,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            } else {
                                Log::warning("orderCardWfpReviewTask: Нет инвойсов для settle при closeReason=104");
                            }
                            break;

                        default:
                            Log::info("orderCardWfpReviewTask: closeReason={$orderweb->closeReason} - не требует обработки WFP");
                            break;
                    }

                } else if ($orderweb->server != "my_server_api" && !in_array($orderweb->closeReason, ['100', '101', '102', '103', '104'])) {
                    Log::info("orderCardWfpReviewTask: Условие для orderReview выполнено (не my_server_api и не closeReason 100-104)");

                    $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();

                    if (!$uid_history) {
                        Log::info("orderCardWfpReviewTask: Uid_history не найден по uid_bonusOrderHold: {$uid}");
                        $uid_history = Uid_history::where("uid_bonusOrder", $uid)->first();
                    }

                    if ($uid_history) {
                        Log::info("orderCardWfpReviewTask: Найден Uid_history", [
                            'id' => $uid_history->id,
                            'uid_bonusOrder' => $uid_history->uid_bonusOrder,
                            'uid_doubleOrder' => $uid_history->uid_doubleOrder,
                            'uid_bonusOrderHold' => $uid_history->uid_bonusOrderHold
                        ]);

                        if ($bonusOrderHold != $uid_history->uid_bonusOrderHold) {
                            $bonusOrder = $uid_history->uid_bonusOrder;
                            $doubleOrder = $uid_history->uid_doubleOrder;
                            $bonusOrderHold = $uid_history->uid_bonusOrderHold;

                            Log::info("orderCardWfpReviewTask: Вызов orderReview с параметрами", [
                                'bonusOrder' => $bonusOrder,
                                'doubleOrder' => $doubleOrder,
                                'bonusOrderHold' => $bonusOrderHold,
                                'previous_bonusOrderHold' => $bonusOrderHold
                            ]);

                            try {
                                $result = (new UniversalAndroidFunctionController)->orderReview(
                                    $bonusOrder,
                                    $doubleOrder,
                                    $bonusOrderHold
                                );
                                Log::info("orderCardWfpReviewTask: orderReview выполнен успешно", ['result' => $result]);
                            } catch (\Exception $e) {
                                Log::error("orderCardWfpReviewTask: Ошибка при orderReview", [
                                    'bonusOrder' => $bonusOrder,
                                    'doubleOrder' => $doubleOrder,
                                    'bonusOrderHold' => $bonusOrderHold,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        } else {
                            Log::info("orderCardWfpReviewTask: Пропускаем - bonusOrderHold уже обработан: {$bonusOrderHold}");
                        }

                    } else {
                        Log::info("orderCardWfpReviewTask: Uid_history не найден, вызываем orderReview с одним UID: {$uid}");

                        try {
                            $result = (new UniversalAndroidFunctionController)->orderReview(
                                $uid,
                                $uid,
                                $uid
                            );
                            Log::info("orderCardWfpReviewTask: orderReview выполнен успешно (без Uid_history)", ['result' => $result]);
                        } catch (\Exception $e) {
                            Log::error("orderCardWfpReviewTask: Ошибка при orderReview (без Uid_history)", [
                                'uid' => $uid,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } else {
                    Log::info("orderCardWfpReviewTask: Условие не выполнено - пропускаем обработку", [
                        'server' => $orderweb->server,
                        'closeReason' => $orderweb->closeReason
                    ]);
                }
            }

            Log::info("orderCardWfpReviewTask: Обработка завершена. Всего обработано инвойсов: {$processedCount}");

        } else {
            $message = "orderCardWfpReviewTask: Нет холдов WFP для пересмотра";
            Log::info($message);
        }
    }
    public function orderBonusReviewTask()
    {
        $orderwebs = Orderweb::where('pay_system', 'bonus_payment')
                ->where('bonus_status', 'hold')->get();

        if (!$orderwebs->isEmpty()) {
            Log::info("orderBonusReviewTask", $orderwebs->toArray());

            foreach ($orderwebs->toArray() as $value) {
                $uid = $value['dispatching_order_uid'];

                $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
                if ($uid_history != null) {
                    Log::info("uid_history $uid_history");
                    $bonusOrder = $uid_history->uid_bonusOrder;
                    $doubleOrder = $uid_history->uid_doubleOrder;
                    $bonusOrderHold  = $uid_history->uid_bonusOrder;
                    Log::info("uid_history bonusOrder $bonusOrder");
                    Log::info("uid_history doubleOrder $doubleOrder");
                    Log::info("uid_history bonusOrderHold $bonusOrderHold");

                } else {
                    $message = "Оператор проверьте холд бонусов: " .  $uid . "для пересмотра";

                    self::sentTaskMessage($message);

                    Log::info("orderCardWfpReviewTask $message");
                    $bonusOrder = $uid;
                    $doubleOrder = $uid;
                    $bonusOrderHold  = $uid;
                    Log::info("uid_history bonusOrder $bonusOrder");
                    Log::info("uid_history doubleOrder $doubleOrder");
                    Log::info("uid_history bonusOrderHold $bonusOrderHold");
                }
                (new UniversalAndroidFunctionController)->orderReview(
                    $bonusOrder,
                    $doubleOrder,
                    $bonusOrderHold
                );
            }
        } else {
            $message = "orderBonusReviewTask нет холдов бонусов для пересмотра";
//            self::sentTaskMessage($message);
            Log::info("orderReviewTask $message");
        }
    }

    public function verifyVersionApiTaskPas1() {
        $message = "ПАС 1: Запущена задача проверки версии серверов такси";
        self::sentTaskMessage($message);
        $client = new Client(); // Guzzle HTTP Client
        $updatedServers = [];

        // Получение всех адресов из таблицы city_pas_2_s
        $servers = DB::table('city_pas_1_s')->get(['id', 'address', 'versionApi']);

        foreach ($servers as $server) {
            try {
                $url = "http://{$server->address}/api/version";

                // Выполнение запроса к API с заголовком Accept: application/json
                $response = $client->get($url, [
                    'headers' => ['Accept' => 'application/json'],
                    'timeout' => 5, // Таймаут на случай, если сервер не отвечает
                ]);

                $body = $response->getBody()->getContents();
//                self::sentTaskMessage("Полученный ответ от {$server->address}: " . $body);
                // Декодируем JSON-ответ в массив
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE || !isset($data['version'])) {
                    $message = "Не удалось получить версию из ответа для сервера {$server->address}. Ошибка JSON: " . json_last_error_msg();
                    self::sentTaskMessage($message);
                    continue;
                }

// Извлечение версии
                $newVersion = $data['version'];
//                self::sentTaskMessage("Извлеченная версия для {$server->address}: " . $newVersion);

                // Проверка и обновление версии
                if ($server->versionApi !== $newVersion) {
                    DB::table('city_pas_1_s')
                        ->where('id', $server->id)
                        ->update(['versionApi' => $newVersion]);

                    $updatedServers[] = [
                        'address' => $server->address,
                        'oldVersion' => $server->versionApi,
                        'newVersion' => $newVersion,
                    ];
                }
            } catch (\Exception $e) {
                $message = "Ошибка при обработке сервера {$server->address}: {$e->getMessage()}";
                self::sentTaskMessage($message);
            }
        }

        // Формирование отчета
        if (!empty($updatedServers)) {
            $message = "ПАС 1: Изменены версии для серверов:\n";
            foreach ($updatedServers as $server) {
                $message .= "Адрес: {$server['address']}, старая версия: {$server['oldVersion']}, новая версия: {$server['newVersion']}\n";
            }

            // Отправка отчета
        } else {

            $message = 'ПАС 1: Все версии актуальны, изменений не требуется.';
        }
        self::sentTaskMessage($message);
    }
    public function verifyVersionApiTaskPas2() {
        $message = "ПАС 2: Запущена задача проверки версии серверов такси";
        self::sentTaskMessage($message);
        $client = new Client(); // Guzzle HTTP Client
        $updatedServers = [];

        // Получение всех адресов из таблицы city_pas_2_s
        $servers = DB::table('city_pas_2_s')->get(['id', 'address', 'versionApi']);

        foreach ($servers as $server) {
            try {
                $url = "http://{$server->address}/api/version";

                // Выполнение запроса к API с заголовком Accept: application/json
                $response = $client->get($url, [
                    'headers' => ['Accept' => 'application/json'],
                    'timeout' => 5, // Таймаут на случай, если сервер не отвечает
                ]);

                $body = $response->getBody()->getContents();
//                self::sentTaskMessage("Полученный ответ от {$server->address}: " . $body);

                // Декодируем JSON-ответ в массив
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE || !isset($data['version'])) {
                    $message = "Не удалось получить версию из ответа для сервера {$server->address}. Ошибка JSON: " . json_last_error_msg();
                    self::sentTaskMessage($message);
                    continue;
                }

// Извлечение версии
                $newVersion = $data['version'];
//                self::sentTaskMessage("Извлеченная версия для {$server->address}: " . $newVersion);

                // Проверка и обновление версии
                if ($server->versionApi !== $newVersion) {
                    DB::table('city_pas_2_s')
                        ->where('id', $server->id)
                        ->update(['versionApi' => $newVersion]);

                    $updatedServers[] = [
                        'address' => $server->address,
                        'oldVersion' => $server->versionApi,
                        'newVersion' => $newVersion,
                    ];
                }
            } catch (\Exception $e) {
                $message = "Ошибка при обработке сервера {$server->address}: {$e->getMessage()}";
                self::sentTaskMessage($message);
            }
        }

        // Формирование отчета
        if (!empty($updatedServers)) {
            $message = "ПАС 2: Изменены версии для серверов:\n";
            foreach ($updatedServers as $server) {
                $message .= "Адрес: {$server['address']}, старая версия: {$server['oldVersion']}, новая версия: {$server['newVersion']}\n";
            }

            // Отправка отчета
        } else {

            $message = 'ПАС 2: Все версии актуальны, изменений не требуется.';
        }
        self::sentTaskMessage($message);
    }
    public function verifyVersionApiTaskPas4() {
        $message = "ПАС 4: Запущена задача проверки версии серверов такси";
        self::sentTaskMessage($message);
        $client = new Client(); // Guzzle HTTP Client
        $updatedServers = [];

        // Получение всех адресов из таблицы city_pas_2_s
        $servers = DB::table('city_pas_4_s')->get(['id', 'address', 'versionApi']);

        foreach ($servers as $server) {
            try {
                $url = "http://{$server->address}/api/version";

                // Выполнение запроса к API с заголовком Accept: application/json
                $response = $client->get($url, [
                    'headers' => ['Accept' => 'application/json'],
                    'timeout' => 5, // Таймаут на случай, если сервер не отвечает
                ]);

                $body = $response->getBody()->getContents();
//                self::sentTaskMessage("Полученный ответ от {$server->address}: " . $body);

                // Декодируем JSON-ответ в массив
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE || !isset($data['version'])) {
                    $message = "Не удалось получить версию из ответа для сервера {$server->address}. Ошибка JSON: " . json_last_error_msg();
                    self::sentTaskMessage($message);
                    continue;
                }

// Извлечение версии
                $newVersion = $data['version'];
//                self::sentTaskMessage("Извлеченная версия для {$server->address}: " . $newVersion);

                // Проверка и обновление версии
                if ($server->versionApi !== $newVersion) {
                    DB::table('city_pas_4_s')
                        ->where('id', $server->id)
                        ->update(['versionApi' => $newVersion]);

                    $updatedServers[] = [
                        'address' => $server->address,
                        'oldVersion' => $server->versionApi,
                        'newVersion' => $newVersion,
                    ];
                }
            } catch (\Exception $e) {
                $message = "Ошибка при обработке сервера {$server->address}: {$e->getMessage()}";
                self::sentTaskMessage($message);
            }
        }

        // Формирование отчета
        if (!empty($updatedServers)) {
            $message = "ПАС 4: Изменены версии для серверов:\n";
            foreach ($updatedServers as $server) {
                $message .= "Адрес: {$server['address']}, старая версия: {$server['oldVersion']}, новая версия: {$server['newVersion']}\n";
            }

            // Отправка отчета
        } else {

            $message = 'ПАС 4: Все версии актуальны, изменений не требуется.';
        }
    self::sentTaskMessage($message);
}
    public function verifyVersionApiTaskPas5() {
        $message = "ПАС 5: Запущена задача проверки версии серверов такси";
        self::sentTaskMessage($message);
        $client = new Client(); // Guzzle HTTP Client
        $updatedServers = [];

        // Получение всех адресов из таблицы city_pas_2_s
        $servers = DB::table('city_pas_5_s')->get(['id', 'address', 'versionApi']);

        foreach ($servers as $server) {
            try {
                $url = "http://{$server->address}/api/version";

                // Выполнение запроса к API с заголовком Accept: application/json
                $response = $client->get($url, [
                    'headers' => ['Accept' => 'application/json'],
                    'timeout' => 5, // Таймаут на случай, если сервер не отвечает
                ]);

                $body = $response->getBody()->getContents();
//                self::sentTaskMessage("Полученный ответ от {$server->address}: " . $body);

                // Декодируем JSON-ответ в массив
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE || !isset($data['version'])) {
                    $message = "Не удалось получить версию из ответа для сервера {$server->address}. Ошибка JSON: " . json_last_error_msg();
                    self::sentTaskMessage($message);
                    continue;
                }

// Извлечение версии
                $newVersion = $data['version'];
//                self::sentTaskMessage("Извлеченная версия для {$server->address}: " . $newVersion);

                // Проверка и обновление версии
                if ($server->versionApi !== $newVersion) {
                    DB::table('city_pas_5_s')
                        ->where('id', $server->id)
                        ->update(['versionApi' => $newVersion]);

                    $updatedServers[] = [
                        'address' => $server->address,
                        'oldVersion' => $server->versionApi,
                        'newVersion' => $newVersion,
                    ];
                }
            } catch (\Exception $e) {
                $message = "Ошибка при обработке сервера {$server->address}: {$e->getMessage()}";
                self::sentTaskMessage($message);
            }
        }

        // Формирование отчета
        if (!empty($updatedServers)) {
            $message = "ПАС 5: Изменены версии для серверов:\n";
            foreach ($updatedServers as $server) {
                $message .= "Адрес: {$server['address']}, старая версия: {$server['oldVersion']}, новая версия: {$server['newVersion']}\n";
            }

            // Отправка отчета
        } else {

            $message = 'ПАС 5: Все версии актуальны, изменений не требуется.';
        }
    self::sentTaskMessage($message);
}


}
