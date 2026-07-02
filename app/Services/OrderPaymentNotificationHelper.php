<?php

namespace App\Services;

use App\Helpers\OrderHelper;
use App\Http\Controllers\TelegramController;
use App\Models\Orderweb;
use Illuminate\Support\Facades\Log;

/**
 * Тексты Telegram и productName WFP: связка uid заказа ↔ orderReference (V_...).
 */
class OrderPaymentNotificationHelper
{
    public static function payTypeLabel(?string $paySystem): string
    {
        switch ($paySystem) {
            case 'google_pay_payment':
                return 'Google Pay';
            case 'wfp_payment':
                return 'Карта';
            case 'bonus_payment':
                return 'Бонуси';
            case 'fondy_payment':
                return 'Fondy';
            case 'mono_payment':
                return 'Mono';
            default:
                return 'Безнал';
        }
    }

    public static function formatNewOrderPayTypeLine(?string $paySystem, int $paymentType): string
    {
        if ($paymentType !== 1) {
            return 'Оплата наличными.';
        }

        switch ($paySystem) {
            case 'google_pay_payment':
                return 'Оплата Google Pay.';
            case 'wfp_payment':
                return 'Оплата картой.';
            case 'bonus_payment':
                return 'Оплата бонусами.';
            default:
                return 'Оплата картой (возможно бонусами).';
        }
    }

    public static function buildWfpProductName(string $dispatchUid, ?string $paySystem, $amountUah): string
    {
        $uidShort = strlen($dispatchUid) > 8 ? substr($dispatchUid, 0, 8) : $dispatchUid;
        $type = $paySystem === 'google_pay_payment' ? 'GP' : 'CARD';
        $amount = (string) $amountUah;

        return "Taxi uid={$uidShort} {$type} {$amount}UAH";
    }

    public static function buildPaymentBindTelegramMessage(object $orderweb, string $orderReference): string
    {
        $uid = (string) ($orderweb->dispatching_order_uid ?? '');
        $payLabel = self::payTypeLabel($orderweb->pay_system);
        $cost = OrderHelper::resolveDisplayCostGrivna($orderweb);

        return "Прив'язка оплати. Заказ: {$uid}. WFP: {$orderReference}. Тип: {$payLabel}. Сума: {$cost} грн.";
    }

    public static function notifyPaymentBoundTelegram(Orderweb $orderweb, string $orderReference): void
    {
        if ($orderReference === '' || $orderReference === '*') {
            return;
        }

        $paySystem = (string) ($orderweb->pay_system ?? '');
        if (!in_array($paySystem, ['wfp_payment', 'google_pay_payment'], true)) {
            return;
        }

        $message = self::buildPaymentBindTelegramMessage($orderweb, $orderReference);

        try {
            $telegram = new TelegramController();
            $telegram->sendMeMessage($message);
            $telegram->sendAlarmMessage($message);
        } catch (\Throwable $e) {
            Log::error('notifyPaymentBoundTelegram failed', [
                'uid' => $orderweb->dispatching_order_uid ?? '',
                'orderReference' => $orderReference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function notifyForkCancelConfirmedTelegram(Orderweb $orderweb, ?string $channelNote = null): void
    {
        try {
            (new \App\Http\Controllers\MessageSentController())->sentCancelInfo(
                $orderweb,
                OrderCancelNotificationHelper::INITIATOR_DISPATCHER,
                $channelNote
            );
        } catch (\Throwable $e) {
            Log::error('notifyForkCancelConfirmedTelegram: sentCancelInfo failed', [
                'uid' => $orderweb->dispatching_order_uid ?? '',
                'error' => $e->getMessage(),
            ]);
        }

        if ($channelNote !== null && $channelNote !== '') {
            try {
                (new \App\Http\Controllers\MessageSentController())->sentMessageMeCancel($channelNote);
            } catch (\Throwable $e) {
                Log::error('notifyForkCancelConfirmedTelegram: sentMessageMeCancel failed', [
                    'uid' => $orderweb->dispatching_order_uid ?? '',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public static function appendWfpReferenceToCancelMessage(string $message, ?string $wfpOrderId): string
    {
        if ($wfpOrderId === null || $wfpOrderId === '') {
            return $message;
        }

        return $message . " Оплата WFP: {$wfpOrderId}.";
    }
}
