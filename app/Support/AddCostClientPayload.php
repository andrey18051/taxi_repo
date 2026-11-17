<?php

namespace App\Support;

/**
 * Ответ chargeActiveTokenAddCost, который PAS разбирает как PurchaseResponse.
 * Пустой transactionStatus роняет switch в приложении (NPE getClass на null).
 */
final class AddCostClientPayload
{
    public const STATUS_PROCESSING = 'InProcessing';

    public const STATUS_DECLINED = 'Declined';

    public static function pendingCancel(): array
    {
        return [
            'response' => 'dispatch_cancel_pending',
            'transactionStatus' => self::STATUS_PROCESSING,
        ];
    }

    public static function failed(string $response = '400'): array
    {
        return [
            'response' => $response,
            'transactionStatus' => self::STATUS_DECLINED,
        ];
    }

    /**
     * Успешное пересоздание после доплаты.
     * client_cost — сколько списали с клиента (7+5). web_cost — цена диспетчера, она может быть ниже.
     */
    public static function recreated(string $uid, $webCost, $clientCost, object $order): array
    {
        return [
            'uid' => $uid,
            'web_cost' => $webCost,
            'client_cost' => $clientCost,
            'routefrom' => $order->routefrom ?? null,
            'startLat' => $order->startLat ?? null,
            'startLan' => $order->startLan ?? null,
            'routeto' => $order->routeto ?? null,
            'to_lat' => $order->to_lat ?? null,
            'to_lng' => $order->to_lng ?? null,
            'pay_system' => $order->pay_system ?? null,
            'transactionStatus' => 'WaitingAuthComplete',
        ];
    }
}
