<?php

namespace App\Jobs;

use App\Helpers\KyivStateHelper;
use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\PusherController;
use App\Models\Orderweb;
use App\Models\User;
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

        if ($order->auto !== null) {
            Log::info("AutoCancelJob: авто уже назначено, отмена не требуется (uid {$uid})");
            return;
        }

        if ($order->server === 'my_server_api') {
            Log::info("AutoCancelJob: заказ my_server_api {$uid}, применяем автоотмену");
        } elseif ($order->server === 'http://188.40.143.61:7222') {
            // Киевский сервер — только в комендантский час
            $curfew = KyivStateHelper::getCurfewStatus();

            if (!$curfew['curfew_active']) {
                Log::info("AutoCancelJob: киевский сервер, но текущее время {$curfew['current_time']} вне комендантского часа ({$curfew['start_time']} - {$curfew['end_time']}) - автоотмена не применяется");
                return;
            }

            Log::info("AutoCancelJob: киевский сервер, время {$curfew['current_time']} в комендантском часе - применяем автоотмену");
        } else {
            $autoCancelCities = [
                'city_lviv', 'city_ivano_frankivsk', 'city_vinnytsia', 'city_poltava',
                'city_sumy', 'city_kharkiv', 'city_chernihiv', 'city_rivne', 'city_ternopil',
                'city_khmelnytskyi', 'city_zakarpattya', 'city_zhytomyr', 'city_kropyvnytskyi',
                'city_mykolaiv', 'city_chernivtsi', 'city_lutsk', 'all',
            ];

            if (!in_array($order->city, $autoCancelCities, true)) {
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

        $city = $this->resolveCancelCity($order);

        (new AndroidTestOSMController)->webordersCancel(
            $uid,
            $city,
            $application
        );

        $this->sendCancelNotification($order, $uid);


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

    /**
     * Город для webordersCancel (для my_server_api внешний API не вызывается, но нужен для Firestore/логики).
     */
    private function resolveCancelCity(Orderweb $order): string
    {
        if ($order->city === 'all' || $order->city === 'city_kiev') {
            return 'Kyiv City';
        }

        $cityMap = [
            'city_odessa' => 'OdessaTest',
            'city_cherkassy' => 'Cherkasy Oblast',
            'city_zaporizhzhia' => 'Zaporizhzhia',
            'city_dnipro' => 'DniproTest',
        ];

        return $cityMap[$order->city] ?? 'OdessaTest';
    }

    private function sendCancelNotification(Orderweb $order, string $uid): void
    {
        if (empty($order->email) || $order->email === 'no email') {
            Log::info("AutoCancelJob: push об отмене не отправлен — нет email (uid {$uid})");
            return;
        }

        $user = User::where('email', $order->email)->first();
        if (!$user) {
            Log::warning("AutoCancelJob: пользователь не найден для push об отмене (email {$order->email}, uid {$uid})");
            return;
        }

        $from = trim((string) ($order->routefrom ?? ''));
        $to = trim((string) ($order->routeto ?? ''));
        if ($from !== '' && $to !== '') {
            $body = $from . ' — ' . $to;
        } elseif ($from !== '') {
            $body = $from;
        } elseif ($to !== '') {
            $body = $to;
        } else {
            $body = $uid;
        }

        $app = $this->resolvePasApp($order);

        try {
            (new FCMController)->sendNotificationCancel(
                $body,
                $app,
                $user->id,
                $uid
            );
            Log::info("AutoCancelJob: FCM push об отмене отправлен (uid {$uid})");
        } catch (\Throwable $e) {
            Log::error("AutoCancelJob: ошибка FCM push об отмене (uid {$uid}): " . $e->getMessage());
        }

        if (!empty($order->email) && $order->email !== 'no email') {
            try {
                (new PusherController)->sentCanceledStatus($app, $order->email, $uid);
                (new CentrifugoController)->sentCanceledStatus($app, $order->email, $uid);
                Log::info("AutoCancelJob: Pusher/Centrifugo canceled отправлен (uid {$uid})");
            } catch (\Throwable $e) {
                Log::error("AutoCancelJob: ошибка Pusher/Centrifugo (uid {$uid}): " . $e->getMessage());
            }
        }
    }

    private function resolvePasApp(Orderweb $order): string
    {
        switch ($order->comment) {
            case 'taxi_easy_ua_pas1':
                return 'PAS1';
            case 'taxi_easy_ua_pas2':
                return 'PAS2';
            case 'taxi_easy_ua_pas4':
                return 'PAS4';
            default:
                return 'PAS5';
        }
    }
}
