<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {


        $uid = (new MemoryOrderChangeController)->show($this->uid);
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        if (!$order) {
            Log::warning("AutoCancelJob: заказ с uid {$uid} не найден.");
            return;
        }
        if ($order->required_time != null) {
            Log::warning("AutoCancelJob: required_time {$uid} не null.");
            return;
        }

        // Города, где разрешена автоотмена
        $autoCancelCities = [
            "city_lviv", "city_ivano_frankivsk", "city_vinnytsia", "city_poltava",
            "city_sumy", "city_kharkiv", "city_chernihiv", "city_rivne", "city_ternopil",
            "city_khmelnytskyi", "city_zakarpattya", "city_zhytomyr", "city_kropyvnytskyi",
            "city_mykolaiv", "city_chernivtsi", "city_lutsk", "all"
        ];

        if ($order->server === "http://188.40.143.61:7222") {
            // Киевский сервер - проверяем комендантский час
            $kyivTime = now()->timezone('Europe/Kiev');
            $currentTime = $kyivTime->format('H:i');

            $startTime = config('app.start_time', '00:00');
            $endTime = config('app.end_time', '05:00');

            // Если сейчас НЕ комендантский час - выходим
            if ($currentTime < $startTime || $currentTime > $endTime) {
                Log::info("AutoCancelJob: киевский сервер, но текущее время {$currentTime} вне комендантского часа ({$startTime} - {$endTime}) - автоотмена не применяется");
                return;
            }

            Log::info("AutoCancelJob: киевский сервер, время {$currentTime} в комендантском часе - применяем автоотмену");
        } else {
            // Проверка города для всех серверов (только для не 188.40.143.61:7222)
            if (!in_array($order->city, $autoCancelCities)) {
                Log::info("AutoCancelJob: автоотмена не применяется для города {$order->city}");
                return;
            }
        }



// Дальше логика автоотмены...

        // Определяем приложение по комментарию
        switch ($order->comment) {
            case 'taxi_easy_ua_pas1':
                $application = config("app.X-WO-API-APP-ID-PAS1");
                break;
            case 'taxi_easy_ua_pas2':
                $application = config("app.X-WO-API-APP-ID-PAS2");
                break;
            case 'taxi_easy_ua_pas4':
                $application = config("app.X-WO-API-APP-ID-PAS4");
                break;
            default:
                $application = config("app.X-WO-API-APP-ID-PAS5");
        }

        if ($order->city == "all") {
            $city = "Kyiv City";
        } else {
            $city = "OdessaTest";
        }

        (new AndroidTestOSMController)->webordersCancel(
            $uid,
            $city,
            $application
        );


//        $uid_history = Uid_history::where("uid_doubleOrder", $uid)->first();
//
//        if ($uid_history) {
//            // Если запись найдена, выходим из цикла
//            $uid = $uid_history->uid_bonusOrder;
//            $uid_Double = $uid_history->uid_doubleOrder;
//        }
//
//
//        if ($uid_history) {
//            (new AndroidTestOSMController)->webordersCancelDouble(
//                $uid,
//                $uid_Double,
//                $order->payment_type,
//                "OdessaTest",
//                $application
//            );
//        } else {
//            (new AndroidTestOSMController)->webordersCancel(
//                $uid,
//                "OdessaTest",
//                $application
//            );
//        }

        Log::info("AutoCancelJob: заказ {$this->uid} автоматически отменён через отложенную задачу.");
    }
}
