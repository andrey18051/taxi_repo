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
                $redisKey = "double_order_processing:" . $order->id;

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

                StartNewProcessExecution::dispatch($order->id);
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
            Log::info("orderCardWfpReviewTask WfpInvoice", $wfpInvoices->toArray());
            $bonusOrderHold = null;
            foreach ($wfpInvoices->toArray() as $value) {
                $uid = $value['dispatching_order_uid'];
                $uid = (new MemoryOrderChangeController)->show($uid);
                $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
                if ($uid_history != null) {
                    Log::info("uid_history $uid_history");

                    if($bonusOrderHold  != $uid_history->uid_bonusOrderHold) {
                        $bonusOrder = $uid_history->uid_bonusOrder;
                        $doubleOrder = $uid_history->uid_doubleOrder;
                        $bonusOrderHold  = $uid_history->uid_bonusOrderHold;
                        Log::info("uid_history bonusOrder $bonusOrder");
                        Log::info("uid_history doubleOrder $doubleOrder");
                        Log::info("uid_history bonusOrderHold $bonusOrderHold");

                        (new UniversalAndroidFunctionController)->orderReview(
                            $bonusOrder,
                            $doubleOrder,
                            $bonusOrderHold
                        );
                    }

                } else {
                    (new UniversalAndroidFunctionController)->orderReview(
                        $uid,
                        $uid,
                        $uid
                    );
                }
            }
        } else {
            $message = "orderCardWfpReviewTask нет холдов WFP для пересмотра";

            Log::info("orderCardWfpReviewTask $message");
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
    }  public function verifyVersionApiTaskPas4() {
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


}
