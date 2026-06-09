<?php

namespace App\Services;

use App\Helpers\TimeHelper;
use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\PusherController;
use App\Models\Orderweb;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderAutoCancelService
{
    public function hasScheduledRequiredTime($requiredTime): bool
    {
        if ($requiredTime === null || $requiredTime === '' || $requiredTime === false || $requiredTime === 'no_time') {
            return false;
        }

        return true;
    }

    public function tryCancelImmediateOrder(string $uid, string $logPrefix = 'AutoCancelJob'): bool
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::warning("{$logPrefix}: заказ с uid {$uid} не найден.");

            return false;
        }

        if ($this->shouldSkipAlreadyCancelledOrFinished($order, $logPrefix)) {
            return false;
        }

        if ($this->hasScheduledRequiredTime($order->required_time)) {
            Log::info("{$logPrefix}: заказ на время, uid {$uid} — immediate job пропускает");

            return false;
        }

        if ($order->auto !== null) {
            Log::info("{$logPrefix}: авто уже назначено, отмена не требуется (uid {$uid})");

            return false;
        }

        if (!$this->shouldApplyAutoCancelRules($order, $logPrefix)) {
            return false;
        }

        return $this->performAutoCancel($order, $uid, $logPrefix);
    }

    public function tryCancelScheduledOrder(Orderweb $order, string $logPrefix = 'ScheduledAutoCancel'): bool
    {
        $uid = $order->dispatching_order_uid;

        if ($this->shouldSkipAlreadyCancelledOrFinished($order, $logPrefix)) {
            return false;
        }

        if (!$this->hasScheduledRequiredTime($order->required_time)) {
            return false;
        }

        if ($order->auto !== null) {
            Log::info("{$logPrefix}: авто уже назначено, отмена не требуется (uid {$uid})");

            return false;
        }

        if (!$this->isScheduledCancelDeadlineReached($order)) {
            return false;
        }

        if (!$this->shouldApplyAutoCancelRules($order, $logPrefix)) {
            return false;
        }

        return $this->performAutoCancel($order, $uid, $logPrefix);
    }

    public function isScheduledCancelDeadlineReached(Orderweb $order): bool
    {
        return time() >= $this->getAutoCancelDeadlineTimestamp($order);
    }

    /**
     * Unix timestamp: когда можно отменять заказ без найденного авто.
     * Срочный — created_at + delay; на время — max(created_at, required_time) + delay.
     */
    public function getAutoCancelDeadlineTimestamp(Orderweb $order): int
    {
        $delaySeconds = $this->getDelaySeconds();
        $createdAt = $order->created_at ? strtotime((string) $order->created_at) : time();
        if ($createdAt === false) {
            $createdAt = time();
        }

        $deadline = $createdAt + $delaySeconds;

        if ($this->hasScheduledRequiredTime($order->required_time)) {
            $requiredTimestamp = $this->parseRequiredTimeTimestamp($order->required_time);
            if ($requiredTimestamp !== null) {
                $deadline = max($deadline, $requiredTimestamp + $delaySeconds);
            }
        }

        return $deadline;
    }

    public function findScheduledOrdersPendingCancel()
    {
        return Orderweb::query()
            ->whereNotNull('required_time')
            ->whereNull('auto')
            ->whereNull('cancel_timestamp')
            ->where(function ($query) {
                $query->whereIn('closeReason', ['100', '', '-1'])
                    ->orWhereNull('closeReason');
            })
            ->get();
    }

    private function getDelaySeconds(): int
    {
        return (int) config('orders.auto_cancel_delay_minutes', 15) * 60;
    }

    private function parseRequiredTimeTimestamp($requiredTime): ?int
    {
        try {
            $timestamp = Carbon::parse($requiredTime)->getTimestamp();
        } catch (\Throwable $e) {
            Log::warning('OrderAutoCancelService: не удалось разобрать required_time', [
                'required_time' => $requiredTime,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return $timestamp > 0 ? $timestamp : null;
    }

    private function shouldApplyAutoCancelRules(Orderweb $order, string $logPrefix): bool
    {
        $uid = $order->dispatching_order_uid;

        if ($order->server === 'my_server_api') {
            Log::info("{$logPrefix}: заказ my_server_api {$uid}, применяем автоотмену");

            return true;
        }

        if ($order->server === 'http://188.40.143.61:7222') {
            $curfew = TimeHelper::getCurfewStatus();

            if (!$curfew['curfew_active']) {
                Log::info("{$logPrefix}: киевский сервер, но текущее время {$curfew['current_time']} вне комендантского часа ({$curfew['start_time']} - {$curfew['end_time']}) - автоотмена не применяется");

                return false;
            }

            Log::info("{$logPrefix}: киевский сервер, время {$curfew['current_time']} в комендантском часе - применяем автоотмену");

            return true;
        }

        $autoCancelCities = [
            'city_lviv', 'city_ivano_frankivsk', 'city_vinnytsia', 'city_poltava',
            'city_sumy', 'city_kharkiv', 'city_chernihiv', 'city_rivne', 'city_ternopil',
            'city_khmelnytskyi', 'city_zakarpattya', 'city_zhytomyr', 'city_kropyvnytskyi',
            'city_mykolaiv', 'city_chernivtsi', 'city_lutsk', 'all',
        ];

        if (!in_array($order->city, $autoCancelCities, true)) {
            Log::info("{$logPrefix}: автоотмена не применяется для города {$order->city}");

            return false;
        }

        return true;
    }

    private function shouldSkipAlreadyCancelledOrFinished(Orderweb $order, string $logPrefix): bool
    {
        $uid = $order->dispatching_order_uid;
        $closeReason = (string) ($order->closeReason ?? '');

        if ($closeReason === '1') {
            Log::info("{$logPrefix}: заказ уже отменён (closeReason=1), uid {$uid}");

            return true;
        }

        if ($order->cancel_timestamp !== null) {
            Log::info("{$logPrefix}: cancel_timestamp задан, пропуск uid {$uid}");

            return true;
        }

        if (in_array($closeReason, ['101', '102', '103', '104'], true)) {
            Log::info("{$logPrefix}: заказ с авто/завершён (closeReason={$closeReason}), uid {$uid}");

            return true;
        }

        if ($closeReason !== '100' && $closeReason !== '' && $closeReason !== '-1') {
            Log::info("{$logPrefix}: неактивный closeReason={$closeReason}, uid {$uid}");

            return true;
        }

        return false;
    }

    private function performAutoCancel(Orderweb $order, string $uid, string $logPrefix): bool
    {
        $application = $this->resolveApplicationConfig($order);
        $city = $this->resolveCancelCity($order);

        (new AndroidTestOSMController)->webordersCancel(
            $uid,
            $city,
            $application
        );

        $this->sendCancelNotification($order, $uid, $logPrefix);

        Log::info("{$logPrefix}: заказ {$uid} автоматически отменён.");

        return true;
    }

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

    private function resolveApplicationConfig(Orderweb $order): string
    {
        switch ($order->comment) {
            case 'taxi_easy_ua_pas1':
                return config('app.X-WO-API-APP-ID-PAS1');
            case 'taxi_easy_ua_pas2':
                return config('app.X-WO-API-APP-ID-PAS2');
            case 'taxi_easy_ua_pas4':
                return config('app.X-WO-API-APP-ID-PAS4');
            default:
                return config('app.X-WO-API-APP-ID-PAS5');
        }
    }

    private function sendCancelNotification(Orderweb $order, string $uid, string $logPrefix): void
    {
        if (empty($order->email) || $order->email === 'no email') {
            Log::info("{$logPrefix}: push об отмене не отправлен — нет email (uid {$uid})");

            return;
        }

        $user = User::where('email', $order->email)->first();
        if (!$user) {
            Log::warning("{$logPrefix}: пользователь не найден для push об отмене (email {$order->email}, uid {$uid})");

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

        $reason = 'auto_cancel';
        if ((int) $order->payment_type === 1
            || (is_string($order->pay_system) && str_contains((string) $order->pay_system, 'wfp'))) {
            $reason = 'payment_timeout';
        }

        try {
            (new FCMController)->sendNotificationCancel(
                $body,
                $app,
                $user->id,
                $uid,
                $reason
            );
            Log::info("{$logPrefix}: FCM push об отмене отправлен (uid {$uid})");
        } catch (\Throwable $e) {
            Log::error("{$logPrefix}: ошибка FCM push об отмене (uid {$uid}): " . $e->getMessage());
        }

        try {
            (new PusherController)->sentCanceledStatus($app, $order->email, $uid);
            (new CentrifugoController)->sentCanceledStatus($app, $order->email, $uid);
            Log::info("{$logPrefix}: Pusher/Centrifugo canceled отправлен (uid {$uid})");
        } catch (\Throwable $e) {
            Log::error("{$logPrefix}: ошибка Pusher/Centrifugo (uid {$uid}): " . $e->getMessage());
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
