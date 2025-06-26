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

class ProcessAutoOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1860; // Laravel принудительно завершит Job через 31 минуту (страховка)
    protected $uid;

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function handle(): void
    {
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
            "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application),
        ];

        $startTime = time();
        $maxDuration = 30 * 60; // 30 минут

        while (true) {
            // Обновляем данные о заказе
            $processedUid = (new MemoryOrderChangeController)->show($processedUid);

            $orderweb = Orderweb::where("dispatching_order_uid", $processedUid)->first();
            if (!$orderweb) {
                Log::warning('ProcessAutoOrder: Заказ исчез из базы', ['uid' => $processedUid]);
                return;
            }

            $url = $connectAPI . '/api/weborders/' . $processedUid;
            $responseArr = (new UniversalAndroidFunctionController)->getStatus($header, $url);

            Log::debug('ProcessAutoOrder: Ответ от API получен', [
                'uid' => $processedUid,
                'response' => $responseArr,
            ]);

            if (!isset($responseArr["order_car_info"]) || $responseArr["order_car_info"] === null) {
                Log::info('ProcessAutoOrder: auto == null, перезапуск SearchAutoOrderJob', ['uid' => $this->uid]);

                $orderweb->auto = null;
                $orderweb->save();
                if ($responseArr["close_reason"] == -1) {
                    SearchAutoOrderJob::dispatch($this->uid);
                    (new FCMController)->writeDocumentToFirestore($this->uid);
                }

                return;
            }

            if (isset($responseArr["close_reason"]) && $responseArr["close_reason"] != -1) {
                Log::info('ProcessAutoOrder: Заказ отменён/закрыт (close_reason != -1)', [
                    'uid' => $this->uid,
                    'close_reason' => $responseArr["close_reason"]
                ]);
                (new FCMController)->deleteDocumentFromFirestore($this->uid);
                (new FCMController)->deleteDocumentFromFirestoreOrdersTakingCancel($this->uid);
                (new FCMController)->deleteDocumentFromSectorFirestore($this->uid);
                (new FCMController)->writeDocumentToHistoryFirestore($this->uid, "cancelled");
                return;
            }

            // Проверка времени
            if (time() - $startTime > $maxDuration) {
                Log::warning('ProcessAutoOrder: Превышен лимит ожидания (30 минут)', [
                    'uid' => $this->uid
                ]);
                return;
            }

            sleep(30);
        }
    }
}
