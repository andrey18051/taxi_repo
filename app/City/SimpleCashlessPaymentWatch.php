<?php

namespace App\City;

use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\PusherController;
use App\Jobs\CheckAndCancelOrderJob;
use App\Jobs\SimplePollStatusJob;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Опрос оплаты и отложенная отмена — только my_server_api + привязанная карта (wfp_payment).
 * Обычные dispatch-серверы: StartStatusPaymentReview (1 мин) + chargeActiveToken (Declined → шторка в PAS).
 * Google Pay: заказ создаётся только после успешного hold на клиенте — watch не нужен.
 */
final class SimpleCashlessPaymentWatch
{
    private const LINKED_CARD_PAY_SYSTEM = 'wfp_payment';

    public static function shouldWatch(Orderweb $order): bool
    {
        if (($order->pay_system ?? '') !== self::LINKED_CARD_PAY_SYSTEM) {
            return false;
        }

        if ((int) $order->payment_type !== 1) {
            return false;
        }

        return $order->server === 'my_server_api';
    }

    public static function start(
        Orderweb $order,
        string $application,
        string $cityDisplayName,
        ?string $orderReference = null
    ): void {
        if (!self::shouldWatch($order)) {
            return;
        }

        $uid = $order->dispatching_order_uid;
        $orderReference = $orderReference ?? $order->wfp_order_id;
        $email = $order->email ?? '';

        if ($uid === null || $uid === '' || $orderReference === null || $orderReference === '') {
            return;
        }

        $delay = (int) config(
            'orders.simple_cashless_payment_check_delay_seconds',
            config('orders.my_server_api_payment_check_delay_seconds', 60)
        );

        SimplePollStatusJob::dispatch(
            $orderReference,
            $uid,
            $application,
            $email,
            $cityDisplayName
        )->onQueue('high');

        CheckAndCancelOrderJob::dispatch(
            $uid,
            $application,
            $email,
            $cityDisplayName
        )->onQueue('high')->delay(now()->addSeconds($delay));
    }

    /**
     * Pusher/Centrifugo + FCM «заказ отменён» — для простого безнала после таймаута оплаты.
     */
    public static function notifyClientOrderCanceled(Orderweb $order, string $application): void
    {
        if (PaymentFlow::normalize($order->payment_flow_mode ?? 0) !== PaymentFlow::SIMPLE) {
            return;
        }

        $uid = $order->dispatching_order_uid;
        $email = $order->email ?? '';

        if ($uid === null || $uid === '' || $email === '') {
            return;
        }

        try {
            (new PusherController)->sentCanceledStatus($application, $email, $uid);
            (new CentrifugoController)->sentCanceledStatus($application, $email, $uid);
        } catch (\Throwable $e) {
            Log::error('SimpleCashlessPaymentWatch: Pusher/Centrifugo cancel failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $user = User::where('email', $email)->first();
            if ($user === null) {
                Log::warning('SimpleCashlessPaymentWatch: user not found for cancel FCM', ['email' => $email]);

                return;
            }

            $body = self::resolveOrderRouteBody($order);

            (new FCMController)->sendNotificationCancel(
                $body,
                $application,
                $user->id,
                $uid,
                'payment_timeout'
            );
        } catch (\Throwable $e) {
            Log::error('SimpleCashlessPaymentWatch: FCM cancel failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function resolveCityDisplayName(Orderweb $order): string
    {
        if ($order->city === 'all' || $order->city === 'city_kiev') {
            return 'Kyiv City';
        }

        $cityMap = [
            'city_odessa' => 'OdessaTest',
            'city_cherkassy' => 'Cherkasy Oblast',
            'city_zaporizhzhia' => 'Zaporizhzhia',
            'city_dnipro' => 'Dnipropetrovsk Oblast',
        ];

        if (isset($cityMap[$order->city])) {
            return $cityMap[$order->city];
        }

        switch ($order->server) {
            case 'http://188.40.143.61:7222':
            case 'http://167.235.113.231:7306':
            case 'http://134.249.181.173:7208':
            case 'http://91.205.17.153:7208':
                return 'Kyiv City';
            case 'http://142.132.213.111:8071':
            case 'http://167.235.113.231:7308':
                return 'Dnipropetrovsk Oblast';
            case 'http://142.132.213.111:8072':
                return 'Odessa';
            case 'http://142.132.213.111:8073':
                return 'Zaporizhzhia';
            case 'http://134.249.181.173:7201':
            case 'http://91.205.17.153:7201':
                return 'Cherkasy Oblast';
            default:
                return 'OdessaTest';
        }
    }

    public static function resolveApplicationLabel(Orderweb $order): ?string
    {
        return CityPaymentFlowResolver::applicationFromIdentificationId($order->comment);
    }

    private static function resolveOrderRouteBody(Orderweb $order): string
    {
        $from = trim((string) ($order->routefrom ?? ''));
        $to = trim((string) ($order->routeto ?? ''));

        if ($from !== '' && $to !== '') {
            return $from . ' — ' . $to;
        }
        if ($from !== '') {
            return $from;
        }
        if ($to !== '') {
            return $to;
        }

        return '';
    }
}
