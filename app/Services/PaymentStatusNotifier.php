<?php

namespace App\Services;

use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\PusherController;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Pusher/Centrifugo + FCM при смене transactionStatus (FCM только для Declined).
 */
class PaymentStatusNotifier
{
    public static function notifyTransactionStatus(
        string $transactionStatus,
        string $uid,
        string $app,
        string $email
    ): void {
        try {
            (new PusherController)->sentStatusWfp($transactionStatus, $uid, $app, $email);
        } catch (\Throwable $e) {
            Log::error('PaymentStatusNotifier: Pusher', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
                'status'=> $transactionStatus,
            ]);
        }

        try {
            (new CentrifugoController)->sentStatusWfp($transactionStatus, $uid, $app, $email);
        } catch (\Throwable $e) {
            Log::error('PaymentStatusNotifier: Centrifugo', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
                'status'=> $transactionStatus,
            ]);
        }

        if ($transactionStatus !== 'Declined') {
            return;
        }

        self::sendPaymentErrorFcm($uid, $app, $email, $transactionStatus);
    }

    public static function sendPaymentErrorFcm(
        string $uid,
        string $app,
        string $email,
        string $transactionStatus = 'Declined'
    ): void {
        try {
            $user = User::where('email', $email)->first();
            if ($user === null) {
                Log::warning('PaymentStatusNotifier: пользователь не найден для FCM', [
                    'email' => $email,
                    'uid'   => $uid,
                ]);
                return;
            }

            $body = self::resolveOrderRouteBody($uid);

            (new FCMController)->sendNotificationPaymentError(
                $body,
                $app,
                $user->id,
                $uid,
                $transactionStatus
            );
        } catch (\Throwable $e) {
            Log::error('PaymentStatusNotifier: FCM payment error', [
                'error' => $e->getMessage(),
                'uid'   => $uid,
            ]);
        }
    }

    private static function resolveOrderRouteBody(string $uid): string
    {
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();
        if ($order === null) {
            return '';
        }

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
