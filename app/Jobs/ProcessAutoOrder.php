<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\Orderweb;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use PharIo\Version\Exception;

class ProcessAutoOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;
    public $tries = 1;    // Только одна попытка
    public $timeout = 0;  // Бесконечное время выполнения

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            Log::info('ProcessAutoOrder: Запуск задачи', ['uid' => $this->uid]);

            $processedUid = (new MemoryOrderChangeController)->show($this->uid);
            $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();

            if (!$orderweb) {
                Log::warning('ProcessAutoOrder: Заказ не найден в базе', ['uid' => $processedUid]);
                return;
            }

            switch ($orderweb->comment) {
                case "taxi_easy_ua_pas1":
                    $application = config("app.X-WO-API-APP-ID-PAS1");
                    break;
                case "taxi_easy_ua_pas2":
                    $application = config("app.X-WO-API-APP-ID-PAS2");
                    break;
                default:
                    $application = config("app.X-WO-API-APP-ID-PAS4");
                    break;
            }

            $city = (new UniversalAndroidFunctionController)->cityFinder($orderweb->city, $orderweb->server);
            $connectAPI = $orderweb->server;

            $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);

            $header = [
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" => (new AndroidTestOSMController)->identificationId($application),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)
                    ->apiVersionApp($city, $connectAPI, $application),
            ];


            $timeSleep = config("app.timeSleepForStatusUpdate");

            while (true) {
                // Обновляем данные о заказе
                $processedUid = (new MemoryOrderChangeController)->show($processedUid);

                $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();
                if (!$orderweb) {
                    Log::warning('ProcessAutoOrder: Заказ исчез из базы', ['uid' => $processedUid]);
                    return;
                }

                $cacheKey = "order_status_" . $processedUid;
                $url = $connectAPI . '/api/weborders/' . $processedUid;

                // Новый ответ от API
                $newResponseArr = (new UniversalAndroidFunctionController)->getStatus($header, $url);

                // Получаем старый ответ из кэша
                $oldResponseArr = Cache::get($cacheKey);

                // Если данные изменились — обновляем кэш
                if ($oldResponseArr !== $newResponseArr) {
                    Cache::put($cacheKey, $newResponseArr, 600); // сохраняем на 10 минут
                    Log::info('ProcessAutoOrder: Обновлены данные в кэше', ['uid' => $processedUid]);
                } else {
                    Log::debug('ProcessAutoOrder: Данные не изменились, используем кэш', [
                        'uid' => $processedUid,
                        'cached_response' => $oldResponseArr
                    ]);
                }

                // Работаем с актуальными данными
                $responseArr = $newResponseArr;

                // Если нет данных об авто
                if (!isset($responseArr["order_car_info"])) {
                    Log::info('ProcessAutoOrder: auto == null ', ['uid' => $this->uid]);

                    $orderweb->auto = null;

                } else {
                    $orderweb->auto = $responseArr["order_car_info"];

                    $orderweb->closeReason = $responseArr["close_reason"];
                }
                $orderweb->save();

                // Проверка на закрытие заказа
                if (isset($responseArr["close_reason"]) && $responseArr["close_reason"] != -1) {
                    Log::info('ProcessAutoOrder: Заказ отменён/закрыт', [
                        'uid' => $this->uid,
                        'close_reason' => $responseArr["close_reason"]
                    ]);

                    (new FCMController)->deleteDocumentFromFirestore($this->uid);
                    (new FCMController)->deleteDocumentFromFirestoreOrdersTakingCancel($this->uid);
                    (new FCMController)->deleteDocumentFromSectorFirestore($this->uid);
                    (new FCMController)->writeDocumentToHistoryFirestore($this->uid, "cancelled");

    //                Cache::forget($cacheKey);
                    return;
                }

                sleep($timeSleep);
            }
        } catch (Exception $e) {
            Log::error("Ошибка в SearchAutoOrderJob", ['message' => $e->getMessage()]);
            // Можно вернуть release() для повторной попытки
            $this->release(30); // повторить через 30 секунд
        }
    }
}
